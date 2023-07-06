<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Customer\Api;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Plugin\Customer\Api\ReassignCompaniesToDefaultGroup;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ReassignCompaniesToDefaultGroup plugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReassignCompaniesToDefaultGroupTest extends TestCase
{
    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var GroupManagementInterface|MockObject
     */
    private $groupManagement;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var ReassignCompaniesToDefaultGroup
     */
    private $groupRepositoryPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );
        $this->searchCriteriaBuilder = $this->createMock(
            SearchCriteriaBuilder::class
        );
        $this->groupManagement = $this->createMock(
            GroupManagementInterface::class
        );
        $this->companyManagement = $this->createMock(
            CompanyManagementInterface::class
        );

        $objectManager = new ObjectManager($this);
        $this->groupRepositoryPlugin = $objectManager->getObject(
            ReassignCompaniesToDefaultGroup::class,
            [
                'companyRepository' => $this->companyRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'groupManagement' => $this->groupManagement,
                'companyManagement' => $this->companyManagement,
            ]
        );
    }

    /**
     * Test aroundDeleteByUd method.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testAroundDeleteById()
    {
        $companyId = 1;
        $storeId = 2;
        $customerGroupId = 10;
        $defaultGroupId = 11;
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $company->expects($this->once())->method('getId')->willReturn($companyId);
        $companyAdmin = $this->getMockForAbstractClass(CustomerInterface::class);
        $companyAdmin->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $defaultGroup = $this->getMockForAbstractClass(GroupInterface::class);
        $defaultGroup->expects($this->once())->method('getId')->willReturn($defaultGroupId);
        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')
            ->with(CompanyInterface::CUSTOMER_GROUP_ID, $customerGroupId)->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->companyRepository->expects($this->once())->method('getList')
            ->with($searchCriteria)->willReturn($searchResults);
        $searchResults->expects($this->once())->method('getItems')->willReturn(new \ArrayIterator([$company]));
        $this->companyManagement->expects($this->once())
            ->method('getAdminByCompanyId')->with($companyId)->willReturn($companyAdmin);
        $this->groupManagement->expects($this->once())
            ->method('getDefaultGroup')->with($storeId)->willReturn($defaultGroup);
        $company->expects($this->once())->method('setCustomerGroupId')->with($defaultGroupId)->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)->willReturn($company);
        $groupRepository = $this->getMockForAbstractClass(GroupRepositoryInterface::class);
        $this->assertTrue(
            $this->groupRepositoryPlugin->aroundDeleteById(
                $groupRepository,
                function ($groupId) {
                    return true;
                },
                $customerGroupId
            )
        );
    }
}
