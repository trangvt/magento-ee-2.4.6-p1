<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Product;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Product\AbstractMassAction;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for controller Adminhtml\SharedCatalog\Configure\Product\AbstractMassAction.
 */
class AbstractMassActionTest extends TestCase
{
    /**
     * @var AbstractMassAction|MockObject
     */
    private $abstractMassAction;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Filter|MockObject
     */
    private $filter;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->filter = $this->getMockBuilder(Filter::class)
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory = $this
            ->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->abstractMassAction = $this->getMockBuilder(AbstractMassAction::class)
            ->setConstructorArgs([
                'context' => $this->context,
                'resultJsonFactory' => $this->resultJsonFactory,
                'filter' => $this->filter,
                'collectionFactory' => $this->collectionFactory,
                'logger' => $this->logger
            ])
            ->getMockForAbstractClass();
    }

    /**
     * Test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $collection = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $filteredCollection = $this
            ->getMockBuilder(AbstractCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filter->expects($this->once())->method('getCollection')->with($collection)
            ->willReturn($filteredCollection);
        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->abstractMassAction->expects($this->once())->method('massAction')->with($filteredCollection)
            ->willReturn($result);
        $this->assertEquals($result, $this->abstractMassAction->execute());
    }

    /**
     * Test for execute() with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $exception = new \Exception();
        $collection = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $this->filter->expects($this->once())->method('getCollection')->with($collection)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);
        $resultJson = $this->getMockBuilder(Json::class)
            ->setMethods(['setJsonData'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->once())->method('setJsonData')->willReturnSelf();
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($resultJson);
        $this->assertEquals($resultJson, $this->abstractMassAction->execute());
    }
}
