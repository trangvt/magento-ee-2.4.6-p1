<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Company;

use Magento\Backend\Model\View\Result\RedirectFactory as BackendRedirectFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\Manager;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\SharedCatalog\Api\CompanyManagementInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Company\Save;
use Magento\SharedCatalog\Model\Form\Storage\Company;
use Magento\SharedCatalog\Model\Form\Storage\CompanyFactory;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for controller \Adminhtml\SharedCatalog\Company\Save.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var CompanyFactory|MockObject
     */
    private $companyStorageFactoryMock;

    /**
     * @var Company|MockObject
     */
    private $companyStorageMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactoryMock;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepositoryMock;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalogMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companySharedCatalogManagement;

    /**
     * @var Manager|MockObject
     */
    private $messageManagerMock;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|MockObject
     */
    private $resultRedirectFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    private $redirectMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var Save
     */
    private $saveController;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addSuccessMessage', 'addErrorMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultRedirectFactoryMock = $this
            ->getMockBuilder(BackendRedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyStorageFactoryMock = $this
            ->getMockBuilder(CompanyFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyStorageMock = $this->getMockBuilder(Company::class)
            ->setMethods(['getAssignedCompaniesIds', 'getUnassignedCompaniesIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companySharedCatalogManagement = $this
            ->getMockBuilder(CompanyManagementInterface::class)
            ->setMethods(['assignCompanies', 'unassignCompanies'])
            ->getMockForAbstractClass();

        $this->sharedCatalogRepositoryMock = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogMock = $this
            ->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getId', 'getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultPageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->saveController = $objectManager->getObject(
            Save::class,
            [
                'logger' => $this->loggerMock,
                '_request' => $this->requestMock,
                'resultPageFactory' => $this->resultPageFactoryMock,
                'messageManager' => $this->messageManagerMock,
                'sharedCatalogRepository' => $this->sharedCatalogRepositoryMock,
                'companySharedCatalogManagement' => $this->companySharedCatalogManagement,
                'companyStorageFactory' => $this->companyStorageFactoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'companyRepository' => $this->companyRepository,
                'resultRedirectFactory' => $this->resultRedirectFactoryMock
            ]
        );
    }

    /**
     * Test for method Execute.
     *
     * @param bool $isContinue
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($isContinue)
    {
        $calls = [
            'companyStorageMock_getAssignedCompaniesIds' => 1,
            'companyStorageMock_getUnassignedCompaniesIds' => 1
        ];

        $sharedCatalogId = 15;

        $configureKey = '236523dsf3';
        $sharedCatalogIdUrlParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $mapForGetParamMethod = [
            [UrlBuilder::class, null, $configureKey],
            [$sharedCatalogIdUrlParam, null, $sharedCatalogId],
            ['back', null, $isContinue]
        ];
        $this->requestMock->expects($this->exactly(3))->method('getParam')->willReturnMap($mapForGetParamMethod);

        $this->prepareCompanyStorageFactory($calls);

        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder->expects($this->exactly(3))->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))->method('create')->willReturn($searchCriteria);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $companySearchResult = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $assignedCompanies = [$company];
        $companySearchResult->expects($this->exactly(2))->method('getItems')->willReturn($assignedCompanies);

        $this->companyRepository->expects($this->exactly(2))->method('getList')->willReturn($companySearchResult);

        $this->sharedCatalogMock->expects($this->exactly(3))->method('getId')->willReturn($sharedCatalogId);
        $customerGroupId = 345;
        $this->sharedCatalogMock->expects($this->exactly(1))->method('getCustomerGroupId')
            ->willReturn($customerGroupId);

        $this->sharedCatalogRepositoryMock->expects($this->once())->method('get')->with($sharedCatalogId)
            ->willReturn($this->sharedCatalogMock);

        $this->companySharedCatalogManagement->expects($this->exactly(1))->method('assignCompanies')
            ->with($sharedCatalogId, $assignedCompanies)->willReturnSelf();
        $this->companySharedCatalogManagement->expects($this->exactly(1))->method('unassignCompanies')
            ->with($sharedCatalogId, $assignedCompanies)->willReturnSelf();

        $this->messageManagerMock->expects($this->once())->method('addSuccessMessage');

        $this->resultRedirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);

        $this->redirectMock->expects($this->once())->method('setPath')->willReturnSelf();

        $this->assertEquals($this->redirectMock, $this->saveController->execute());
    }

    /**
     * Data provider for execute() test.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * Prepare CompanyStorageFactory mock.
     *
     * @param array $calls
     * @return void
     */
    private function prepareCompanyStorageFactory(array $calls)
    {
        $companyIds = [1, 4, 6];
        $this->companyStorageMock->expects($this->exactly($calls['companyStorageMock_getAssignedCompaniesIds']))
            ->method('getAssignedCompaniesIds')->willReturn($companyIds);
        $this->companyStorageMock->expects($this->exactly($calls['companyStorageMock_getUnassignedCompaniesIds']))
            ->method('getUnassignedCompaniesIds')->willReturn($companyIds);

        $this->companyStorageFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->companyStorageMock);
    }

    /**
     * Test for execute() with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $calls = [
            'companyStorageMock_getAssignedCompaniesIds' => 0,
            'companyStorageMock_getUnassignedCompaniesIds' => 0
        ];

        $configureKey = '236523dsf3';
        $sharedCatalogId = 19;
        $mapForGetParamMethod = [
            ['id', null, $sharedCatalogId],
            [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY, null, $configureKey]
        ];
        $this->requestMock->expects($this->exactly(2))->method('getParam')->willReturnMap($mapForGetParamMethod);

        $this->prepareCompanyStorageFactory($calls);

        $exception = new \Exception();

        $this->sharedCatalogRepositoryMock->expects($this->exactly(1))->method('get')->willThrowException($exception);

        $this->loggerMock->expects($this->exactly(1))->method('critical');

        $this->messageManagerMock->expects($this->exactly(1))->method('addErrorMessage')->willReturnSelf();

        $this->resultRedirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);

        $this->redirectMock->expects($this->once())->method('setPath')->willReturnSelf();

        $this->assertEquals($this->redirectMock, $this->saveController->execute());
    }
}
