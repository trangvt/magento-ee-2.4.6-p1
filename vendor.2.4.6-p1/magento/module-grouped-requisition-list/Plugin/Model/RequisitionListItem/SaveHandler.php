<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedRequisitionList\Plugin\Model\RequisitionListItem;

use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder;
use Magento\RequisitionList\Model\RequisitionListProduct;
use Magento\RequisitionList\Model\RequisitionListItem\Locator;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder\ConfigurationException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\RequisitionList\Model\RequisitionListItem\SaveHandler as SaveHandlerModel;

/**
 * Plugin for save handler requisition list for grouped product
 */
class SaveHandler
{
    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var Builder
     */
    private $optionsBuilder;

    /**
     * @var RequisitionListManagementInterface
     */
    private $requisitionListManagement;

    /**
     * @var Locator
     */
    private $requisitionListItemLocator;

    /**
     * @var RequisitionListProduct
     */
    private $requisitionListProduct;

    /**
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param Builder $optionsBuilder
     * @param RequisitionListManagementInterface $requisitionListManagement
     * @param Locator $requisitionListItemLocator
     * @param RequisitionListProduct $requisitionListProduct
     */
    public function __construct(
        RequisitionListRepositoryInterface $requisitionListRepository,
        Builder $optionsBuilder,
        RequisitionListManagementInterface $requisitionListManagement,
        Locator $requisitionListItemLocator,
        RequisitionListProduct $requisitionListProduct
    ) {
        $this->requisitionListRepository = $requisitionListRepository;
        $this->optionsBuilder = $optionsBuilder;
        $this->requisitionListManagement = $requisitionListManagement;
        $this->requisitionListItemLocator = $requisitionListItemLocator;
        $this->requisitionListProduct = $requisitionListProduct;
    }

    /**
     * Save requisition list for grouped product.
     *
     * @param SaveHandlerModel $subject
     * @param \Closure $proceed
     * @param DataObject $productData
     * @param array $options
     * @param int $itemId
     * @param int $listId
     *
     * @return Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function aroundSaveItem(
        SaveHandlerModel $subject,
        \Closure $proceed,
        DataObject $productData,
        array $options,
        $itemId,
        $listId
    ) {
        $parentProduct = $this->requisitionListProduct->getProduct((string)$productData->getSku());
        if ($parentProduct->getTypeId() !== Grouped::TYPE_CODE) {
            return $proceed($productData, $options, $itemId, $listId);
        }
        $options = $productData->getOptions();
        $buyRequest = new DataObject($options);
        $cartCandidates = $parentProduct->getTypeInstance()->prepareForCartAdvanced(
            $buyRequest,
            $parentProduct,
            AbstractType::PROCESS_MODE_FULL
        );
        if (is_string($cartCandidates) || $cartCandidates instanceof Phrase) {
            throw new ConfigurationException(__($cartCandidates));
        }

        $cartCandidates = (array)$cartCandidates;
        $requisitionList = $this->requisitionListRepository->get($listId);
        $items = $requisitionList->getItems();
        foreach ($cartCandidates as $candidate) {
            $qty = $candidate->getCartQty();
            $itemOptions = $this->optionsBuilder->build($options, $itemId, false);
            if (empty($itemOptions['info_buyRequest']['item'])) {
                $itemOptions['info_buyRequest']['item'] = $parentProduct->getId();
            }
            $item = $this->requisitionListItemLocator->getItem($itemId);
            $item->setQty($qty);
            $item->setOptions($itemOptions);
            $item->setSku($candidate->getSku());
            if ($item->getId()) {
                foreach ($items as $i => $existItem) {
                    if ($existItem->getId() == $item->getId()) {
                        $items[$i] = $item;
                    }
                }
            } else {
                $items[] = $item;
            }
        }
        $message = __(
            'Product %1 has been added to the requisition list %2.',
            $parentProduct->getName(),
            $requisitionList->getName()
        );
        $this->requisitionListManagement->setItemsToList($requisitionList, $items);
        $this->requisitionListRepository->save($requisitionList);

        return $message;
    }
}
