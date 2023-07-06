<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Configure;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\Configure\Category;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for model Configure\Category.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryTest extends TestCase
{
    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var ProductManagementInterface|MockObject
     */
    private $productSharedCatalogManagement;

    /**
     * @var CatalogPermissionManagement|MockObject
     */
    private $catalogPermissionManagement;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Category
     */
    private $category;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productSharedCatalogManagement = $this
            ->getMockBuilder(ProductManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogPermissionManagement = $this->getMockBuilder(
            CatalogPermissionManagement::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->category = $this->objectManagerHelper->getObject(
            Category::class,
            [
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'productSharedCatalogManagement' => $this->productSharedCatalogManagement,
                'catalogPermissionManagement' => $this->catalogPermissionManagement,
            ]
        );
    }

    /**
     * Test for saveConfiguredCategories().
     *
     * @return void
     */
    public function testSaveConfiguredCategories()
    {
        $sharedCatalogId = 34;
        $storeId = 3;
        $customerGroupId = 5;
        $productSkus = ['sku_1', 'sku_2', 'sku_3'];
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $currentStorage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepository->expects($this->once())->method('get')->willReturn($sharedCatalog);
        $currentStorage->expects($this->once())->method('getAssignedProductSkus')->willReturn($productSkus);
        $currentStorage->expects($this->once())->method('getAssignedCategoriesIds')->willReturn([7, 9]);
        $currentStorage->expects($this->once())->method('getUnassignedCategoriesIds')->willReturn([12, 13]);
        $sharedCatalog->expects($this->once())->method('getStoreId')->willReturn(null);
        $sharedCatalog->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();
        $this->sharedCatalogRepository->expects($this->once())->method('save')->with($sharedCatalog)->willReturn(1);
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalog->expects($this->once())
            ->method('getType')
            ->willReturn(SharedCatalogInterface::TYPE_PUBLIC);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('setDenyPermissions')
            ->with([12, 13], [1 => 0, 0 => 5]);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('setAllowPermissions')
            ->with([7, 9], [1 => 0, 0 => 5]);
        $this->productSharedCatalogManagement->expects($this->once())
            ->method('reassignProducts')
            ->with($sharedCatalog, $productSkus)
            ->willReturnSelf();

        $this->assertEquals(
            $sharedCatalog,
            $this->category->saveConfiguredCategories($currentStorage, $sharedCatalogId, $storeId)
        );
    }
}
