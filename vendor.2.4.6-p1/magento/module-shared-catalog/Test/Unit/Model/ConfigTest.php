<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\Config;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Shared Catalog Config model.
 */
class ConfigTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->config = $this->objectManagerHelper->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testIsActive()
    {
        $result = false;
        $scopeType = ScopeInterface::SCOPE_WEBSITE;
        $scopeCode = null;

        $this->scopeConfigMock->expects($this->once())->method('isSetFlag')->with(
            'btob/website_configuration/sharedcatalog_active',
            $scopeType,
            $scopeCode
        )->willReturn($result);

        $this->assertEquals($result, $this->config->isActive($scopeType, $scopeCode));
    }

    /**
     * @return void
     */
    public function testGetActiveSharedCatalogStoreIds()
    {
        $scopeType = ScopeInterface::SCOPE_WEBSITE;
        $scopeCode = 'default';
        $storeId = 1;
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStores'])
            ->getMockForAbstractClass();
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->once())->method('getWebsites')->with(true)->willReturn([$website]);
        $website->expects($this->once())->method('getCode')->willReturn($scopeCode);
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('btob/website_configuration/sharedcatalog_active', $scopeType, $scopeCode)
            ->willReturn(true);
        $website->expects($this->once())->method('getStores')->willReturn([$store]);
        $store->expects($this->once())->method('getId')->willReturn($storeId);

        $this->assertSame([$storeId], $this->config->getActiveSharedCatalogStoreIds());
    }
}
