<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedRequisitionList\Plugin\Model;

use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\OptionsManagement as RequisitionListOptionManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\RequisitionList\Model\RequisitionListItem\OptionFactory;
use Magento\GroupedRequisitionList\Model\RetrieveParentByRequest;

/**
 * Plugin for requisition options management
 */
class OptionsManagement
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var OptionFactory
     */
    private $itemOptionsFactory;

    /**
     * @var retrieveParentByRequest
     */
    private $retrieveParentByRequest;

    /**
     * OptionsManagement constructor.
     *
     * @param OptionFactory $itemOptionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param RetrieveParentByRequest $retrieveParentByRequest
     */
    public function __construct(
        OptionFactory $itemOptionFactory,
        ProductRepositoryInterface $productRepository,
        RetrieveParentByRequest $retrieveParentByRequest
    ) {
        $this->itemOptionsFactory = $itemOptionFactory;
        $this->productRepository = $productRepository;
        $this->retrieveParentByRequest = $retrieveParentByRequest;
    }

    /**
     * Get options with parent product
     *
     * @param RequisitionListOptionManager $subject
     * @param array $options
     * @param RequisitionListItemInterface $item
     *
     * @return array
     */
    public function afterGetOptions(
        RequisitionListOptionManager $subject,
        array $options,
        RequisitionListItemInterface $item
    ): array {
        if ($item->getId()) {
            $infoBuyRequest = $subject->getInfoBuyRequest($item);
            $product = $this->retrieveParentByRequest->execute($infoBuyRequest);
            if ($product) {
                $infoBuyRequest['item'] = $infoBuyRequest['item'] ?? $product->getId();
                $option = $this->itemOptionsFactory->create()
                    ->setData('value', 'grouped')
                    ->setData('code', 'product_type');
                $option->setProduct($product);
                $options['product_type'] = $option;
            }
        }

        return $options;
    }
}
