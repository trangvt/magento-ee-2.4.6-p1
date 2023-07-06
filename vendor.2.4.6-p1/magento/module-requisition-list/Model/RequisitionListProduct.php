<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;

/**
 * Actions with product for the requisition list.
 */
class RequisitionListProduct
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var array
     */
    private $products = [];

    /**
     * @var array
     */
    private $productTypesToConfigure;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    private $productType;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var OptionsManagement
     */
    private $optionsManagement;

    /**
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Catalog\Model\Product\Type $productType
     * @param array $productTypesToConfigure [optional]
     * @param Json $jsonSerializer [optional]
     * @param OptionsManagement $optionsManagement [optional]
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Catalog\Model\Product\Type $productType,
        array $productTypesToConfigure = [],
        ?Json $jsonSerializer = null,
        ?OptionsManagement $optionsManagement = null
    ) {
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
        $this->productType = $productType;
        $this->productTypesToConfigure = $productTypesToConfigure;
        $this->jsonSerializer = $jsonSerializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->optionsManagement = $optionsManagement ?: ObjectManager::getInstance()->get(OptionsManagement::class);
    }

    /**
     * Get product by sku.
     *
     * Returns product object if product with provided sku exists and is visible in catalog and false if product
     * with this sku does not exist or is not visible in catalog
     *
     * @param string $sku
     * @return ProductInterface|bool
     */
    public function getProduct($sku)
    {
        if (!isset($this->products[$sku])) {
            try {
                $product = $this->productRepository->get($sku);

                if ($product->isVisibleInCatalog()) {
                    $this->products[$sku] = $product;
                } else {
                    $this->products[$sku] = false;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->products[$sku] = false;
            }
        }

        return $this->products[$sku];
    }

    /**
     * Check if it is necessary to configure product.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isProductShouldBeConfigured(ProductInterface $product)
    {
        if (in_array($product->getTypeId(), $this->productTypesToConfigure)) {
            return true;
        }

        $typeInstance = $this->productType->factory($product);
        return $typeInstance->hasRequiredOptions($product);
    }

    /**
     * Prepare product information.
     *
     * @param string|array $productData
     * @return \Magento\Framework\DataObject
     */
    public function prepareProductData($productData)
    {
        if (is_string($productData)) {
            $productData = $this->jsonSerializer->unserialize($productData);
        }

        if (isset($productData['options'])) {
            $options = [];
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            parse_str($productData['options'], $options);
            $productData['options'] = $options;
        }

        return new \Magento\Framework\DataObject($productData);
    }

    /**
     * Prepare multiple product information.
     *
     * @param array $productData
     * @return \Magento\Framework\DataObject[]
     */
    public function prepareMultipleProductData(array $productData)
    {
        $products = [];
        foreach ($productData as $product) {
            $products[] = $this->prepareProductData($product);
        }

        return $products;
    }

    /**
     * Does $product exist in $requisitionList's items already?
     *
     * @param RequisitionListInterface $requisitionList
     * @param ProductInterface $product
     * @param array $productOptions
     * @return bool
     */
    public function isProductExistsInRequisitionList(
        RequisitionListInterface $requisitionList,
        ProductInterface $product,
        array $productOptions
    ) {
        $productExists = false;

        $requisitionListItems = $requisitionList->getItems();

        foreach ($requisitionListItems as $requisitionListItem) {
            $itemOptions = $requisitionListItem->getOptions();
            $buyRequestData = $this->optionsManagement->getInfoBuyRequest($requisitionListItem);

            if (isset($itemOptions['simple_product'])) {
                $itemProductId = $itemOptions['simple_product'];
            } elseif (isset($buyRequestData['product'])) {
                $itemProductId = $buyRequestData['product'];
            } else {
                $itemProductId = null;
            }

            if ($itemProductId !== null && $itemProductId != $product->getId()) {
                continue;
            }

            $productExists = true;

            if (isset($buyRequestData['super_attribute']) && isset($productOptions['super_attribute'])) {
                $hasSameOptions = $buyRequestData['super_attribute'] == $productOptions['super_attribute'];
                $productExists = $hasSameOptions;
            }

            if ($productExists) {
                break;
            }
        }

        return $productExists;
    }
}
