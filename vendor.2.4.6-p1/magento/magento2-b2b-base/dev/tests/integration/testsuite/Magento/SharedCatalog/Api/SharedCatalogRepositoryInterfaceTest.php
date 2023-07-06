<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Api;

use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\Authorization\Model\Role;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexProcessor;
use Magento\CatalogPermissions\Model\Indexer\Category\Processor as CategoryPermissionIndexProcessor;
use Magento\CatalogPermissions\Model\Indexer\Product\Processor as ProductPermissionIndexProcessor;
use Magento\CatalogPermissions\Model\Permission  as CatalogPermission;
use Magento\Elasticsearch\Model\ResourceModel\Index;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\AbstractProcessor;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Indexer\Cron\UpdateMview;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\ClassModel;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogRepositoryInterfaceTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SharedCatalogRepositoryInterface
     */
    private $repository;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @var CategoryManagementInterface
     */
    private $categoryManagement;

    /**
     * @var ProductManagementInterface
     */
    private $productManagement;

    /**
     * @var bool
     */
    private $resetIndexersMode = false;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->repository = $this->objectManager->create(SharedCatalogRepositoryInterface::class);
        $this->categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->catalogPermissionManagement = $this->objectManager->create(CatalogPermissionManagement::class);
        $this->categoryManagement = $this->objectManager->create(CategoryManagementInterface::class);
        $this->productManagement = $this->objectManager->create(ProductManagementInterface::class);
    }

    /**
     * @magentoDataFixture Magento/SharedCatalog/_files/catalogs_for_search.php
     */
    public function testGetList()
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = $this->objectManager->create(FilterBuilder::class);

        $filter1 = $filterBuilder->setField(SharedCatalogInterface::NAME)
            ->setValue('catalog 2')
            ->create();
        $filter2 = $filterBuilder->setField(SharedCatalogInterface::NAME)
            ->setValue('catalog 3')
            ->create();
        $filter3 = $filterBuilder->setField(SharedCatalogInterface::NAME)
            ->setValue('catalog 4')
            ->create();
        $filter4 = $filterBuilder->setField(SharedCatalogInterface::NAME)
            ->setValue('catalog 5')
            ->create();
        $filter5 = $filterBuilder->setField(SharedCatalogInterface::CUSTOMER_GROUP_ID)
            ->setValue(1)
            ->create();

        /**@var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = $this->objectManager->create(SortOrderBuilder::class);

        /** @var SortOrder $sortOrder */
        $sortOrder = $sortOrderBuilder->setField(SharedCatalogInterface::DESCRIPTION)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);

        $searchCriteriaBuilder->addFilters([$filter1, $filter2, $filter3, $filter4]);
        $searchCriteriaBuilder->addFilters([$filter5]);
        $searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteriaBuilder->setPageSize(2);
        $searchCriteriaBuilder->setCurrentPage(2);

        $searchCriteria = $searchCriteriaBuilder->create();

        $searchResult = $this->repository->getList($searchCriteria);

        $this->assertEquals(3, $searchResult->getTotalCount());
        $items = array_values($searchResult->getItems());
        $this->assertCount(1, $items);
        $this->assertEquals('catalog 4', $items[0][SharedCatalogInterface::NAME]);
    }

    /**
     * Verify admin with restriction to specific website able to get shared catalog without store id specified.
     *
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/AdminGws/_files/role_websites_login.php
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog_without_store.php
     * @return void
     */
    public function testGetSharedCatalogWithUserRestrictedToSpecificWebsite(): void
    {
        $adminRole = $this->objectManager->get(Role::class);
        $adminRole->load('admingws_role', 'role_name');
        $adminGwsRole = $this->objectManager->get(AdminGwsRole::class);
        $adminGwsRole->setAdminRole($adminRole);
        $sharedCatalogCollection = $this->objectManager->get(Collection::class);
        $sharedCatalogId = $sharedCatalogCollection->getLastItem()->getId();
        $sharedCatalog = $this->repository->get($sharedCatalogId);
        self::assertNotEmpty($sharedCatalog->getId());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture base_website btob/website_configuration/company_active 1
     * @magentoConfigFixture base_website btob/website_configuration/sharedcatalog_active 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoAdminConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento/SharedCatalog/_files/tax_class.php
     * @magentoDataFixture deleteAllCustomSharedCatalogs
     * @dataProvider indexerModeDataProvider
     *
     * @return void
     */
    public function testNewSharedCatalogCustomerGroupShouldHaveDeniedPermissionForAllCategoriesAndProducts(
        bool $isIndexerScheduled
    ): void {

        $this->setIndexerMode(
            CategoryPermissionIndexProcessor::class,
            $isIndexerScheduled
        );

        $this->setIndexerMode(
            ProductPermissionIndexProcessor::class,
            $isIndexerScheduled
        );
        $mainWebsite = $this->objectManager->get(StoreManagerInterface::class)->getWebsite('base');
        $categoryId = 3;
        $productId = $this->productRepository->get('simple')->getId();
        $sharedCatalog = $this->createSharedCatalog();
        self::assertNotNull($sharedCatalog->getCustomerGroupId());
        $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
            $categoryId,
            null,
            $sharedCatalog->getCustomerGroupId()
        );
        self::assertEquals(
            CatalogPermission::PERMISSION_DENY,
            $sharedCatalogPermission->getPermission()
        );
        $this->queueConsumerStart('sharedCatalogUpdateCategoryPermissions');
        $this->queueConsumerStart('sharedCatalogUpdatePrice');
        $this->reindexAllInvalid();
        if ($isIndexerScheduled) {
            $this->updateMview();
        }
        /**
         * @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index $catalogPermissionResource
         */
        $catalogPermissionResource = $this->objectManager->get(
            \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index::class
        );
        $catalogPermission = $catalogPermissionResource->getIndexForCategory(
            $categoryId,
            $sharedCatalog->getCustomerGroupId(),
            $mainWebsite->getId()
        );
        $expectedCatalogPermission = [
            'category_id' => $categoryId,
            'website_id' => $mainWebsite->getId(),
            'customer_group_id' => $sharedCatalog->getCustomerGroupId(),
            'grant_catalog_category_view' => CatalogPermission::PERMISSION_DENY,
            'grant_catalog_product_price' => CatalogPermission::PERMISSION_DENY,
            'grant_checkout_items' => CatalogPermission::PERMISSION_DENY,
        ];
        self::assertIsArray($catalogPermission);
        self::assertArrayHasKey($categoryId, $catalogPermission);
        self::assertEquals(
            $expectedCatalogPermission,
            $catalogPermission[$categoryId]
        );

        $catalogPermission = $catalogPermissionResource->getIndexForProduct(
            $productId,
            $sharedCatalog->getCustomerGroupId(),
            $mainWebsite->getDefaultStore()->getId()
        );
        $expectedCatalogPermission = [
            'product_id' => $productId,
            'store_id' => $mainWebsite->getDefaultStore()->getId(),
            'customer_group_id' => $sharedCatalog->getCustomerGroupId(),
            'grant_catalog_category_view' => CatalogPermission::PERMISSION_DENY,
            'grant_catalog_product_price' => CatalogPermission::PERMISSION_DENY,
            'grant_checkout_items' => CatalogPermission::PERMISSION_DENY,
            'index_id' => '7'
        ];
        self::assertIsArray($catalogPermission);
        self::assertArrayHasKey($productId, $catalogPermission);
        self::assertEquals(
            $expectedCatalogPermission,
            $catalogPermission[$productId]
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture base_website btob/website_configuration/company_active 1
     * @magentoConfigFixture base_website btob/website_configuration/sharedcatalog_active 1
     * @magentoAdminConfigFixture catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoConfigFixture base_website catalog/magento_catalogpermissions/grant_catalog_category_view 1
     * @magentoConfigFixture base_website catalog/magento_catalogpermissions/grant_catalog_product_price 1
     * @magentoConfigFixture base_website catalog/magento_catalogpermissions/grant_checkout_items 1
     * @magentoDataFixture Magento/SharedCatalog/_files/tax_class.php
     * @magentoDataFixture deleteAllCustomSharedCatalogs
     * @dataProvider indexerModeDataProvider
     *
     * @return void
     */
    public function testNewSharedCatalogCustomerGroupShouldHavePermissionForAssignedCategoriesAndProducts(
        bool $isIndexerScheduled
    ): void {
        $this->setIndexerMode(
            CategoryPermissionIndexProcessor::class,
            $isIndexerScheduled
        );
        $this->setIndexerMode(
            ProductPermissionIndexProcessor::class,
            $isIndexerScheduled
        );
        $this->setIndexerMode(
            PriceIndexProcessor::class,
            $isIndexerScheduled
        );

        $mainWebsite = $this->objectManager->get(StoreManagerInterface::class)->getWebsite('base');
        $categoryId = 3;
        $product = $this->productRepository->get('simple');
        $productId = $product->getId();
        $category = $this->categoryRepository->get($categoryId);
        $sharedCatalog = $this->createSharedCatalog();
        self::assertNotNull($sharedCatalog->getCustomerGroupId());

        $this->categoryManagement->assignCategories($sharedCatalog->getId(), [$category]);
        $this->productManagement->assignProducts($sharedCatalog->getId(), [$product]);
        $this->queueConsumerStart('sharedCatalogUpdateCategoryPermissions');
        $this->queueConsumerStart('sharedCatalogUpdatePrice');
        $this->reindexAllInvalid();
        if ($isIndexerScheduled) {
            $this->updateMview();
        }

        /** @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index $catalogPermissionResource */
        $catalogPermissionResource = $this->objectManager->get(
            \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index::class
        );
        $catalogPermission = $catalogPermissionResource->getIndexForCategory(
            $categoryId,
            $sharedCatalog->getCustomerGroupId(),
            $mainWebsite->getId()
        );
        $expectedCatalogPermission = [
            'category_id' => $categoryId,
            'website_id' => $mainWebsite->getId(),
            'customer_group_id' => $sharedCatalog->getCustomerGroupId(),
            'grant_catalog_category_view' => CatalogPermission::PERMISSION_ALLOW,
            'grant_catalog_product_price' => CatalogPermission::PERMISSION_ALLOW,
            'grant_checkout_items' => CatalogPermission::PERMISSION_ALLOW,
        ];
        self::assertIsArray($catalogPermission);
        self::assertArrayHasKey($categoryId, $catalogPermission);
        self::assertEquals(
            $expectedCatalogPermission,
            $catalogPermission[$categoryId]
        );

        $catalogPermission = $catalogPermissionResource->getIndexForProduct(
            $productId,
            $sharedCatalog->getCustomerGroupId(),
            $mainWebsite->getDefaultStore()->getId()
        );
        $expectedCatalogPermission = [
            'product_id' => $productId,
            'store_id' => $mainWebsite->getDefaultStore()->getId(),
            'customer_group_id' => $sharedCatalog->getCustomerGroupId(),
            'grant_catalog_category_view' => CatalogPermission::PERMISSION_ALLOW,
            'grant_catalog_product_price' => CatalogPermission::PERMISSION_ALLOW,
            'grant_checkout_items' => CatalogPermission::PERMISSION_ALLOW,
            'index_id' => '7'
        ];
        self::assertIsArray($catalogPermission);
        self::assertArrayHasKey($productId, $catalogPermission);
        self::assertEquals(
            $expectedCatalogPermission,
            $catalogPermission[$productId]
        );
        /** @var Index $priceIndexResource */
        $priceIndexResource = $this->objectManager->get(Index::class);
        $priceIndexData = $priceIndexResource->getPriceIndexData(
            [$productId],
            $mainWebsite->getDefaultStore()->getId()
        );
        self::assertIsArray($priceIndexData);
        self::assertArrayHasKey($productId, $priceIndexData);
        self::assertArrayHasKey($sharedCatalog->getCustomerGroupId(), $priceIndexData[$productId]);
    }

    /**
     * @param string $consumerName
     * @param int $maxNumberOfMessages
     * @throws LocalizedException
     */
    private function queueConsumerStart(string $consumerName, int $maxNumberOfMessages = 1000): void
    {
        /** @var ConsumerFactory $consumerFactory */
        $consumerFactory = $this->objectManager->get(ConsumerFactory::class);
        $categoryPermissionsUpdater = $consumerFactory->get($consumerName);
        $categoryPermissionsUpdater->process($maxNumberOfMessages);
    }

    /**
     * @return SharedCatalog
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    private function createSharedCatalog(): SharedCatalog
    {
        /** @var \Magento\Tax\Model\ResourceModel\TaxClass\Collection $taxClassCollection */
        $taxClassCollection = $this->objectManager->create(\Magento\Tax\Model\ResourceModel\TaxClass\Collection::class);
        /** @var ClassModel $taxClass */
        $taxClass = $taxClassCollection->getLastItem();
        $taxClassId = $taxClass->getId();
        /** @var $sharedCatalog SharedCatalog */
        $sharedCatalog = $this->objectManager->get(SharedCatalogFactory::class)
            ->create();
        $sharedCatalog->setName('shared catalog ' . time());
        $sharedCatalog->setDescription('shared catalog description');
        $sharedCatalog->setType(0);
        $sharedCatalog->setCreatedBy(null);
        $sharedCatalog->setTaxClassId($taxClassId);
        $sharedCatalog->setStoreId(0);
        $this->repository->save($sharedCatalog);
        return $sharedCatalog;
    }

    private function setIndexerMode(string $processorClassName, bool $isScheduled)
    {
        /** @var AbstractProcessor $processor */
        $processor = $this->objectManager->get($processorClassName);
        if ($isScheduled !== $processor->getIndexer()->isScheduled()) {
            $processor->getIndexer()->setScheduled($isScheduled);
            $this->resetIndexersMode = true;
        }
    }

    private function reindexAllInvalid(): void
    {
        $this->objectManager->create(\Magento\Indexer\Model\Processor::class)->reindexAllInvalid();
    }

    private function updateMview(): void
    {
        $this->objectManager->create(UpdateMview::class)->execute();
    }

    public static function deleteAllCustomSharedCatalogs(): void
    {
        /** @var SharedCatalog $sharedCatalog */
        $sharedCatalogCollection = Bootstrap::getObjectManager()
            ->create(Collection::class);
        $sharedCatalogRepository =  Bootstrap::getObjectManager()->create(SharedCatalogRepositoryInterface::class);
        foreach ($sharedCatalogCollection as $sharedCatalog) {
            if ($sharedCatalog->getId() != 1 && $sharedCatalog->getType() != 1) {
                $sharedCatalogRepository->delete($sharedCatalog);
            }
        }
    }

    public static function deleteAllCustomSharedCatalogsRollback(): void
    {
        static::deleteAllCustomSharedCatalogs();
    }

    /**
     * @return array
     */
    public function indexerModeDataProvider(): array
    {
        return [
            ['isIndexerScheduled' => true],
            ['isIndexerScheduled' => false]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->resetIndexersMode) {
            $this->setIndexerMode(
                CategoryPermissionIndexProcessor::class,
                false
            );

            $this->setIndexerMode(
                ProductPermissionIndexProcessor::class,
                false
            );

            $this->setIndexerMode(
                PriceIndexProcessor::class,
                false
            );
        }

        $this->resetIndexersMode = false;
    }
}
