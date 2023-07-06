<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\Form\Storage\CompanyFactory as StorageCompanyFactory;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Ui\DataProvider\Collection\Grid\Company;
use Magento\SharedCatalog\Ui\DataProvider\Collection\Grid\CompanyFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Company data provider.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CompanyTest extends TestCase
{
    /**
     * @var string
     */
    private $filterValue = 'test value';

    /**
     * @var string
     */
    private $key = 'test key';

    /**
     * @var array
     */
    private $assignedCompaniesIds = ['test id'];

    /**
     * @var string
     */
    private $sharedCatalogId = 'test sharedCatalog id';

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CompanyFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var StorageCompanyFactory|MockObject
     */
    private $companyStorageFactory;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $catalogRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Company|MockObject
     */
    private $collection;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * @var \Magento\SharedCatalog\Model\Form\Storage\Company|MockObject
     */
    private $storage;

    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $catalogManagement;

    /**
     * @var Filter|MockObject
     */
    private $filter;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var SearchCriteriaInterface|MockObject
     */
    private $searchCriteria;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var CompanySearchResultsInterface|MockObject
     */
    private $companySearchResults;

    /**
     * @var \Magento\SharedCatalog\Ui\DataProvider\Company
     */
    private $companyDataProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->collectionFactory = $this->getMockBuilder(CompanyFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storage = $this->getMockBuilder(\Magento\SharedCatalog\Model\Form\Storage\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyStorageFactory = $this->getMockBuilder(StorageCompanyFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogRepository = $this->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->onlyMethods(['get'])
            ->addMethods(['getBySharedCatalogId', 'getPublicCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collection = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogManagement = $this->getMockBuilder(SharedCatalogManagementInterface::class)
            ->onlyMethods(['getPublicCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->filter = $this->getMockBuilder(Filter::class)
            ->onlyMethods(['getField', 'getValue', 'getConditionType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->onlyMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companySearchResults = $this
            ->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request->expects($this->once())->method('getParam')
            ->with(UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY)
            ->willReturn($this->key);
        $this->companyStorageFactory->expects($this->once())
            ->method('create')->with(['key' => $this->key])->willReturn($this->storage);

        $this->objectManager = new ObjectManager($this);
        $this->companyDataProvider = $this->objectManager->getObject(
            \Magento\SharedCatalog\Ui\DataProvider\Company::class,
            [
                'request' => $this->request,
                'collectionFactory' => $this->collectionFactory,
                'companyStorageFactory' =>$this->companyStorageFactory,
                'catalogManagement' => $this->catalogManagement,
                'catalogRepository' => $this->catalogRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'companyRepository' => $this->companyRepository,
                'data' => [
                    'config' => [
                        'filter_url_params' => [
                            'shared_catalog_id' => 1
                        ],
                        'update_url' => ''
                    ]
                ]
            ]
        );
    }

    /**
     * Test for addFilter method.
     *
     * @return void
     */
    public function testAddFilter(): void
    {
        $field = 'test field';
        $conditionType = 'test condition type';
        $this->filter->expects($this->once())->method('getValue')->willReturn($this->filterValue);
        $this->filter->expects($this->exactly(2))->method('getField')->willReturn($field);
        $this->filter->expects($this->once())->method('getConditionType')->willReturn($conditionType);
        $this->collection->expects($this->once())
            ->method('addFieldToFilter')->with($field, [$conditionType => $this->filterValue])->willReturnSelf();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);
        $this->companyDataProvider->addFilter($this->filter);
    }

    /**
     * Test for addFilter method with is_current field.
     *
     * @return void
     */
    public function testAddFilterWithIsCurrent(): void
    {
        $this->filter->expects($this->once())->method('getValue')->willReturn($this->filterValue);
        $this->filter->expects($this->once())->method('getField')->willReturn('is_current');
        $this->collection->expects($this->once())->method('addIdFilter')
            ->with($this->assignedCompaniesIds, !$this->filterValue);
        $this->storage->expects($this->once())
            ->method('getAssignedCompaniesIds')->willReturn($this->assignedCompaniesIds);
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);
        $this->companyDataProvider->addFilter($this->filter);
    }

    /**
     * Test for addFilter method with shared_catalog_id field.
     *
     * @return void
     */
    public function testAddFilterWithSharedCatalogId(): void
    {
        $this->filter->expects($this->once())->method('getValue')->willReturn($this->filterValue);
        $this->filter->expects($this->once())->method('getField')->willReturn('shared_catalog_id');
        $this->storage->expects($this->atLeastOnce())->method('getSharedCatalogId')->willReturn($this->sharedCatalogId);
        $this->catalogRepository->method('get')
            ->withConsecutive([$this->sharedCatalogId], [$this->filterValue])
            ->willReturnOnConsecutiveCalls($this->sharedCatalog, $this->sharedCatalog);
        $this->storage->expects($this->once())
            ->method('getAssignedCompaniesIds')->willReturn($this->assignedCompaniesIds);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')->willReturn($this->searchCriteriaBuilder);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($this->searchCriteria);
        $this->companyRepository->expects($this->once())
            ->method('getList')->with($this->searchCriteria)->willReturn($this->companySearchResults);
        $this->companySearchResults->expects($this->once())
            ->method('getItems')->willReturn($this->assignedCompaniesIds);
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);
        $publicCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $publicCatalog->expects($this->once())->method('getId')->willReturn($this->filterValue);
        $this->storage->expects($this->once())->method('getUnassignedCompaniesIds')->willReturn([]);
        $this->companyDataProvider->addFilter($this->filter);
    }

    /**
     * Test for addFilter method with shared_catalog_id field that matches current catalog id.
     *
     * @return void
     */
    public function testAddFilterWithSameSharedCatalogId(): void
    {
        $this->filter->expects($this->once())->method('getValue')->willReturn($this->filterValue);
        $this->filter->expects($this->once())->method('getField')->willReturn('shared_catalog_id');
        $this->storage->expects($this->atLeastOnce())->method('getSharedCatalogId')->willReturn($this->sharedCatalogId);
        $this->catalogRepository->method('get')
            ->with($this->sharedCatalogId)
            ->willReturn($this->sharedCatalog);
        $this->collection->expects($this->once())->method('addIdFilter')
            ->with($this->assignedCompaniesIds, !$this->filterValue);
        $this->storage->expects($this->once())
            ->method('getAssignedCompaniesIds')->willReturn($this->assignedCompaniesIds);
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);
        $this->sharedCatalog->expects($this->once())->method('getId')->willReturn($this->filterValue);
        $this->companyDataProvider->addFilter($this->filter);
    }

    /**
     * Test for getData method.
     *
     * @param bool $isAssigned
     * @return void
     * @dataProvider getDataDataProvider
     */
    public function testGetData($isAssigned): void
    {
        $entityId = 2;
        $dataObject = $this->getMockBuilder(DataObject::class)
            ->onlyMethods(['toArray'])
            ->addMethods(
                [
                    'getEntityId',
                    'getSharedCatalogId',
                    'setSharedCatalogId',
                    'setIsCurrent',
                    'setIsPublicCatalog'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);
        $this->collection->expects($this->once())->method('addIsCurrentColumn')->with($this->sharedCatalogId)
            ->willReturnSelf();
        $this->collection->expects($this->once())->method('getSize')->willReturn(1);
        $this->collection->expects($this->once())->method('getItems')->willReturn([$dataObject]);
        $dataObject->expects($this->atLeastOnce())->method('getEntityId')->willReturn($entityId);
        $this->storage->expects($this->once())
            ->method('isCompanyAssigned')->with($entityId)->willReturn($isAssigned);
        $this->storage->expects($this->once())
            ->method('isCompanyUnassigned')->with($entityId)->willReturn(!$isAssigned);
        $dataObject->expects($this->atLeastOnce())->method('getSharedCatalogId')->willReturn($this->sharedCatalogId);
        $this->storage->expects($this->atLeastOnce())
            ->method('getSharedCatalogId')->willReturn($this->sharedCatalogId);
        $dataObject->expects($this->once())
            ->method('setSharedCatalogId')->with($this->sharedCatalogId)->willReturnSelf();
        $dataObject->expects($this->once())->method('setIsCurrent')->with(1)->willReturnSelf();
        $dataObject->expects($this->once())->method('setIsPublicCatalog')->with(true)->willReturnSelf();
        $publicCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogManagement->expects($this->atLeastOnce())
            ->method('getPublicCatalog')->willReturn($publicCatalog);
        $publicCatalog->expects($this->atLeastOnce())->method('getId')->willReturn($this->sharedCatalogId);
        $dataObject->expects($this->once())->method('toArray')->willReturn(['company_data']);
        $this->assertEquals(
            [
                'totalRecords' => 1,
                'items' => [['company_data']],
            ],
            $this->companyDataProvider->getData()
        );
    }

    /**
     * Test for getConfigData method.
     *
     * @return void
     */
    public function testGetConfigData(): void
    {
        $this->assertEquals(
            [
                'filter_url_params' => [
                    'shared_catalog_id' => 1
                ],
                'update_url' => 'shared_catalog_id/1/',
            ],
            $this->companyDataProvider->getConfigData()
        );
    }

    /**
     * Data provider for testGetData.
     *
     * @return array
     */
    public function getDataDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
