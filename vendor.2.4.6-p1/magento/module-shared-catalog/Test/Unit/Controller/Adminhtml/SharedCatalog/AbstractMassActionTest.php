<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\AbstractMassAction;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractMassActionTest extends TestCase
{
    /** @var Context|MockObject*/
    protected $context;

    /** @var Filter|MockObject */
    protected $filter;

    /** @var CollectionFactory|MockObject */
    protected $collectionFactory;

    /** @var LoggerInterface|MockObject */
    protected $logger;

    /** @var ObjectManager */
    protected $objectManagerHelper;

    /** @var AbstractMassAction */
    protected $abstractMassAction;

    protected function setUp(): void
    {
        $this->context = $this->createPartialMock(
            Context::class,
            ['getMessageManager', 'getResultFactory']
        );
        $this->filter = $this->createPartialMock(Filter::class, ['getCollection']);
        $this->collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->logger = $this->getMockForAbstractClass(
            LoggerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['critical']
        );
    }

    /**
     * Test for method Execute
     */
    public function testExecute()
    {
        $collection = $this->createMock(AbstractDb::class);
        $filteredCollection = $this->getMockForAbstractClass(
            AbstractCollection::class,
            [],
            '',
            false
        );

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $this->filter->expects($this->once())
            ->method('getCollection')
            ->with($collection)
            ->willReturn($filteredCollection);

        $this->abstractMassAction = $this->getMockForAbstractClass(
            AbstractMassAction::class,
            [
                $this->context,
                $this->filter,
                $this->collectionFactory,
                $this->logger,
            ],
            '',
            true,
            false,
            true,
            []
        );

        $this->abstractMassAction->expects($this->once())
            ->method('massAction')
            ->with($filteredCollection);

        $result = $this->abstractMassAction->execute();
        $this->assertNull($result);
    }

    /**
     * Test for method Execute
     */
    public function testExecuteException()
    {
        $sampleResult = 'sample result';
        $message = 'An Error has occured';
        $exception = new \Exception($message);
        $messageManager = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['addError']
        );
        $this->context->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($messageManager);
        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willThrowException($exception);
        $messageManager->expects($this->once())
            ->method('addError')
            ->with($message);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $resultRedirect = $this->createPartialMock(Redirect::class, ['setPath']);
        $resultRedirect->expects($this->any())
            ->method('setPath')
            ->willReturn($sampleResult);

        $resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $resultFactory->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($resultRedirect);

        $this->context->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($resultFactory);

        $this->abstractMassAction = $this->getMockForAbstractClass(
            AbstractMassAction::class,
            [
                $this->context,
                $this->filter,
                $this->collectionFactory,
                $this->logger,
            ],
            '',
            true,
            false,
            true,
            []
        );

        $result = $this->abstractMassAction->execute();
        $this->assertEquals($sampleResult, $result);
    }
}
