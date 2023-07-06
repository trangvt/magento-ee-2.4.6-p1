<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedRequisitionList\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

/**
 * Class for retrieve data about a grouped product from request
 */
class RetrieveParentByRequest
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * OptionsManagement constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * Get grouped product ID
     *
     * @param array $infoBuyRequest
     *
     * @return int
     */
    private function getParentIdByRequest(array $infoBuyRequest): int
    {
        $parentProductId = 0;
        if ((!empty($infoBuyRequest['super_group']) || !empty($infoBuyRequest['super_product_config']))
            && !empty($infoBuyRequest['item'])
        ) {
            $parentProductId = $infoBuyRequest['item'];
        } elseif (empty($infoBuyRequest['item'])
            && !empty($infoBuyRequest['super_product_config']['product_id'])
        ) {
            $parentProductId = $infoBuyRequest['super_product_config']['product_id'];
        }

        return (int)$parentProductId;
    }

    /**
     * Get parent grouped product by ID
     *
     * @param int $id
     *
     * @return null|ProductInterface
     */
    private function getParentProductById(int $id): ?ProductInterface
    {
        $parentProduct = $this->productRepository->getById($id);

        return $parentProduct->getTypeId() === Grouped::TYPE_CODE ? $parentProduct : null;
    }

    /**
     * Get parent product by request
     *
     * @param array $infoBuyRequest
     *
     * @return ProductInterface|null
     */
    public function execute(array $infoBuyRequest): ?ProductInterface
    {
        $parentProduct = null;
        $parentProductId = $this->getParentIdByRequest($infoBuyRequest);
        if ($parentProductId) {
            try {
                $parentProduct = $this->getParentProductById($parentProductId);
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }

        return $parentProduct;
    }
}
