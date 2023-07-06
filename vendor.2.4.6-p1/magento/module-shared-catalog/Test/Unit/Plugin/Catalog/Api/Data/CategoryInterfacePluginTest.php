<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Catalog\Api\Data;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\Data\SearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\SharedCatalog\Model\SharedCatalogAssignment;
use Magento\SharedCatalog\Plugin\Catalog\Api\Data\CategoryInterfacePlugin;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for plugin Catalog\Api\Data\CategoryInterfacePlugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryInterfacePluginTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CategoryInterfacePlugin
     */
    private $categoryInterfacePlugin;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CustomerGroupManagement|MockObject
     */
    private $sharedCatalogCustomerGroupManagement;

    /**
     * @var Index|MockObject
     */
    private $permissionIndex;

    /**
     * @var SharedCatalogAssignment|MockObject
     */
    private $sharedCatalogAssignment;

    /**
     * @var CategoryManagementInterface|MockObject
     */
    private $sharedCatalogCategoryManagement;

    /**
     * @var CategoryInterface|MockObject
     */
    private $catalogCategory;

    /**
     * @var int
     */
    private $permissionsCustomerGroupId = 345;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogCustomerGroupManagement = $this
            ->getMockBuilder(CustomerGroupManagement::class)
            ->setMethods(['getSharedCatalogGroupIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionIndex = $this->getMockBuilder(Index::class)
            ->setMethods(['getIndexForCategory'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogAssignment = $this
            ->getMockBuilder(SharedCatalogAssignment::class)
            ->setMethods(['assignProductsForCategories', 'unassignProductsForCategories'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogCategoryManagement = $this
            ->getMockBuilder(CategoryManagementInterface::class)
            ->setMethods(['getCategories'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->catalogCategory = $this->getMockBuilder(CategoryInterface::class)
            ->setMethods(['getData', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->categoryInterfacePlugin = $this->objectManagerHelper->getObject(
            CategoryInterfacePlugin::class,
            [
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'storeManager' => $this->storeManager,
                'sharedCatalgoCustomerGroupManagement' => $this->sharedCatalogCustomerGroupManagement,
                'permissionIndex' => $this->permissionIndex,
                'sharedCatalogAssignment' => $this->sharedCatalogAssignment,
                'sharedCatalogCategoryManagement' => $this->sharedCatalogCategoryManagement,
                'sharedCatalogCustomerGroupIds' => [$this->permissionsCustomerGroupId]
            ]
        );
    }

    /**
     * Prepare PermissionIndex mock.
     *
     * @param int $websiteId
     * @param string $permissionValue
     * @return void
     */
    private function preparePermissionIndex($websiteId, $permissionValue)
    {
        $categoryPermissionData = [
            'customer_group_id' => $this->permissionsCustomerGroupId,
            'website_id' => $websiteId, 'grant_catalog_category_view' => $permissionValue
        ];
        $categoriesPermissionData = [$categoryPermissionData];
        $this->permissionIndex->expects($this->atLeastOnce())->method('getIndexForCategory')
            ->willReturn($categoriesPermissionData);
    }

    /**
     * Test for beforeSave().
     *
     * @return void
     */
    public function testBeforeSave()
    {
        $permissionsCustomerGroupId = $this->permissionsCustomerGroupId;
        $permission = ['customer_group_id' => $permissionsCustomerGroupId];
        $permissions = [$permission];
        $this->catalogCategory->expects($this->atLeastOnce())->method('getData')->with('permissions')
            ->willReturn($permissions);
        $categoryId = 45;
        $this->catalogCategory->expects($this->atLeastOnce())->method('getId')->willReturn($categoryId);

        $sharedCatalogCustomerGroupIds = [$permissionsCustomerGroupId];
        $this->sharedCatalogCustomerGroupManagement->expects($this->atLeastOnce())->method('getSharedCatalogGroupIds')
            ->willReturn($sharedCatalogCustomerGroupIds);

        $websiteId = 4;
        $permissionValue = '-1';
        $this->preparePermissionIndex($websiteId, $permissionValue);

        $this->categoryInterfacePlugin->beforeSave($this->catalogCategory);
    }

    /**
     * Test for afterSave().
     *
     * @param string $permissionValue
     * @param array $returned
     * @param array $calls
     * @return void
     * @dataProvider afterSaveDataProvider
     */
    public function testAfterSave($permissionValue, array $returned, array $calls)
    {
        $websiteId = 4;

        $categoryId = 45;
        $this->catalogCategory->expects($this->atLeastOnce())->method('getId')->willReturn($categoryId);

        $this->preparePermissionIndex($websiteId, $permissionValue);

        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);

        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getCustomerGroupId', 'getStoreId', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerGroupId = $this->permissionsCustomerGroupId;
        $sharedCatalog->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturnOnConsecutiveCalls($customerGroupId, $customerGroupId, 999);
        $sharedCatalog->expects($this->atLeastOnce())->method('getStoreId')
            ->willReturn($returned['sharedCatalog_getStoreId']);
        $sharedCatalogId = 32;
        $sharedCatalog->expects($this->atLeastOnce())->method('getId')->willReturn($sharedCatalogId);

        $sharedCatalogSearchResults = $this
            ->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogs = [$sharedCatalog, $sharedCatalog];
        $sharedCatalogSearchResults->expects($this->atLeastOnce())->method('getItems')->willReturn($sharedCatalogs);
        $this->sharedCatalogRepository->expects($this->atLeastOnce())->method('getList')
            ->willReturn($sharedCatalogSearchResults);

        $store = $this->getMockBuilder(GroupInterface::class)
            ->setMethods(['getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($calls['store_getWebsiteId'])->method('getWebsiteId')->willReturn($websiteId);

        $this->storeManager->expects($calls['getStore_storeManager'])->method('getGroup')->willReturn($store);

        $categoryIds = [$categoryId];
        $this->sharedCatalogCategoryManagement->expects($calls['sharedCatalogCategoryManagement_getCategories'])
            ->method('getCategories')->willReturn($categoryIds);

        $this->sharedCatalogAssignment->expects($calls['sharedCatalogAssignment_assignProductsForCategories'])
            ->method('assignProductsForCategories');
        $this->sharedCatalogAssignment->expects($calls['sharedCatalogAssignment_unassignProductsForCategories'])
            ->method('unassignProductsForCategories');

        $this->categoryInterfacePlugin->afterSave($this->catalogCategory, $this->catalogCategory);
    }

    /**
     * Data provider for afterSave() test.
     *
     * @return array
     */
    public function afterSaveDataProvider()
    {
        return [
            [
                '-1', ['sharedCatalog_getStoreId' => 0],
                [
                    'store_getWebsiteId' => $this->never(), 'getStore_storeManager' => $this->never(),
                    'sharedCatalogCategoryManagement_getCategories' => $this->never(),
                    'sharedCatalogAssignment_assignProductsForCategories' => $this->atLeastOnce(),
                    'sharedCatalogAssignment_unassignProductsForCategories' => $this->never()
                ]
            ],
            [
                '-1', ['sharedCatalog_getStoreId' => 3],
                [
                    'store_getWebsiteId' => $this->atLeastOnce(), 'getStore_storeManager' => $this->atLeastOnce(),
                    'sharedCatalogCategoryManagement_getCategories' => $this->never(),
                    'sharedCatalogAssignment_assignProductsForCategories' => $this->atLeastOnce(),
                    'sharedCatalogAssignment_unassignProductsForCategories' => $this->never()
                ]
            ],
            [
                '0', ['sharedCatalog_getStoreId' => 3],
                [
                    'store_getWebsiteId' => $this->atLeastOnce(), 'getStore_storeManager' => $this->atLeastOnce(),
                    'sharedCatalogCategoryManagement_getCategories' => $this->atLeastOnce(),
                    'sharedCatalogAssignment_assignProductsForCategories' => $this->never(),
                    'sharedCatalogAssignment_unassignProductsForCategories' => $this->atLeastOnce()
                ]
            ],
        ];
    }

    /**
     * Test for afterSave method.
     *
     * @return void
     */
    public function testAfterSaveWithEmptyCustomerGroups()
    {
        $this->categoryInterfacePlugin = $this->objectManagerHelper->getObject(
            CategoryInterfacePlugin::class,
            [
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'storeManager' => $this->storeManager,
                'sharedCatalgoCustomerGroupManagement' => $this->sharedCatalogCustomerGroupManagement,
                'permissionIndex' => $this->permissionIndex,
                'sharedCatalogAssignment' => $this->sharedCatalogAssignment,
                'sharedCatalogCategoryManagement' => $this->sharedCatalogCategoryManagement,
                'sharedCatalogCustomerGroupIds' => []
            ]
        );
        $this->catalogCategory->expects($this->never())->method('getId');
        $this->storeManager->expects($this->never())->method('getStore');
        $this->sharedCatalogCategoryManagement->expects($this->never())->method('getCategories');

        $this->categoryInterfacePlugin->afterSave($this->catalogCategory, $this->catalogCategory);
    }
}
