<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Wizard\Step;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Wizard\Step\Structure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Block Adminhtml\SharedCatalog\Wizard\Step\Structure.
 */
class StructureTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Structure|MockObject
     */
    private $structure;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->structure = $this->objectManagerHelper->getObject(
            Structure::class,
            [
                'context' => $this->contextMock
            ]
        );
    }

    /**
     * Test for getCaption().
     *
     * @return void
     */
    public function testGetCaption()
    {
        $expects = __('Products');
        $this->assertEquals($expects, $this->structure->getCaption());
    }
}
