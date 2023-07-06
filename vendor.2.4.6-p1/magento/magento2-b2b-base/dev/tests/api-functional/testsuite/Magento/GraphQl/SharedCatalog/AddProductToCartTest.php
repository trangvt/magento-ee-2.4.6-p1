<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\SharedCatalog;

use Magento\ConfigurableProductGraphQl\Model\Options\SelectionUidFormatter;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\TestFramework\ObjectManager;

/**
 * Add products to cart from a specific shared catalog
 */
class AddProductToCartTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var SelectionUidFormatter
     */
    private $selectionUidFormatter;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->selectionUidFormatter = $this->objectManager->get(SelectionUidFormatter::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    }

    /**
     * Response should have cart items available
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store btob/website_configuration/company_active 1
     * @magentoConfigFixture default_store btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/multiple_shared_catalogs.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/companies_with_admin.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/categories.php
     */
    public function testProductIsAddedToCart()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'product_00';
        $desiredQuantity = 5;
        $currentEmail = 'admin@0company.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
            ->execute($currentEmail, $currentPassword);
        $cartId = $this->createEmptyCart($headerAuthorization);

        $response = $this->graphQlMutation(
            $this->prepareMutation($cartId, $productSku, $desiredQuantity),
            [],
            '',
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertNotEmpty($response['addProductsToCart']['cart']['items']);
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($productSku, $cartItems[0]['product']['sku']);
        $this->assertArrayHasKey('user_errors', $response['addProductsToCart']);
        $userErrors = $response['addProductsToCart']['user_errors'];
        self::assertCount(0, $userErrors);
    }

    /**
     * Response should have configurable product in the cart.
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store btob/website_configuration/company_active 1
     * @magentoConfigFixture default_store btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/multiple_shared_catalogs.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/companies_with_admin.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/categories.php
     */
    public function testConfigurableProductIsAddedToCart()
    {
        $this->reindexCatalogPermissions();
        $productSku = 'configurable';

        /** @var \Magento\Catalog\Model\Product $configurableProduct */
        $configurableProduct = $this->productRepository->get($productSku);
        $attributeId = (int)$configurableProduct->getExtensionAttributes()
            ->getConfigurableProductOptions()[0]
            ->getAttributeId();
        $valueIndex = (int)$configurableProduct->getExtensionAttributes()
            ->getConfigurableProductOptions()[0]
            ->getOptions()[0]['value_index'];
        $desiredQuantity = 3;
        $currentEmail = 'admin@0company.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
            ->execute($currentEmail, $currentPassword);
        $cartId = $this->createEmptyCart($headerAuthorization);
        $options = $this->generateSuperAttributesUIDQuery($attributeId, $valueIndex);
        $mutation = $this->getAddConfigurableProductToCartMutation($cartId, $productSku, $desiredQuantity, $options);
        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertNotEmpty($response['addProductsToCart']['cart']['items']);
        $cartItems = $response['addProductsToCart']['cart']['items'];
        $this->assertCount(1, $cartItems);
        $this->assertEquals($desiredQuantity, $cartItems[0]['quantity']);
        $this->assertEquals($productSku, $cartItems[0]['product']['sku']);
        $this->assertEquals($attributeId, $cartItems[0]['configurable_options'][0]['id']);
        $this->assertEquals($valueIndex, $cartItems[0]['configurable_options'][0]['value_id']);
        $this->assertArrayHasKey('user_errors', $response['addProductsToCart']);
        $userErrors = $response['addProductsToCart']['user_errors'];
        self::assertCount(0, $userErrors);
    }

    /**
     * Response should have no configurable product in cart
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store btob/website_configuration/company_active 1
     * @magentoConfigFixture default_store btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/multiple_shared_catalogs.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/companies_with_admin.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/categories.php
     */
    public function testConfigurableProductIsDeniedToCart()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'configurable';
        $desiredQuantity = 5;
        $currentEmail = 'admin@2company.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
            ->execute($currentEmail, $currentPassword);
        $cartId = $this->createEmptyCart($headerAuthorization);
        /** @var \Magento\Catalog\Model\Product $configurableProduct */
        $configurableProduct = $this->productRepository->get($productSku);
        $attributeId = (int)$configurableProduct->getExtensionAttributes()
            ->getConfigurableProductOptions()[0]
            ->getAttributeId();
        $valueIndex = (int)$configurableProduct->getExtensionAttributes()
            ->getConfigurableProductOptions()[0]
            ->getOptions()[0]['value_index'];
        $options = $this->generateSuperAttributesUIDQuery($attributeId, $valueIndex);
        $mutation = $this->getAddConfigurableProductToCartMutation($cartId, $productSku, $desiredQuantity, $options);
        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertArrayHasKey('user_errors', $response['addProductsToCart']);
        $userErrors = $response['addProductsToCart']['user_errors'];
        $this->assertCount(1, $userErrors);
        $this->assertEquals("You cannot add \"$productSku\" to the cart.", $userErrors[0]['message']);
    }

    /**
     * Generates UID for super configurable product super attributes
     *
     * @param int $attributeId
     * @param int $valueIndex
     * @return string
     */
    private function generateSuperAttributesUIDQuery(int $attributeId, int $valueIndex): string
    {
        return 'selected_options: ["' . $this->selectionUidFormatter->encode($attributeId, $valueIndex) . '"]';
    }

    /**
     * @param string $maskedQuoteId
     * @param string $configurableSku
     * @param int $quantity
     * @param string $selectedOptionsQuery
     * @return string
     */
    private function getAddConfigurableProductToCartMutation(
        string $maskedQuoteId,
        string $configurableSku,
        int    $quantity,
        string $selectedOptionsQuery
    ): string {
        return <<<QUERY
mutation {
    addProductsToCart(
        cartId:"{$maskedQuoteId}"
        cartItems: [
            {
                sku: "{$configurableSku}"
                quantity: $quantity
                {$selectedOptionsQuery}
            }
        ]
    ) {
        cart {
            items {
                id
                quantity
                product {
                    sku
                    id
                }
                ... on ConfigurableCartItem {
                    configurable_options {
                        id
                        value_id
                    }
                }
            }
        },
        user_errors {
            message
        }
    }
}
QUERY;
    }

    /**
     * Response should have no cart items
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store btob/website_configuration/company_active 1
     * @magentoConfigFixture default_store btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/multiple_shared_catalogs.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/companies_with_admin.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/categories.php
     */
    public function testProductIsDeniedToCart()
    {
        $this->reindexCatalogPermissions();

        $productSku = 'product_20';
        $desiredQuantity = 5;
        $currentEmail = 'admin@2company.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
            ->execute($currentEmail, $currentPassword);
        $cartId = $this->createEmptyCart($headerAuthorization);

        $response = $this->graphQlMutation(
            $this->prepareMutation($cartId, $productSku, $desiredQuantity),
            [],
            '',
            $headerAuthorization
        );

        $this->removeQuote($cartId);

        $this->assertEmpty($response['addProductsToCart']['cart']['items']);
        $this->assertArrayHasKey('user_errors', $response['addProductsToCart']);
        $userErrors = $response['addProductsToCart']['user_errors'];
        self::assertCount(1, $userErrors);
        self::assertStringContainsString($productSku, $userErrors[0]['message']);
        self::assertEquals('PERMISSION_DENIED', $userErrors[0]['code']);
    }

    /**
     * Prepare add products to cart mutation
     *
     * @param string $cartId
     * @param string $productSku
     * @param int $desiredQuantity
     * @return string
     */
    private function prepareMutation(string $cartId, string $productSku, int $desiredQuantity): string
    {
        $mutation = <<<MUTATION
mutation {
  addProductsToCart(
    cartId: "{$cartId}",
    cartItems: [
      {
          sku: "{$productSku}"
          quantity: {$desiredQuantity}
      }
      ]
  ) {
    cart {
      items {
       quantity
       product {
          sku
        }
      }
    },
    user_errors {
        code,
        message
    }
  }
}
MUTATION;
        return $mutation;
    }

    /**
     * Create empty cart
     *
     * @return string
     * @throws \Exception
     */
    private function createEmptyCart(array $headerAuthorization): string
    {
        $query = <<<QUERY
mutation {
  createEmptyCart
}
QUERY;
        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );
        $cartId = $response['createEmptyCart'];
        return $cartId;
    }

    /**
     * Remove the quote
     *
     * @param string $maskedId
     */
    private function removeQuote(string $maskedId): void
    {
        $maskedIdToQuote = $this->objectManager->get(MaskedQuoteIdToQuoteIdInterface::class);
        $quoteId = $maskedIdToQuote->execute($maskedId);

        $cartRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $quote = $cartRepository->get($quoteId);
        $cartRepository->delete($quote);
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category", $out);
    }
}
