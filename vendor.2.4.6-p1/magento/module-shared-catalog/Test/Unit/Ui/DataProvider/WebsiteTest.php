<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Ui\DataProvider\Website;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Website data provider.
 *
 */
class WebsiteTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Website
     */
    private $websiteDataProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);
        $this->websiteDataProvider = $this->objectManager->getObject(
            Website::class,
            [
                'storeManager' => $this->storeManager,
            ]
        );
    }

    /**
     * Test for getWebsites method.
     *
     * @return void
     */
    public function testGetWebsites()
    {
        $this->assertEquals($this->mockWebsites(), $this->websiteDataProvider->getWebsites());
    }

    /**
     * Test for getStoreByWebsiteId method.
     *
     * @return void
     */
    public function testGetStoreByWebsiteId()
    {
        $websiteId = 1;
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getStores'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->once())->method('getWebsite')->with($websiteId)
            ->willReturn($website);
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $stores = ['store' => $store];
        $website->expects($this->exactly(1))->method('getStores')->willReturn($stores);
        $this->assertEquals($store, $this->websiteDataProvider->getStoreByWebsiteId($websiteId));
    }

    /**
     * Test for toOptionArray method.
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $this->assertEquals($this->mockWebsites(), $this->websiteDataProvider->toOptionArray());
    }

    /**
     * Mock websites.
     *
     * @return array
     */
    private function mockWebsites()
    {
        $resultWebsites = [
            [
                'value' => 0,
                'label' => __('All Websites'),
                'store_ids' => [],
            ],
            [
                'value' => 1,
                'label' => __('Website 1'),
                'store_ids' => [],
            ]
        ];
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getName', 'getId', 'getGroupIds'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $website->expects($this->atLeastOnce())->method('getName')
            ->willReturn('Website 1');
        $website->expects($this->atLeastOnce())->method('getId')
            ->willReturn(1);
        $website->expects($this->atLeastOnce())->method('getGroupIds')
            ->willReturn([]);
        $websites = [$website];
        $this->storeManager->expects($this->once())->method('getWebsites')
            ->willReturn($websites);
        $this->websiteDataProvider->getWebsites();

        return $resultWebsites;
    }
}
