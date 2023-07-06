<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedRequisitionList\Plugin\Block\Requisition\View;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\RequisitionList\Block\Requisition\View\Item as ItemBlock;
use Magento\GroupedRequisitionList\Model\RetrieveParentByRequest;

/**
 * Plugin for get product url for grouped product
 */
class Item
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var RetrieveParentByRequest
     */
    private $retrieveParentByRequest;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param RetrieveParentByRequest $retrieveParentByRequest
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        RetrieveParentByRequest $retrieveParentByRequest
    ) {
        $this->productRepository = $productRepository;
        $this->retrieveParentByRequest = $retrieveParentByRequest;
    }

    /**
     * Get product url for grouped product
     *
     * @param ItemBlock $subject
     * @param \Closure $proceed
     *
     * @return string|null
     */
    public function aroundGetProductUrlByItem(
        ItemBlock $subject,
        \Closure $proceed
    ) {
        if (empty($subject->getItem()->getOptions()['info_buyRequest'])) {
            return $proceed();
        }

        try {
            $product = $this->retrieveParentByRequest->execute(
                $subject->getItem()->getOptions()['info_buyRequest']
            );
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $product ? $product->getProductUrl() : $proceed();
    }
}
