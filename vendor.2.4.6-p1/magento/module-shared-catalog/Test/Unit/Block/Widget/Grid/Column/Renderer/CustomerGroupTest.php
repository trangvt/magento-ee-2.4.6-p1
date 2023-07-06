<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Widget\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Block\Widget\Grid\Column\Renderer\CustomerGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for block CustomerGroup.
 */
class CustomerGroupTest extends TestCase
{
    /**
     * @var Column|MockObject
     */
    private $column;

    /**
     * @var Escaper|MockObject
     */
    private $escaper;

    /**
     * @var CustomerGroup
     */
    private $customerGroup;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->column = $this->getMockBuilder(Column::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptions', 'getIndex', 'getShowMissingOptionValues'])
            ->getMock();
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->customerGroup = $objectManager->getObject(
            CustomerGroup::class,
            [
                'escaper' => $this->escaper
            ]
        );
        $this->customerGroup->setColumn($this->column);
    }

    /**
     * Test for render method.
     *
     * @return void
     */
    public function testRender()
    {
        $options = [
            [
                'label' => 'Group',
                'value' => [
                    [
                        'value' => 1,
                        'label' => 'Custom group'
                    ]
                ]
            ]
        ];
        $this->column->expects($this->atLeastOnce())->method('getOptions')->willReturn($options);
        $this->column->expects($this->atLeastOnce())->method('getIndex')->willReturn('group');
        $this->escaper->expects($this->atLeastOnce())->method('escapeHtml')->willReturnArgument(0);

        $row = new DataObject(['group' => 1]);
        $this->assertEquals(
            'Custom group',
            $this->customerGroup->render($row)
        );
    }
}
