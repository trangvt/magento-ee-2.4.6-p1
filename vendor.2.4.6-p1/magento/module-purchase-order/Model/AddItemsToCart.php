<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\AddProductsToCartError;
use Magento\Quote\Model\Cart\Data\Error;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Add items (from purchase order quote) to cart
 */
class AddItemsToCart
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $cartRepository;

    /**
     * @var AddProductsToCartError
     */
    private AddProductsToCartError $addProductsToCartError;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CartRepositoryInterface $cartRepository
     * @param AddProductsToCartError $addProductsToCartError
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        CartRepositoryInterface $cartRepository,
        AddProductsToCartError $addProductsToCartError
    ) {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->cartRepository = $cartRepository;
        $this->addProductsToCartError = $addProductsToCartError;
    }

    /**
     * Add items (from purchase order quote) to cart
     *
     * @param Quote $quote
     * @param PurchaseOrderInterface $purchaseOrder
     * @param bool $replace
     * @return Error[]
     * @throws LocalizedException
     */
    public function execute(Quote $quote, PurchaseOrderInterface $purchaseOrder, bool $replace = false): array
    {
        $items = $purchaseOrder->getSnapshotQuote()->getAllVisibleItems();
        if ($replace) {
            $quote->removeAllItems();
        }

        $errors = [];
        foreach ($items as $position => $item) {
            if ($item->getParentItem() !== null) {
                continue;
            }
            try {
                $product = $this->productRepository->getById(
                    $item->getProductId(),
                    false,
                    $this->storeManager->getStore()->getId(),
                    true
                );
            } catch (NoSuchEntityException $e) {
                $errors[] = $this->addProductsToCartError->create('Could not find a product with SKU', $position);
                continue;
            }
            $options = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
            $info = new DataObject($options['info_buyRequest'] ?? []);
            $info->setQty($item->getQty());
            try {
                $quote->addProduct($product, $info);
            } catch (LocalizedException $exception) {
                $errors[] = $this->addProductsToCartError->create($exception->getMessage(), $position);
            }
        }

        if (count($items) !== count($errors)) {
            $quote->getBillingAddress();
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->collectTotals();
            $this->cartRepository->save($quote);
        }

        return $errors;
    }
}
