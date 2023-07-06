<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyContextFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\Config as SharedCatalogConfig;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Product\CollectionPlugin;
use Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Product\CollectionPlugin as ProductCollectionPlugin;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionPluginTest extends TestCase
{
    /**
     * @var CompanyContextFactory|MockObject
     */
    private $companyContextFactory;

    /**
     * @var SharedCatalogConfig|MockObject
     */
    private $config;

    /**
     * @var CustomerGroupManagement|MockObject
     */
    private $customerGroupManagement;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CollectionPlugin
     */
    private $collectionPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyContextFactory = $this->createMock(CompanyContextFactory::class);
        $this->config = $this->createPartialMock(SharedCatalogConfig::class, ['isActive']);
        $this->customerGroupManagement = $this->createMock(CustomerGroupManagement::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $objectManager = new ObjectManager($this);
        $this->collectionPlugin = $objectManager->getObject(
            ProductCollectionPlugin::class,
            [
                'companyContextFactory' => $this->companyContextFactory,
                'config' => $this->config,
                'customerGroupManagement' => $this->customerGroupManagement,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    /**
     * Test for beforeLoad().
     *
     * @return void
     */
    public function testBeforeLoad()
    {
        $customerGroupId = 2;

        $companyContext = $this->createMock(CompanyContext::class);
        $this->companyContextFactory->method('create')
            ->willReturn($companyContext);
        $companyContext->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);
        $this->customerGroupManagement->expects($this->once())
            ->method('isPrimaryCatalogAvailable')
            ->with($customerGroupId)
            ->willReturn(false);

        $website = $this->getMockForAbstractClass(WebsiteInterface::class);
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $subject = $this->createMock(Collection::class);
        $subject->expects($this->any())->method('isLoaded')->willReturn(false);
        $this->config->expects($this->once())->method('isActive')->willReturn(true);
        $subject->expects($this->once())->method('joinTable')->willReturnSelf();
        $result = $this->collectionPlugin->beforeLoad($subject);
        $this->assertEquals($result, [false, false]);
    }
}
