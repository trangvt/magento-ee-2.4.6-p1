<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Form;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\SharedCatalog\Ui\Component\Form\Field;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    /**
     * @var ContextInterface|MockObject
     */
    protected $context;

    /**
     * @var UiComponentFactory|MockObject
     */
    protected $uiComponentFactory;

    /**
     * @var Field|MockObject
     */
    protected $fieldMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Test prepare() method
     */
    public function testPrepare()
    {
        $data = [
            'config'=> ['formElement'=>'testElement']
        ];
        $processor = $this->createPartialMock(
            Processor::class,
            ['register', 'notify']
        );
        $this->context = $this->getMockForAbstractClass(
            ContextInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getProcessor']
        );
        $this->context->expects($this->atLeastOnce())
            ->method('getProcessor')
            ->willReturn($processor);
        $wrappedComponent = $this->getMockForAbstractClass(
            UiComponentInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setData', 'getContext']
        );
        $wrappedComponent->expects($this->any())
            ->method('getContext')
            ->willReturn($this->context);
        $this->uiComponentFactory = $this->createPartialMock(
            UiComponentFactory::class,
            ['create']
        );
        $this->uiComponentFactory->expects($this->any())
            ->method('create')
            ->willReturn($wrappedComponent);
        $this->fieldMock = $this->objectManager->getObject(
            Field::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'components' => [],
                'data' => $data,
            ]
        );
        $this->fieldMock->prepare();
    }
}
