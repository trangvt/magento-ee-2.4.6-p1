<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\AsynchronousOperations\Api\Data\OperationListInterface;
use Magento\AsynchronousOperations\Api\Data\OperationListInterfaceFactory;
use Magento\Framework\MessageQueue\MergedMessageInterface;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\Merger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Merger.
 */
class MergerTest extends TestCase
{
    /**
     * @var OperationListInterfaceFactory|MockObject
     */
    private $operationListFactory;

    /**
     * @var MergedMessageInterfaceFactory|MockObject
     */
    private $mergedMessageFactory;

    /**
     * @var Merger
     */
    private $merger;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->operationListFactory = $this
            ->getMockBuilder(OperationListInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mergedMessageFactory = $this
            ->getMockBuilder(MergedMessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->merger = $objectManager->getObject(
            Merger::class,
            [
                'operationListFactory' => $this->operationListFactory,
                'mergedMessageFactory' => $this->mergedMessageFactory,
            ]
        );
    }

    /**
     * Test for merge().
     *
     * @return void
     */
    public function testMerge()
    {
        $topicName = 'topic.name';
        $messages = [$topicName => [1 => 'message1', 2 => 'message2']];
        $operationList = $this->getMockBuilder(OperationListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->operationListFactory->expects($this->atLeastOnce())->method('create')
            ->with(['items' => $messages[$topicName]])->willReturn($operationList);
        $mergedMessage = $this->getMockBuilder(MergedMessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mergedMessageFactory->expects($this->atLeastOnce())->method('create')->willReturn($mergedMessage);

        $this->assertEquals([$topicName => [$mergedMessage]], $this->merger->merge($messages));
    }
}
