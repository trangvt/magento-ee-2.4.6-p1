<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Messages;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\SharedCatalog\Block\Adminhtml\SharedCatalog\Messages\Notification;
use Magento\SharedCatalog\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for block Magento\SharedCatalog\Test\Unit\Block\Adminhtml\SharedCatalog\Messages\Notification.
 */
class NotificationTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $moduleConfig;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->notification = $objectManager->getObject(
            Notification::class,
            [
                'moduleConfig' => $this->moduleConfig,
                '_authorization' => $this->authorization,
                '_urlBuilder' => $this->urlBuilder
            ]
        );
    }

    /**
     * Test for isConfigurationAvailable method.
     *
     * @return void
     */
    public function testIsConfigurationAvailable()
    {
        $this->authorization->expects($this->once())
            ->method('isAllowed')->with('Magento_Config::config')->willReturn(true);
        $this->assertTrue($this->notification->isConfigurationAvailable());
    }

    /**
     * Test for getConfigurationUrl method.
     *
     * @return void
     */
    public function testGetConfigurationUrl()
    {
        $url = 'url value';
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')->with('adminhtml/system_config/edit', ['section' => 'btob'])->willReturn($url);
        $this->assertEquals($url, $this->notification->getConfigurationUrl());
    }
}
