<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog;

use Magento\Backend\Model\View\Result\RedirectFactory as BackendRedirectFactory;
use Magento\Customer\Model\ResourceModel\GroupExcludedWebsite;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Delete;
use Magento\SharedCatalog\Model\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test controller for Adminhtml\SharedCatalog\Delete.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteTest extends TestCase
{
    /**
     * Sample Id
     * @var string
     */
    const ID = '123';

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $sharedCatalogManagement;

    /**
     * @var Repository|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var GroupExcludedWebsite|MockObject
     */
    private $groupExcludedWebsite;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Delete
     */
    private $deleteMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogManagement = $this
            ->getMockBuilder(SharedCatalogManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogRepository = $this->getMockBuilder(Repository::class)
            ->setMethods(['get', 'delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultRedirectFactory = $this
            ->getMockBuilder(BackendRedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->groupExcludedWebsite = $this->getMockBuilder(GroupExcludedWebsite::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Test for method Execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $sampleRedirectResult = 'sample result'; //sample result
        $redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redirect
            ->expects($this->once())
            ->method('setPath')
            ->with('shared_catalog/sharedCatalog/index')
            ->willReturn($sampleRedirectResult);

        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($redirect);

        $urlParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $this->request
            ->expects($this->exactly(1))
            ->method('getParam')
            ->with($urlParam)
            ->willReturn(static::ID);

        $this->messageManager->expects($this->once())->method('addSuccessMessage');

        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->method('getId')->willReturn(static::ID);
        $sharedCatalog->method('getCustomerGroupId')->willReturn(static::ID);

        $this->groupExcludedWebsite
            ->expects($this->any())
            ->method('delete')
            ->with(static::ID)
            ->willReturn($this->groupExcludedWebsite);

        $this->sharedCatalogRepository
            ->expects($this->atLeastOnce())
            ->method('get')
            ->with(static::ID)
            ->willReturn($sharedCatalog);
        $this->sharedCatalogRepository->expects($this->once())->method('delete');

        $this->createDeleteMock();

        $result = $this->deleteMock->execute();
        $this->assertInstanceOf(get_class($redirect), $result);
    }

    /**
     * Test for method Execute with Exception.
     *
     * @return void
     */
    public function testExecuteException()
    {
        $exceptionMessage = 'sample exception message'; //sample exception message
        $exception = new \Exception($exceptionMessage);

        $redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupExcludedWebsite
            ->expects($this->any())
            ->method('delete')
            ->willReturn($this->groupExcludedWebsite);

        $this->resultRedirectFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturn($redirect);

        $urlParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $this->request
            ->expects($this->exactly(2))
            ->method('getParam')
            ->with($urlParam)
            ->willReturn(static::ID);

        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($exceptionMessage);

        $this->sharedCatalogRepository->expects($this->once())->method('get')->willThrowException($exception);


        $this->createDeleteMock();

        $result = $this->deleteMock->execute();
        $this->assertInstanceOf(get_class($redirect), $result);
    }

    /**
     * Create Delete mock.
     *
     * @return void
     */
    private function createDeleteMock()
    {
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->deleteMock = $this->objectManager->getObject(
            Delete::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'logger' => $loggerMock,
                '_request' => $this->request,
                'messageManager' => $this->messageManager,
            ]
        );
    }
}
