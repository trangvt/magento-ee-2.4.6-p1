<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Customer\Api;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\Config;
use Magento\SharedCatalog\Plugin\Customer\Api\UpdateCompanyCustomerGroupPlugin;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for GroupRepositoryInterfacePlugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateCompanyCustomerGroupPluginTest extends TestCase
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
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $catalogManagement;

    /**
     * @var Config|MockObject
     */
    private $moduleConfig;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var UpdateCompanyCustomerGroupPlugin
     */
    private $updateCompanyCustomerGroupPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupManagement = $this->getMockBuilder(GroupManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogManagement = $this
            ->getMockBuilder(SharedCatalogManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->moduleConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->updateCompanyCustomerGroupPlugin = $objectManager->getObject(
            UpdateCompanyCustomerGroupPlugin::class,
            [
                'companyRepository' => $this->companyRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'groupManagement' => $this->groupManagement,
                'companyManagement' => $this->companyManagement,
                'catalogManagement' => $this->catalogManagement,
                'storeManager' => $this->storeManager,
                'moduleConfig' => $this->moduleConfig
            ]
        );
    }

    /**
     * Test aroundDeleteById method.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testAfterDeleteById()
    {
        $customerGroupId = 10;
        $publicGroupId = 11;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')
            ->with(CompanyInterface::CUSTOMER_GROUP_ID, $customerGroupId)->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);
        $this->companyRepository->expects($this->atLeastOnce())->method('getList')
            ->with($searchCriteria)->willReturn($searchResults);
        $searchResults->expects($this->atLeastOnce())->method('getItems')->willReturn(new \ArrayIterator([$company]));
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $publicCatalog = $this
            ->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $publicCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($publicGroupId);
        $this->catalogManagement->expects($this->atLeastOnce())->method('getPublicCatalog')->willReturn($publicCatalog);
        $company->expects($this->atLeastOnce())->method('setCustomerGroupId')->with($publicGroupId)->willReturnSelf();
        $this->companyRepository->expects($this->atLeastOnce())->method('save')->with($company)->willReturn($company);
        $this->companyManagement->expects($this->never())->method('getAdminByCompanyId');
        $this->groupManagement->expects($this->never())->method('getDefaultGroup');
        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertTrue(
            $this->updateCompanyCustomerGroupPlugin->afterDeleteById(
                $groupRepository,
                true,
                $customerGroupId
            )
        );
    }

    /**
     * Test aroundDeleteById method with exception.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testAfterDeleteByIdWithException()
    {
        $companyId = 1;
        $storeId = 2;
        $customerGroupId = 10;
        $defaultGroupId = 11;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->atLeastOnce())->method('getId')->willReturn($companyId);
        $companyAdmin = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyAdmin->expects($this->atLeastOnce())->method('getStoreId')->willReturn($storeId);
        $defaultGroup = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $defaultGroup->expects($this->atLeastOnce())->method('getId')->willReturn($defaultGroupId);
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')
            ->with(CompanyInterface::CUSTOMER_GROUP_ID, $customerGroupId)->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);
        $this->companyRepository->expects($this->atLeastOnce())->method('getList')
            ->with($searchCriteria)->willReturn($searchResults);
        $searchResults->expects($this->atLeastOnce())->method('getItems')->willReturn(new \ArrayIterator([$company]));
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->catalogManagement->expects($this->atLeastOnce())->method('getPublicCatalog')->willThrowException(
            new NoSuchEntityException()
        );
        $this->companyManagement->expects($this->atLeastOnce())
            ->method('getAdminByCompanyId')->with($companyId)->willReturn($companyAdmin);
        $this->groupManagement->expects($this->atLeastOnce())
            ->method('getDefaultGroup')->with($storeId)->willReturn($defaultGroup);
        $company->expects($this->atLeastOnce())->method('setCustomerGroupId')->with($defaultGroupId)->willReturnSelf();
        $this->companyRepository->expects($this->atLeastOnce())->method('save')->with($company)->willReturn($company);
        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertTrue(
            $this->updateCompanyCustomerGroupPlugin->afterDeleteById(
                $groupRepository,
                true,
                $customerGroupId
            )
        );
    }
}
