<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList\Item;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Model\Cart\BuyRequest\BuyRequestBuilder;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder;
use Magento\RequisitionList\Model\RequisitionListItem\SaveHandler;

/**
 * Add Products items to Requisition list.
 */
class AddItemsToRequisitionList
{
    /**
     * @var BuyRequestBuilder
     */
    private $requestBuilder;

    /**
     * @var Builder
     */
    private $optionsBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SaveHandler
     */
    private $requisitionSaveHandler;

    /**
     * AddItemsToRequisitionList constructor.
     * @param BuyRequestBuilder $requestBuilder
     * @param Builder $optionsBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param SaveHandler $requisitionSaveHandler
     */
    public function __construct(
        BuyRequestBuilder $requestBuilder,
        Builder $optionsBuilder,
        ProductRepositoryInterface $productRepository,
        SaveHandler $requisitionSaveHandler
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->optionsBuilder = $optionsBuilder;
        $this->productRepository = $productRepository;
        $this->requisitionSaveHandler = $requisitionSaveHandler;
    }

    /**
     * Add Items to specific requisition list
     *
     * @param RequisitionListInterface $requisitionList
     * @param array $items
     * @return void
     * @throws Builder\ConfigurationException
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function execute(
        RequisitionListInterface $requisitionList,
        array $items
    ): void {
        foreach ($items as $item) {
            $sku = $item->getSku();
            $qty = $item->getQuantity();
            $options = $this->requestBuilder->build($item);
            $itemOptions = $this->optionsBuilder->build($options->getData(), null, false);
            try {
                $product = $this->productRepository->get($sku);
            } catch (NoSuchEntityException $exception) {
                throw new GraphQlInputException(__('The SKU was not found.'));
            }
            $itemOptions = $this->prepareOptions($itemOptions, $product, $qty);

            $productData = $this->prepareProductData($itemOptions);
            $optionsData = $itemOptions['options'];
            $listId = (int)$requisitionList->getId();
            $itemId = (int)$itemOptions['options']['item_id'];

            $this->requisitionSaveHandler->saveItem($productData, $optionsData, $itemId, $listId);
        }
    }

    /**
     * Prepare product data object.
     *
     * @param array $productData
     * @return DataObject
     */
    public function prepareProductData(array $productData): DataObject
    {
        return new DataObject($productData);
    }

    /**
     * Prepare Product types options
     *
     * @param array $options
     * @param ProductInterface $product
     * @param float $qty
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function prepareOptions(array $options, ProductInterface $product, float $qty): array
    {
        $options['product'] = $product->getId();
        $options['item'] = $product->getId();
        $options['item_id'] = 0;

        if (!empty($options['info_buyRequest'])) {
            $infoBuyRequest = $options['info_buyRequest'];
            $options['qty'] = $infoBuyRequest['qty'];

            /**
             * loads the additional options for custom options
             */
            if (!empty($infoBuyRequest['options'])) {
                $infoOptions = $infoBuyRequest['options'];
                $options['options'] = $infoOptions;
            }
        }
        unset($options['info_buyRequest']);
        $options['options'] = $options;
        $options['sku'] = $product->getSku();

        return $options;
    }
}
