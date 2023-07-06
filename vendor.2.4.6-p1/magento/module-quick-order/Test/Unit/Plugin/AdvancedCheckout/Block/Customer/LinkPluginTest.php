<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Plugin\AdvancedCheckout\Block\Customer;

use Magento\AdvancedCheckout\Block\Customer\Link;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\QuickOrder\Model\Config;
use Magento\QuickOrder\Plugin\AdvancedCheckout\Block\Customer\LinkPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LinkPlugin plugin.
 */
class LinkPluginTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var LinkPlugin
     */
    private $linkPlugin;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->linkPlugin = $this->objectManagerHelper->getObject(
            LinkPlugin::class,
            [
                'config' => $this->configMock
            ]
        );
    }

    /**
     * Test for aroundToHtml() method if QuickOrder is inactive.
     *
     * @return void
     */
    public function testAroundToHtmlIfConfigInactive()
    {
        $expectedResult = 'test';
        $this->configMock->expects($this->once())->method('isActive')->willReturn(false);

        $subject = $this->getMockBuilder(Link::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () use ($expectedResult) {
            return $expectedResult;
        };

        $this->assertEquals($expectedResult, $this->linkPlugin->aroundToHtml($subject, $proceed));
    }

    /**
     * Test for aroundToHtml() method if QuickOrder is active.
     *
     * @return void
     */
    public function testAroundToHtmlIfConfigActive()
    {
        $this->configMock->expects($this->once())->method('isActive')->willReturn(true);

        $subject = $this->getMockBuilder(Link::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return;
        };

        $this->assertEquals('', $this->linkPlugin->aroundToHtml($subject, $proceed));
    }
}
