<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Wizard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\Container;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layout;

    /**
     * @var AbstractBlock|MockObject
     */
    protected $block;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Container|MockObject
     */
    protected $containerMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->layout = $this->getMockForAbstractClass(
            LayoutInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getChildName', 'getBlock']
        );
        $this->block = $this->getMockForAbstractClass(
            AbstractBlock::class,
            [],
            '',
            false,
            false,
            true,
            ['setInitData', 'toHtml']
        );
        $this->context = $this->createPartialMock(Context::class, ['getLayout']);
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Test for getWizard() function
     *
     * @param bool $isLayout
     * @param string $expectedResult
     * @dataProvider getWizardDataProvider
     */
    public function testGetWizard($isLayout, $expectedResult)
    {
        $initData = ['test data'];
        $this->context->expects($this->once())
            ->method('getLayout')
            ->willReturn($this->layout);
        if ($isLayout == true) {
            $name = 'test name';
            $this->layout->expects($this->once())
                ->method('getChildName')
                ->willReturn($name);
            $this->layout->expects($this->once())
                ->method('getBlock')
                ->willReturn($this->block);
            $this->block->expects($this->once())
                ->method('setInitData')
                ->with($initData);
            $this->block->expects($this->once())
                ->method('toHtml')
                ->willReturn($expectedResult);
        }
        $this->containerMock = $this->objectManager->getObject(
            Container::class,
            [
                'context' => $this->context,
                'data' => [],
            ]
        );
        $actualResult = $this->containerMock->getWizard($initData);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array
     */
    public function getWizardDataProvider()
    {
        return [
            [
                true, 'test html'
            ],
            [
                false, ''
            ]
        ];
    }
}
