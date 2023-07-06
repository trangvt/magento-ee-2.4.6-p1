<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\Quote\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Api\StatusInfoInterface;
use Magento\SharedCatalog\Model\SharedCatalogLocator;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Denies to add product to cart if SharedCatalog module is active and product is not in a shared catalog.
 */
class ValidateAddProductToCartPlugin
{
    /**
     * @var StatusInfoInterface
     */
    private $moduleConfig;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var SharedCatalogLocator
     */
    private $sharedCatalogLocator;

    /**
     * @var SharedCatalogManagementInterface
     */
    private $sharedCatalogManagement;

    /**
     * @var ProductManagementInterface
     */
    private $sharedCatalogProductManagement;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param StatusInfoInterface $moduleConfig
     * @param CartRepositoryInterface $quoteRepository
     * @param SharedCatalogLocator $sharedCatalogLocator
     * @param SharedCatalogManagementInterface $sharedCatalogManagement
     * @param ProductManagementInterface $sharedCatalogProductManagement
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StatusInfoInterface $moduleConfig,
        CartRepositoryInterface $quoteRepository,
        SharedCatalogLocator $sharedCatalogLocator,
        SharedCatalogManagementInterface $sharedCatalogManagement,
        ProductManagementInterface $sharedCatalogProductManagement,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->quoteRepository = $quoteRepository;
        $this->sharedCatalogLocator = $sharedCatalogLocator;
        $this->sharedCatalogManagement = $sharedCatalogManagement;
        $this->sharedCatalogProductManagement = $sharedCatalogProductManagement;
        $this->storeManager = $storeManager;
    }

    /**
     * Denies to add product to cart if SharedCatalog module is active and product is not in a shared catalog.
     *
     * @param CartItemRepositoryInterface $subject
     * @param CartItemInterface $cartItem
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        CartItemRepositoryInterface $subject,
        CartItemInterface $cartItem
    ) {
        $website = $this->storeManager->getWebsite()->getId();
        if ($this->moduleConfig->isActive(ScopeInterface::SCOPE_WEBSITE, $website)) {
            $quote = $this->quoteRepository->get($cartItem->getQuoteId());
            $sku = $cartItem->getSku();

            if (!$this->isProductInPublicCatalog($sku)) {

                $sharedCatalog = $this->getSharedCatalog($quote->getCustomerGroupId());

                if (!$sharedCatalog || !$this->isProductInSharedCatalog($sharedCatalog->getId(), $sku)) {
                    throw new NoSuchEntityException(
                        __('Requested product doesn\'t exist: %1.', $sku)
                    );
                }
            }
        }

        return [$cartItem];
    }

    /**
     * Get shared catalog by customer group id if $customerGroupId is not empty or load public shared catalog.
     *
     * @param int $customerGroupId
     * @return SharedCatalogInterface|null
     */
    private function getSharedCatalog($customerGroupId)
    {
        if ($customerGroupId) {
            return $this->sharedCatalogLocator->getSharedCatalogByCustomerGroup($customerGroupId);
        }

        return $this->sharedCatalogManagement->getPublicCatalog();
    }

    /**
     * Is shared catalog contain the product with given sku.
     *
     * @param int $sharedCatalogId
     * @param string $sku
     * @return bool
     */
    private function isProductInSharedCatalog($sharedCatalogId, $sku)
    {
        $productSkus = $this->sharedCatalogProductManagement->getProducts($sharedCatalogId);

        return in_array($sku, $productSkus);
    }

    /**
     * Is a product with a given sku assigned to a public catalog
     *
     * @param string $sku
     * @return bool
     */
    private function isProductInPublicCatalog($sku): bool
    {
        if (!$this->sharedCatalogManagement->isPublicCatalogExist()) {
            return false;
        }
        $publicCatalog = $this->sharedCatalogManagement->getPublicCatalog();
        $productSkus = $this->sharedCatalogProductManagement->getProducts($publicCatalog->getId());

        return in_array($sku, $productSkus);
    }
}
