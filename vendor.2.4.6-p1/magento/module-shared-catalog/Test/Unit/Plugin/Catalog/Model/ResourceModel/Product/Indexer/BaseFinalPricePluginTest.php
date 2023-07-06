<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Catalog\Model\ResourceModel\Product\Indexer;

use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\Query\BaseFinalPrice;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Product\Indexer\BaseFinalPricePlugin;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BaseFinalPricePluginTest extends TestCase
{

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var BaseFinalPricePlugin
     */
    private $baseFinalPricePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $objectManager = new ObjectManager($this);
        $this->baseFinalPricePlugin = $objectManager->getObject(
            BaseFinalPricePlugin::class,
            [
                'resource' => $this->resourceMock,
                'scopeConfig' => $this->scopeConfigMock,
                'storeManager' => $this->storeManager
            ]
        );
    }

    public function testAfterGetQuery()
    {
        $subject = $this->createMock(BaseFinalPrice::class);
        $selectMock = $this->createMock(Select::class);
        $websiteMock = $this->getMockForAbstractClass(WebsiteInterface::class);
        $this->storeManager->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);
        $websiteMock->expects($this->once())->method('getId')->willReturn(1);
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->willReturn(true);
        $this->resourceMock->expects($this->once())
            ->method('getTableName')
            ->with('shared_catalog_product_item', 'indexer')
            ->willReturn('shared_catalog_product_item');
        $selectMock->expects($this->once())->method('joinLeft')->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->willReturnSelf();

        $this->baseFinalPricePlugin->afterGetQuery($subject, $selectMock);
    }
}
