<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\Data\TierPriceInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogDuplicationInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\Duplicator;
use Magento\SharedCatalog\Model\Price\DuplicatorTierPriceLoader;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for DuplicateHandler.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DuplicatorTest extends TestCase
{
    /**
     * @var CategoryManagementInterface|MockObject
     */
    private $categoryManagement;

    /**
     * @var ProductManagementInterface|MockObject
     */
    private $productManagement;

    /**
     * @var CatalogPermissionManagement|MockObject
     */
    private $catalogPermissionManagement;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var ScheduleBulk|MockObject
     */
    private $scheduleBulk;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextInterface;

    /**
     * @var DuplicatorTierPriceLoader|MockObject
     */
    private $tierPriceLoader;

    /**
     * @var Duplicator
     */
    private $duplicateManager;

    /**
     * @var SharedCatalogDuplicationInterface|MockObject
     */
    private $sharedCatalogDuplication;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->categoryManagement = $this->getMockBuilder(CategoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productManagement = $this->getMockBuilder(ProductManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogPermissionManagement = $this
            ->getMockBuilder(CatalogPermissionManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scheduleBulk = $this
            ->getMockBuilder(ScheduleBulk::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContextInterface = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPriceLoader = $this
            ->getMockBuilder(DuplicatorTierPriceLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogDuplication = $this
            ->getMockForAbstractClass(SharedCatalogDuplicationInterface::class);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->duplicateManager = $objectManagerHelper->getObject(
            Duplicator::class,
            [
                'categoryManagement' => $this->categoryManagement,
                'productManagement' => $this->productManagement,
                'catalogPermissionManagement' => $this->catalogPermissionManagement,
                'productRepository' => $this->productRepository,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'scheduleBulk' => $this->scheduleBulk,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'userContextInterface' => $this->userContextInterface,
                'tierPriceLoader' => $this->tierPriceLoader,
                'sharedCatalogDuplication' => $this->sharedCatalogDuplication
            ]
        );
    }

    /**
     * Unit test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $idOriginal = 1;
        $idDuplicated = 2;
        $oldStoreId = 3;
        $categoryIds = [4];
        $oldCatalogCustomerGroupId = 5;
        $newCatalogCustomerGroupId = 6;
        $productSkus = ['sku'];
        $oldCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $oldCatalog->expects($this->atLeastOnce())->method('getStoreId')->willReturn($oldStoreId);
        $oldCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')
            ->willReturn($oldCatalogCustomerGroupId);
        $newCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $newCatalog->expects($this->atLeastOnce())->method('setStoreId')->with($oldStoreId)->willReturnSelf();
        $newCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')
            ->willReturn($newCatalogCustomerGroupId);
        $this->sharedCatalogRepository->expects($this->atLeastOnce())->method('get')
            ->willReturnOnConsecutiveCalls($oldCatalog, $newCatalog);
        $this->sharedCatalogRepository->expects($this->atLeastOnce())->method('save')->with($newCatalog);
        $this->categoryManagement->expects($this->atLeastOnce())->method('getCategories')->with($idOriginal)
            ->willReturn($categoryIds);
        $this->catalogPermissionManagement->expects($this->atLeastOnce())->method('setAllowPermissions')
            ->with($categoryIds, [$newCatalogCustomerGroupId]);
        $this->productManagement->expects($this->atLeastOnce())->method('getProducts')->with($idOriginal)
            ->willReturn($productSkus);
        $tierPrice = $this->getMockBuilder(TierPriceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPriceLoader->expects($this->atLeastOnce())->method('load')->willReturn([$tierPrice]);
        $this->sharedCatalogDuplication->expects($this->atLeastOnce())->method('assignProductsToDuplicate')
            ->with($idDuplicated, $productSkus)->willReturnSelf();
        $this->userContextInterface->expects($this->atLeastOnce())->method('getUserId')->willReturn(1);
        $this->scheduleBulk->expects($this->atLeastOnce())->method('execute');

        $this->duplicateManager->duplicateCatalog($idOriginal, $idDuplicated);
    }
}
