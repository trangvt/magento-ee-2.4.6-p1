<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\AdvancedCheckout\Model;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\AdvancedCheckout\Model\Cart;
use Magento\Customer\Model\Customer;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\SharedCatalog\Api\StatusInfoInterface;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Collection;
use Magento\SharedCatalog\Model\SharedCatalogResolver;
use Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\CollectionFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin.
 *
 * @covers \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HideProductsAbsentInSharedCatalogPluginTest extends TestCase
{
    /**
     * @var StatusInfoInterface|MockObject
     */
    private $config;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Cart|MockObject
     */
    private $cart;

    /**
     * @var SharedCatalogResolver|MockObject
     */
    private $sharedCatalogResolver;

    /**
     * @var CollectionFactory|MockObject
     */
    private $sharedCatalogProductCollectionFactory;

    /**
     * @var HideProductsAbsentInSharedCatalogPlugin
     */
    private $cartPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(StatusInfoInterface::class)
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();

        $this->cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogResolver = $this->getMockBuilder(SharedCatalogResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogProductCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()->getMock();

        $objectManager = new ObjectManager($this);
        $this->cartPlugin = $objectManager->getObject(
            HideProductsAbsentInSharedCatalogPlugin::class,
            [
                'config' => $this->config,
                'storeManager' => $this->storeManager,
                'sharedCatalogProductCollectionFactory' => $this->sharedCatalogProductCollectionFactory
            ]
        );
    }

    /**
     * Test for afterCheckItems().
     *
     * @param bool $isActive
     * @param $call
     * @param string $columnSkuValue
     * @param array $items
     * @param array $result
     * @return void
     * @throws LocalizedException
     * @dataProvider afterCheckItemDataProvider
     */
    public function testAfterCheckItems(
        bool $isActive,
        $call,
        string $columnSkuValue,
        array $items,
        array $result
    ): void {
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->getMock();
        $this->storeManager->expects($this->atLeastOnce())
            ->method('getWebsite')
            ->willReturn($website);
        $this->config->expects($this->atLeastOnce())
            ->method('isActive')
            ->willReturn($isActive);

        $customerGroupId = 99;
        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalogProductCollection = $this->getMockBuilder(
            Collection::class
        )->disableOriginalConstructor()->getMock();
        $select = $this->getMockBuilder(
            Select::class
        )->disableOriginalConstructor()->getMock();
        $sharedCatalogProductCollection->expects($call)
            ->method('getSelect')
            ->willReturn($select);
        $sharedCatalogProductCollection->expects($call)
            ->method('getColumnValues')
            ->willReturn([$columnSkuValue]);
        $customer->expects($call)
            ->method('getId')
            ->willReturn(666);
        $customer->expects($call)
            ->method('getGroupId')
            ->willReturn($customerGroupId);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($call)
            ->method('getCustomer')
            ->willReturn($customer);
        $this->cart->expects($call)
            ->method('getActualQuote')
            ->willReturn($quote);
        $this->sharedCatalogProductCollectionFactory->expects($call)
            ->method('create')
            ->willReturn($sharedCatalogProductCollection);
        $this->sharedCatalogResolver->expects($call)
            ->method('isPrimaryCatalogAvailable')
            ->willReturn(true);

        $this->assertEquals($result, $this->cartPlugin->afterCheckItems($this->cart, $items));
    }

    /**
     * Data provider for afterCheckItem() test.
     *
     * @return array
     */
    public function afterCheckItemDataProvider(): array
    {
        return [
            [
                false,
                $this->never(),
                'test_sku_1',
                [['code' => Data::ADD_ITEM_STATUS_SUCCESS, 'sku' => 'test_sku_1']],
                [['code' => Data::ADD_ITEM_STATUS_SUCCESS, 'sku' => 'test_sku_1']]
            ],
            [
                true,
                $this->atLeastOnce(),
                'test_sku_1',
                [['code' => Data::ADD_ITEM_STATUS_SUCCESS, 'sku' => 'test_sku_1']],
                [['code' => Data::ADD_ITEM_STATUS_SUCCESS, 'sku' => 'test_sku_1']]
            ],
            [
                true,
                $this->atLeastOnce(),
                'test_sku_1',
                [['code' => Data::ADD_ITEM_STATUS_SUCCESS, 'sku' => 'test_sku_3']],
                [['code' => Data::ADD_ITEM_STATUS_FAILED_SKU, 'sku' => 'test_sku_3']]
            ],
            [
                true,
                $this->atLeastOnce(),
                'us20-1699-02',
                [['code' => Data::ADD_ITEM_STATUS_SUCCESS, 'sku' => 'US20-1699-02']],
                [['code' => Data::ADD_ITEM_STATUS_SUCCESS, 'sku' => 'US20-1699-02']]
            ]
        ];
    }
}
