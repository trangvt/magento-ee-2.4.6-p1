<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\SharedCatalog;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\TestFramework\ObjectManager;

/**
 * Filter category list test
 */
class CategoryListTest extends GraphQlAbstract
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
     * Set Up
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
    }

    /**
     * Response needs to have exact count and category by name
     *
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture default_store btob/website_configuration/company_active 1
     * @magentoConfigFixture default_store btob/website_configuration/sharedcatalog_active 1
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/multiple_shared_catalogs.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/companies_with_admin.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/permissions/categories.php
     */
    public function testCategoriesReturnedForCompany()
    {
        $this->reindexCatalogPermissions();

        $currentEmail = 'admin@0company.com';
        $currentPassword = 'password';
        $response = $this->graphQlQuery(
            $this->getQuery(),
            [],
            '',
            $this->objectManager->get(GetCustomerAuthenticationHeader::class)->execute($currentEmail, $currentPassword)
        );

        $this->assertCount(1, $response['categoryList']);
        $this->assertEquals("Catalog for company 0", $response['categoryList'][0]['name']);
    }

    /**
     * Get category list query
     *
     * @return string
     */
    private function getQuery(): string
    {
        $query = <<<QUERY
{
  categoryList(filters: {name: {match: "Catalog for company"}}){
    id
    name
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
        exec("php -f {$appDir}/bin/magento indexer:reindex catalogpermissions_category", $out);
    }
}
