<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfig;

    /**
     * @var Config|MockObject
     */
    protected $config;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->scopeConfig =
            $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $objectManager = new ObjectManager($this);
        $this->config = $objectManager->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     * isActive() method test
     */
    public function testIsActive()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag');
        $this->config->isActive();
    }

    /**
     * getMaxCountRequisitionList() method test
     */
    public function testGetMaxCountRequisitionList()
    {
        $maxCount = 123;
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn((string)$maxCount);
        $actualResult = $this->config->getMaxCountRequisitionList();
        $this->assertEquals($maxCount, $actualResult);
    }
}
