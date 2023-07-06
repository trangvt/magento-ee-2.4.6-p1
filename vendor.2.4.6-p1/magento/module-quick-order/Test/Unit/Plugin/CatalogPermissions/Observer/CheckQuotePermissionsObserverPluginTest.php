<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Plugin\CatalogPermissions\Observer;

use Magento\CatalogPermissions\Observer\CheckQuotePermissionsObserver;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\QuickOrder\Model\Config;
use Magento\QuickOrder\Plugin\CatalogPermissions\Observer\CheckQuotePermissionsObserverPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckQuotePermissionsObserverPluginTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var CheckQuotePermissionsObserverPlugin|MockObject
     */
    private $checkQuotePermissionsObserverPlugin;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['isActive'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->checkQuotePermissionsObserverPlugin = $this->objectManagerHelper->getObject(
            CheckQuotePermissionsObserverPlugin::class,
            [
                'config' => $this->configMock
            ]
        );
    }

    /**
     * Test for aroundExecute() method
     *
     * @return void
     */
    public function testAroundExecute()
    {
        $this->configMock->expects($this->any())
            ->method('isActive')
            ->willReturn(true);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = $this->getMockBuilder(CheckQuotePermissionsObserver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function ($observer) {
            return $observer;
        };

        $this->assertInstanceOf(
            CheckQuotePermissionsObserver::class,
            $this->checkQuotePermissionsObserverPlugin->aroundExecute($subject, $proceed, $observerMock)
        );
    }

    /**
     * Test for aroundExecute() method when our extension is inactive
     *
     * @return void
     */
    public function testAroundExecuteInactive()
    {
        $this->configMock->expects($this->any())
            ->method('isActive')
            ->willReturn(false);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = $this->getMockBuilder(CheckQuotePermissionsObserver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function ($observer) {
            return $observer;
        };

        $this->assertInstanceOf(
            Observer::class,
            $this->checkQuotePermissionsObserverPlugin->aroundExecute($subject, $proceed, $observerMock)
        );
    }
}
