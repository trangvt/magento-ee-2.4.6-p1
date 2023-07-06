<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest.
 *
 * Unit test for config model for order search history.
 */
class ConfigTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\Framework\App\Config|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Config
     */
    private $configModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this
            ->getMockBuilder(\Magento\Framework\App\Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->configModel = $this->objectManagerHelper->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
            ]
        );
    }

    /**
     * Test getMinInputLength() method.
     *
     * @return void
     */
    public function testGetMinInputLength()
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('order_history_search/general/min_input_length')
            ->willReturn(3);

        $this->assertEquals(3, $this->configModel->getMinInputLength());
    }
}
