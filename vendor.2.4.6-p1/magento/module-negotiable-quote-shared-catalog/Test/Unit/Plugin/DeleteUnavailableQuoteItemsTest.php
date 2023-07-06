<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement;
use Magento\NegotiableQuoteSharedCatalog\Plugin\DeleteUnavailableQuoteItems;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for DeleteUnavailableQuoteItems plugin.
 */
class DeleteUnavailableQuoteItemsTest extends TestCase
{
    /**
     * @var QuoteManagement|MockObject
     */
    private $quoteManagement;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var DeleteUnavailableQuoteItems
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quoteManagement = $this
            ->getMockBuilder(QuoteManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            DeleteUnavailableQuoteItems::class,
            [
                'quoteManagement' => $this->quoteManagement,
                'config' => $this->config,
            ]
        );
    }

    /**
     * Test for afterDelete method.
     *
     * @return void
     */
    public function testAfterDelete()
    {
        $productId = '3';
        $customerGroupId = 1;
        $storeIds = [1, 2];
        $productItemRepository = $this
            ->getMockBuilder(ProductItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productItem->expects($this->once())->method('getId')->willReturn($productId);
        $productItem->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->config->expects($this->atLeastOnce())->method('getActiveSharedCatalogStoreIds')->willReturn($storeIds);
        $this->quoteManagement->expects($this->once())
            ->method('deleteItems')
            ->with([$productId], $customerGroupId, $storeIds);

        $this->assertTrue($this->plugin->afterDelete($productItemRepository, true, $productItem));
    }

    /**
     * Test for afterDeleteItems method.
     *
     * @return void
     */
    public function testAfterDeleteItems()
    {
        $productIds = [3, 4];
        $customerGroupIds = [1, 2];
        $storeIds = [1, 2];
        $productItemRepository = $this
            ->getMockBuilder(ProductItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productItem->expects($this->atLeastOnce())->method('getId')
            ->willReturnOnConsecutiveCalls($productIds[0], $productIds[1]);
        $productItem->expects($this->atLeastOnce())->method('getCustomerGroupId')
            ->willReturnOnConsecutiveCalls($customerGroupIds[0], $customerGroupIds[1]);
        $this->config->expects($this->atLeastOnce())->method('getActiveSharedCatalogStoreIds')->willReturn($storeIds);
        $this->quoteManagement->expects($this->atLeastOnce())->method('deleteItems')
            ->withConsecutive(
                [[$productIds[0]], $customerGroupIds[0], $storeIds],
                [[$productIds[1]], $customerGroupIds[1], $storeIds]
            );

        $this->assertTrue(
            $this->plugin->afterDeleteItems($productItemRepository, true, [$productItem, $productItem])
        );
    }
}
