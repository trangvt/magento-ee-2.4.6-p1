<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\CompanyManagement;
use Magento\SharedCatalog\Model\Management;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * CompanyManagement unit test.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyManagementTest extends TestCase
{
    /**
     * @var Management|MockObject
     */
    private $sharedCatalogManagement;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var SharedCatalog|MockObject
     */
    private $resource;

    /**
     * @var CompanyManagement
     */
    private $companyManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->sharedCatalogManagement = $this->getMockBuilder(Management::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepository = $this->getMockBuilder(
            SharedCatalogRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resource = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->companyManagement = $objectManager->getObject(
            CompanyManagement::class,
            [
                'sharedCatalogManagement' => $this->sharedCatalogManagement,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'companyRepository' => $this->companyRepository,
                'resource' => $this->resource
            ]
        );
    }

    /**
     * Test getCompanies.
     *
     * @return void
     */
    public function testGetCompanies()
    {
        $id = 1;
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with($id)
            ->willReturn($sharedCatalog);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getId')->willReturn($id);
        $companySearchResult = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companySearchResult->expects($this->once())->method('getItems')->willReturn([$company]);
        $this->companyRepository->expects($this->once())->method('getList')->willReturn($companySearchResult);

        $this->assertEquals(json_encode([$id]), $this->companyManagement->getCompanies($id));
    }

    /**
     * Test assignCompanies method.
     *
     * @return void
     */
    public function testAssignCompanies()
    {
        $id = 1;
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($id);
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with($id)
            ->willReturn($sharedCatalog);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getId')->willReturn($id);
        $company->expects($this->once())->method('setCustomerGroupId')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)->willReturn($company);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $companySearchResult = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companySearchResult->expects($this->once())->method('getItems')->willReturn([$company]);
        $this->companyRepository->expects($this->once())->method('getList')->willReturn($companySearchResult);
        $this->resource->expects($this->once())->method('beginTransaction')->willReturnSelf();
        $this->resource->expects($this->once())->method('commit')->willReturnSelf();

        $this->assertTrue($this->companyManagement->assignCompanies($id, [$company]));
    }

    /**
     * Test assignCompanies with Exception.
     *
     * @return void
     */
    public function testAssignCompaniesWithException()
    {
        $this->expectException('Exception');
        $id = 1;
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($id);
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with($id)
            ->willReturn($sharedCatalog);
        $exception = new \Exception('Exception');
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getId')->willReturn($id);
        $company->expects($this->once())->method('setCustomerGroupId')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)
            ->willThrowException($exception);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $companySearchResult = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companySearchResult->expects($this->once())->method('getItems')->willReturn([$company]);
        $this->companyRepository->expects($this->once())->method('getList')->willReturn($companySearchResult);
        $this->resource->expects($this->once())->method('beginTransaction')->willReturnSelf();
        $this->resource->expects($this->once())->method('rollBack')->willReturnSelf();

        $this->assertTrue($this->companyManagement->assignCompanies($id, [$company]));
    }

    /**
     * Test unassignCompanies.
     *
     * @return void
     */
    public function testUnassignCompanies()
    {
        $id = 1;
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($id);
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with($id)
            ->willReturn($sharedCatalog);
        $publicCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $publicCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn(2);
        $this->sharedCatalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getId')->willReturn($id);
        $company->expects($this->once())->method('setCustomerGroupId')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)->willReturn($company);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $companySearchResult = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companySearchResult->expects($this->once())->method('getItems')->willReturn([$company]);
        $this->companyRepository->expects($this->once())->method('getList')->willReturn($companySearchResult);
        $this->resource->expects($this->once())->method('beginTransaction')->willReturnSelf();
        $this->resource->expects($this->once())->method('commit')->willReturnSelf();

        $this->assertTrue($this->companyManagement->unassignCompanies($id, [$company]));
    }

    /**
     * Test unassignCompanies with Exception.
     *
     * @return void
     */
    public function testUnassignCompaniesWithException()
    {
        $this->expectException('Exception');
        $id = 1;
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($id);
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with($id)
            ->willReturn($sharedCatalog);
        $publicCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $publicCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn(2);
        $this->sharedCatalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $exception = new \Exception('Exception');
        $company->expects($this->once())->method('getId')->willReturn($id);
        $company->expects($this->once())->method('setCustomerGroupId')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)
            ->willThrowException($exception);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $companySearchResult = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companySearchResult->expects($this->once())->method('getItems')->willReturn([$company]);
        $this->companyRepository->expects($this->once())->method('getList')->willReturn($companySearchResult);
        $this->resource->expects($this->once())->method('beginTransaction')->willReturnSelf();
        $this->resource->expects($this->never())->method('commit')->willReturnSelf();
        $this->resource->expects($this->once())->method('rollBack')->willReturnSelf();

        $this->assertTrue($this->companyManagement->unassignCompanies($id, [$company]));
    }

    /**
     * Test unassignCompanies with Exception.
     *
     * @return void
     */
    public function testUnassignCompaniesWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $id = 1;
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($id);
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with($id)
            ->willReturn($sharedCatalog);
        $publicCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $publicCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn(1);
        $this->sharedCatalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyManagement->unassignCompanies($id, [$company]);
    }

    /**
     * Test unassignCompanies with empty companies ids.
     *
     * @return void
     */
    public function testUnassignCompaniesWithoutCompanies()
    {
        $id = 1;
        $this->sharedCatalogRepository->expects($this->never())->method('get');
        $this->sharedCatalogManagement->expects($this->never())->method('getPublicCatalog');

        $this->companyManagement->unassignCompanies($id, []);
    }

    /**
     * Test unassignAllCompanies.
     *
     * @return void
     */
    public function testUnassignAllCompanies()
    {
        $id = 1;
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($id);
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with($id)
            ->willReturn($sharedCatalog);
        $publicCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $publicCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn(2);
        $this->sharedCatalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('setCustomerGroupId')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)->willReturn($company);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $companySearchResult = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companySearchResult->expects($this->once())->method('getItems')->willReturn([$company]);
        $this->companyRepository->expects($this->once())->method('getList')->willReturn($companySearchResult);
        $this->resource->expects($this->once())->method('beginTransaction')->willReturnSelf();
        $this->resource->expects($this->once())->method('commit')->willReturnSelf();

        $this->companyManagement->unassignAllCompanies($id);
    }

    /**
     * Test unassignAllCompanies with Exception.
     *
     * @return void
     */
    public function testUnassignAllCompaniesWithException()
    {
        $this->expectException('Exception');
        $id = 1;
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($id);
        $this->sharedCatalogRepository->expects($this->once())->method('get')->with($id)
            ->willReturn($sharedCatalog);
        $publicCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $publicCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn(2);
        $this->sharedCatalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $exception = new \Exception('Exception');
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('setCustomerGroupId')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)
            ->willThrowException($exception);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $companySearchResult = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companySearchResult->expects($this->once())->method('getItems')->willReturn([$company]);
        $this->companyRepository->expects($this->once())->method('getList')->willReturn($companySearchResult);
        $this->resource->expects($this->once())->method('beginTransaction')->willReturnSelf();
        $this->resource->expects($this->never())->method('commit')->willReturnSelf();
        $this->resource->expects($this->once())->method('rollBack')->willReturnSelf();

        $this->companyManagement->unassignAllCompanies($id);
    }
}
