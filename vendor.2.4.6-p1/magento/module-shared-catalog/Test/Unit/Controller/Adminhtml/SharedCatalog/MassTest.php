<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog;

use Magento\Backend\App\Action\Context as BackendActionContext;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\Manager;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\MassDelete;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory as SharedCatalogCollectionFactory;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class MassTest extends TestCase
{
    /**
     * @var MassDelete
     */
    protected $massAction;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $responseMock;

    /**
     * @var Manager|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Collection|MockObject
     */
    protected $sharedCatalogCollectionMock;

    /**
     * @var SharedCatalogCollectionFactory|MockObject
     */
    protected $sharedCatalogCollectionFactoryMock;

    /**
     * @var Filter|MockObject
     */
    protected $filterMock;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    protected $sharedCatalogRepositoryMock;

    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    protected $sharedCatalogManagement;

    /**
     * @var SharedCatalog|MockObject
     */
    protected $sharedCatalog;

    /**
     * @var GroupExcludedWebsiteRepositoryInterface|MockObject
     */
    protected $groupExcludedWebsite;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var AbstractResource|MockObject
     */
    protected $resource;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->contextMock = $this->createMock(BackendActionContext::class);
        $resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->responseMock = $this->getMockForAbstractClass(ResponseInterface::class);
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock =
            $this->getMockBuilder(ObjectManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create']);
        $this->sharedCatalogManagement =
            $this->getMockForAbstractClass(SharedCatalogManagementInterface::class);
        $this->sharedCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMock();
        $this->messageManagerMock = $this->createMock(Manager::class);
        $this->sharedCatalogCollectionMock =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->sharedCatalogCollectionFactoryMock =
            $this->getMockBuilder(SharedCatalogCollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();

        $redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultFactoryMock
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($redirectMock);

        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultRedirectFactory
            ->method('create')
            ->willReturn($this->resultRedirectMock);

        $this->contextMock
            ->expects(self::once())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->contextMock->expects(self::once())->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->expects(self::once())->method('getResponse')->willReturn($this->responseMock);
        $this->contextMock
            ->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);

        $this->contextMock
            ->method('getResultRedirectFactory')
            ->willReturn($resultRedirectFactory);

        $this->contextMock
            ->method('getResultFactory')
            ->willReturn($resultFactoryMock);

        $this->filterMock = $this->createMock(Filter::class);
        $this->filterMock->expects(self::once())
            ->method('getCollection')
            ->with($this->sharedCatalogCollectionMock)
            ->willReturnArgument(0);

        $this->sharedCatalogCollectionFactoryMock
            ->expects(self::once())
            ->method('create')
            ->willReturn($this->sharedCatalogCollectionMock);

        $this->sharedCatalogRepositoryMock = $this->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->groupExcludedWebsite = $this->getMockBuilder(GroupExcludedWebsiteRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->resource = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalog->method('getCustomerGroupId')->willReturn(1);

        $this->massAction = $objectManagerHelper->getObject(
            MassDelete::class,
            [
                'context' => $this->contextMock,
                'filter' => $this->filterMock,
                'collectionFactory' => $this->sharedCatalogCollectionFactoryMock,
                'logger' => $this->logger,
                'sharedCatalogRepository' => $this->sharedCatalogRepositoryMock,
                'groupExcludedWebsite' => $this->groupExcludedWebsite,
            ]
        );
    }
}
