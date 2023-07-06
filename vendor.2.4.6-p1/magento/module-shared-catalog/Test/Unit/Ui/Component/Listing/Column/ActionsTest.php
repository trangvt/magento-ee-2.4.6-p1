<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Ui\Component\Listing\Column\Actions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionsTest extends TestCase
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
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var Actions|MockObject
     */
    protected $actionsMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockForAbstractClass(ContextInterface::class);
        $this->uiComponentFactory = $this->createMock(UiComponentFactory::class);
        $processor =
            $this->getMockBuilder(Processor::class)
                ->addMethods(['getProcessor'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->context->expects($this->never())->method('getProcessor')->willReturn($processor);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $objectManager = new ObjectManager($this);
        $this->actionsMock = $objectManager->getObject(
            Actions::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'urlBuilder' => $this->urlBuilder,
                'components' => [],
                'data' => [],
                'editUrl' => ''
            ]
        );
    }

    /**
     * Test for method prepareDataSource
     */
    public function testPrepareDataSource()
    {
        $dataSource['data']['items']['item'] = [SharedCatalogInterface::SHARED_CATALOG_ID => 1, 'name' => 'test'];
        $this->actionsMock->prepareDataSource($dataSource);
    }
}
