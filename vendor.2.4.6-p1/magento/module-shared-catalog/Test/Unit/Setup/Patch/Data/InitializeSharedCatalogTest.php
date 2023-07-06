<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use PHPUnit\Framework\TestCase;
use Magento\SharedCatalog\Setup\Patch\Data\InitializeSharedCatalog;
use Magento\SharedCatalog\Model\Repository as SharedCatalogRepository;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\User\Api\Data\UserInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Tax\Api\Data\TaxClassSearchResultsInterface;
use Magento\Tax\Api\Data\TaxClassInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class for test InitializeSharedCatalog setup patch
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InitializeSharedCatalogTest extends TestCase
{
    /** @var InitializeSharedCatalog */
    private $initializeSharedCatalog;

    /** @var GroupInterface|MockObject */
    private $customerDefaultGroup;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $catalogFactoryMock = $this->createMock(SharedCatalogFactory::class);
        $sharedCatalogRepositoryMock = $this->createMock(SharedCatalogRepository::class);
        $groupManagementMock = $this->createMock(GroupManagementInterface::class);
        $groupRepositoryMock = $this->createMock(GroupRepositoryInterface::class);
        $taxClassRepositoryMock = $this->getTaxClassRepositoryMock();
        $searchCriteriaBuilderMock = $this->getSearchCriteriaBuilderMock();
        $userCollectionFactoryMock = $this->getUserCollectionFactoryMock();
        $moduleDataSetupMock = $this->createMock(ModuleDataSetupInterface::class);
        $this->customerDefaultGroup = $this->getCustomerDefaultGroupMock();
        $sharedCatalogInterfaceMock = $this->getSharedCatalogInterfaceMock();
        $groupManagementMock->method('getDefaultGroup')->willReturn($this->customerDefaultGroup);
        $catalogFactoryMock->method('create')->willReturn($sharedCatalogInterfaceMock);
        $groupRepositoryMock->method('getById')->willReturn($this->customerDefaultGroup);
        $this->initializeSharedCatalog = new InitializeSharedCatalog(
            $catalogFactoryMock,
            $sharedCatalogRepositoryMock,
            $groupManagementMock,
            $groupRepositoryMock,
            $taxClassRepositoryMock,
            $searchCriteriaBuilderMock,
            $userCollectionFactoryMock,
            $moduleDataSetupMock
        );
    }

    /**
     * Test for apply setup patch
     *
     * @return void
     */
    public function testApply(): void
    {
        $this->customerDefaultGroup->expects($this->once())->method('setCode')->with('New Group');
        $this->initializeSharedCatalog->apply();
    }

    /**
     * Get user collection factory
     *
     * @return UserCollectionFactory|MockObject
     */
    private function getUserCollectionFactoryMock()
    {
        $userCollectionFactoryMock = $this->createMock(UserCollectionFactory::class);
        $userCollection = $this->createMock(UserCollection::class);
        $userCollection->method('setPageSize')->willReturnSelf();
        $userCollectionFactoryMock->method('create')->willReturn($userCollection);
        $userInterfaceMock = $this->createMock(UserInterface::class);
        $userCollection->method('getFirstItem')->willReturn($userInterfaceMock);

        return $userCollectionFactoryMock;
    }

    /**
     * Get tax class repository
     *
     * @return TaxClassRepositoryInterface|MockObject
     */
    private function getTaxClassRepositoryMock()
    {
        $taxClassRepositoryMock = $this->createMock(TaxClassRepositoryInterface::class);
        $taxClassSearchResultsInterfaceMock = $this->createMock(TaxClassSearchResultsInterface::class);
        $taxInterfaceMock = $this->createMock(TaxClassInterface::class);
        $taxInterfaceMock->method('getClassId')->willReturn(3);
        $taxClassSearchResultsInterfaceMock->method('getItems')->willReturn([$taxInterfaceMock]);
        $taxClassRepositoryMock->method('getList')->willReturn($taxClassSearchResultsInterfaceMock);

        return $taxClassRepositoryMock;
    }

    /**
     * Get search criteria builder
     *
     * @return SearchCriteriaBuilder|MockObject
     */
    private function getSearchCriteriaBuilderMock()
    {
        $searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchCriteriaBuilderMock->method('create')->willReturn($searchCriteriaMock);

        return $searchCriteriaBuilderMock;
    }

    /**
     * Get customer group
     *
     * @return GroupInterface|MockObject
     */
    private function getCustomerDefaultGroupMock()
    {
        $customerDefaultGroup = $this->createMock(GroupInterface::class);
        $customerDefaultGroup->method('getId')->willReturn(1);
        $customerDefaultGroup->method('getCode')->willReturn('New Group');

        return $customerDefaultGroup;
    }

    /**
     * Get shared catalog
     *
     * @return SharedCatalogInterface|MockObject
     */
    private function getSharedCatalogInterfaceMock()
    {
        $sharedCatalogInterfaceMock = $this->createMock(SharedCatalogInterface::class);
        $methods = [
            'setName',
            'setDescription',
            'setCreatedBy',
            'setType',
            'setCustomerGroupId',
            'setTaxClassId'
        ];
        foreach ($methods as $method) {
            $sharedCatalogInterfaceMock->method($method)->willReturnSelf();
        }

        return $sharedCatalogInterfaceMock;
    }
}
