<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\ResourceModel\Company\Collection;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\Adminhtml\Index\MassDelete;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\Manager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractMass extends TestCase
{
    /**
     * @var string
     */
    protected $actionName = '';

    /**
     * @var string
     */
    protected $successMessage = '';

    /**
     * @var MassDelete
     */
    protected $massAction;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var Manager|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Collection|MockObject
     */
    protected $companyCollectionMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $companyCollectionFactoryMock;

    /**
     * @var Filter|MockObject
     */
    protected $filterMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    protected $companyRepositoryMock;

    /**
     * SetUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (empty($this->actionName)) {
            return;
        }
        $objectManagerHelper = new ObjectManagerHelper($this);
        $resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->objectManagerMock = $this->createPartialMock(
            \Magento\Framework\ObjectManager\ObjectManager::class,
            ['create']
        );
        $this->messageManagerMock = $this->createMock(Manager::class);
        $this->companyCollectionMock =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->companyCollectionFactoryMock =
            $this->getMockBuilder(CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
        $redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMock();
        $redirectMock->expects($this->any())->method('setPath')->willReturnSelf();
        $resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($redirectMock);

        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->resultRedirectMock);
        $this->filterMock = $this->createMock(Filter::class);
        $this->filterMock->expects($this->once())
            ->method('getCollection')
            ->with($this->companyCollectionMock)
            ->willReturnArgument(0);
        $this->companyCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->companyCollectionMock);
        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->massAction = $objectManagerHelper->getObject(
            '\Magento\Company\Controller\Adminhtml\Index\Mass' . $this->actionName,
            [
                'filter' => $this->filterMock,
                'collectionFactory' => $this->companyCollectionFactoryMock,
                'companyRepository' => $this->companyRepositoryMock,
                'resultRedirectFactory' => $resultRedirectFactory,
                'resultFactory' => $resultFactoryMock,
                'messageManager' => $this->messageManagerMock
            ]
        );
    }
}
