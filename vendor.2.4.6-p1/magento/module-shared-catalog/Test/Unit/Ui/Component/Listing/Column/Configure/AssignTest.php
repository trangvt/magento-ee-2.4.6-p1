<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing\Column\Configure;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Ui\Component\Listing\Column\Configure\Assign;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssignTest extends TestCase
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
     * @var Processor|MockObject
     */
    protected $processor;

    /**
     * @var UrlBuilder|MockObject
     */
    protected $urlBuilder;

    /**
     * @var Assign|MockObject
     */
    protected $assignMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->uiComponentFactory = $this->createMock(
            UiComponentFactory::class
        );
        $this->urlBuilder = $this->createPartialMock(
            UrlBuilder::class,
            ['getUrl']
        );
        $this->processor = $this->createPartialMock(
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
            ->willReturn($this->processor);
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function prepareDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * Test prepare() method.
     *
     * @param array $dataConfigAtKeySet
     *
     * @return void
     * @dataProvider prepareDataProvider
     */
    public function testPrepare($dataConfigAtKeySet): void
    {
        $data = [];
        if ($dataConfigAtKeySet === true) {
            $data['config']['assignClientConfig'] = [true];
            $data['config']['massAssignClientConfig'] = [true];
            $this->urlBuilder
                ->method('getUrl')
                ->withConsecutive(
                    ['shared_catalog/sharedCatalog/configure_product_assign'],
                    ['shared_catalog/sharedCatalog/configure_product_massAssign']
                );
        }
        $this->assignMock = $this->objectManager->getObject(
            Assign::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'urlBuilder' => $this->urlBuilder,
                'components' => [],
                'data' => $data
            ]
        );
        $this->assignMock->prepare();
    }
}
