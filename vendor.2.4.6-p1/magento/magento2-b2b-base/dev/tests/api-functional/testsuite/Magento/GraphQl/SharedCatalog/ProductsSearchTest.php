<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\SharedCatalog;

use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;

/**
 * Search products for a specific shared catalog
 */
class ProductsSearchTest extends GraphQlAbstract
{
    /**
     * @var SharedCatalogManagementInterface
     */
    private $sharedCatalogManagement;

    /**
     * @var ProductManagementInterface
     */
    private $productManagement;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->sharedCatalogManagement = $objectManager->get(SharedCatalogManagementInterface::class);
        $this->productManagement = $objectManager->get(ProductManagementInterface::class);
    }

    /**
     * Response needs to have exact items in place with prices available
     *
     * @magentoConfigFixture base_website catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture base_website btob/website_configuration/company_active 1
     * @magentoConfigFixture base_website btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/multiple_shared_catalogs.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/companies_with_admin.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/categories.php
     */
    public function testProductsSearchWithPricesPublicAndAllowedCompany0()
    {
        $this->reindexCatalogPermissions();

        $companyIdentifier = 0;
        $currentEmail = 'admin@' . $companyIdentifier . 'company.com';
        $currentPassword = 'password';

        $response = $this->graphQlQuery(
            $this->getQuery(),
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($currentEmail, $currentPassword)
        );
        //Verify exact number of products are returned
        $this->assertCount(4, $response['products']['items']);
        $this->assertEquals(4, $response['products']['total_count']);
        $items = $response['products']['items'];
        foreach ($items as $item) {
            $id = $item['id'];
            $sku = $item['sku'];
            if ($sku !== 'configurable') {
                //Verify expected sku's pattern
                $splitSku = explode("_", $sku);
                $this->assertEquals($companyIdentifier, (int)substr($splitSku[1], 0, 1));
                //Verify price is available
                $specialPrice = $item['special_price'];
                $this->assertEquals(10, (int)$id - (int)$specialPrice);
            } else {
                $this->assertEquals('configurable', $item['sku']);
                $this->assertEquals(null, $item['special_price']);
                $this->assertEquals(10, $item['price_range']['minimum_price']['final_price']['value']);
                $this->assertEquals('simple_10', $item['variants'][0]['product']['sku']);
            }
        }

        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento cache:flush", $out);

        $response = $this->graphQlMutation(
            $this->getQuery(),
            [],
            '',
            []
        );
        $this->assertCount(0, $response['products']['items']);
        $this->assertEquals(0, $response['products']['total_count']);
    }

    /**
     * Response needs to have to have exact items in place but without prices
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture base_website btob/website_configuration/company_active 1
     * @magentoConfigFixture base_website btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/multiple_shared_catalogs.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/companies_with_admin.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/categories.php
     */
    public function testProductsSearchWithPricesDenied()
    {
        $this->reindexCatalogPermissions();

        $companyIdentifier = 2;
        $currentEmail = 'admin@' . $companyIdentifier . 'company.com';
        $currentPassword = 'password';
        $response = $this->graphQlQuery(
            $this->getQuery(),
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($currentEmail, $currentPassword)
        );

        //Verify exact number of products are returned
        $this->assertCount(4, $response['products']['items']);

        $items = $response['products']['items'];
        foreach ($items as $item) {
            $sku = $item['sku'];
            if ($sku !== 'configurable') {
                //Verify expected sku's pattern
                $splitSku = explode("_", $sku);
                $this->assertEquals($companyIdentifier, (int)substr($splitSku[1], 0, 1));
            } else {
                $this->assertEquals('configurable', $item['sku']);
            }
            //Verify price is not available
            $priceRange = $item['price_range'];
            $this->assertNull($priceRange['minimum_price']['final_price']['value']);
            $this->assertNull($item['special_price']);
        }
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_without_options.php
     * @magentoConfigFixture base_website btob/website_configuration/company_active 1
     * @magentoConfigFixture base_website btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/public_shared_catalog_products.php
     */
    public function testFilterProductsNotAssignedToSharedCatalog()
    {
        $productSku = 'simple';

        $query = <<<QUERY
{
  products(filter: {sku: {eq: "{$productSku}"}}) {
    items{
      id
      sku
    }
    aggregations {
      attribute_code
      label
      options {
        count
        label
        value
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertEmpty($response['products']['items']);
        $this->assertEmpty($response['products']['aggregations']);
    }

    /**
     * Response needs to have exact items in place with prices available
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture base_website btob/website_configuration/company_active 1
     * @magentoConfigFixture base_website btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/multiple_shared_catalogs.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/public_shared_catalog_products.php
     */
    public function testProductsSearchWithPricesPublicCatalog()
    {
        $this->reindexCatalogPermissions();

        $response = $this->graphQlQuery(
            $this->getQuery(),
            [],
            '',
            []
        );
        $this->assertEmpty($response['products']['items']);
        //second time this runs it will get the assigned products from previous step
        //according to MC-42567 total count must be equal actual number of items
        $this->assertEquals(0, $response['products']['total_count']);

        $sharedCatalog = $this->sharedCatalogManagement->getPublicCatalog();
        /** @var ProductManagementInterface $productManagement */
        $this->productManagement->assignProducts($sharedCatalog->getId(), []);

        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento cache:flush", $out);

        $response = $this->graphQlQuery(
            $this->getQuery(),
            [],
            '',
            []
        );

        //Verify no products are returned
        $this->assertCount(0, $response['products']['items']);
        // there is a bug around shared catalog and total_count
        // $this->assertEquals(0, $response['products']['total_count']);
    }

    /**
     * Get products search query
     *
     * @return string
     */
    private function getQuery(): string
    {
        $query = <<<QUERY
{
  products(search: "Product"){
    items {
      id
      name
      sku
      ... on ConfigurableProduct {
        variants {
          product {
            sku
          }
        }
      }
      price_range {
        minimum_price {
          final_price {
            value
          }
        }
      }
      special_price
    }
    total_count
  }
}
QUERY;
        return $query;
    }

    /**
     * Reindex catalog permissions
     */
    private function reindexCatalogPermissions()
    {
        $appDir = dirname(Bootstrap::getInstance()->getAppTempDir());
        $out = '';
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento cache:flush full_page", $out);
        // phpcs:ignore Magento2.Security.InsecureFunction
        exec("php -f {$appDir}/bin/magento indexer:reindex", $out);
    }
}
