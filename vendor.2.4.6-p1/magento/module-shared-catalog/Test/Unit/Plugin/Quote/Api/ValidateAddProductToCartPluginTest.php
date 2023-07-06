<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Quote\Api;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\Config;
use Magento\SharedCatalog\Model\SharedCatalogLocator;
use Magento\SharedCatalog\Plugin\Quote\Api\ValidateAddProductToCartPlugin;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ValidateAddProductToCartPlugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateAddProductToCartPluginTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $moduleConfig;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var SharedCatalogLocator|MockObject
     */
    private $sharedCatalogLocator;

    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $sharedCatalogManagement;

    /**
     * @var ProductManagementInterface|MockObject
     */
    private $sharedCatalogProductManagement;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ValidateAddProductToCartPlugin
     */
    private $validateAddProductToCartPlugin;

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
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogLocator = $this->getMockBuilder(SharedCatalogLocator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogManagement = $this
            ->getMockBuilder(SharedCatalogManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductManagement = $this
            ->getMockBuilder(ProductManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->validateAddProductToCartPlugin = $objectManagerHelper->getObject(
            ValidateAddProductToCartPlugin::class,
            [
                'moduleConfig' => $this->moduleConfig,
                'quoteRepository' => $this->quoteRepository,
                'sharedCatalogLocator' => $this->sharedCatalogLocator,
                'sharedCatalogManagement' => $this->sharedCatalogManagement,
                'sharedCatalogProductManagement' => $this->sharedCatalogProductManagement,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    /**
     * Test for beforeSave().
     *
     * @param int $customerGroupId
     * @param int $getSharedCatalogByCustomerGroupInvokesCount
     * @param int $getPublicCatalogInvokesCount
     * @return void
     * @dataProvider beforeSaveDataProvider
     */
    public function testBeforeSave(
        $customerGroupId,
        $getSharedCatalogByCustomerGroupInvokesCount,
        $getPublicCatalogInvokesCount
    ) {
        $quoteId = 2;
        $productSku = 'sku';
        $sharedCatalogId = 3;
        $sharedCatalogProductsSkus = ['sku', 'sku1'];
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartItem->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->quoteRepository->expects($this->atLeastOnce())->method('get')->with($quoteId)->willReturn($quote);
        $cartItem->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())->method('getId')->willReturn($sharedCatalogId);
        $this->sharedCatalogLocator->expects($this->exactly($getSharedCatalogByCustomerGroupInvokesCount))
            ->method('getSharedCatalogByCustomerGroup')->with($customerGroupId)->willReturn($sharedCatalog);
        $this->sharedCatalogManagement->expects($this->exactly($getPublicCatalogInvokesCount))
            ->method('getPublicCatalog')->willReturn($sharedCatalog);
        $this->sharedCatalogProductManagement->expects($this->atLeastOnce())->method('getProducts')
            ->with($sharedCatalogId)->willReturn($sharedCatalogProductsSkus);
        $cartItemRepository = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $result = $this->validateAddProductToCartPlugin->beforeSave($cartItemRepository, $cartItem);

        $this->assertInstanceOf(CartItemInterface::class, $result[0]);
    }

    /**
     * Test for beforeSave() with NoSuchEntityException.
     *
     * @return void
     */
    public function testBeforeSaveWithNoSuchEntityException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $customerGroupId = 1;
        $quoteId = 2;
        $productSku = 'sku';
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartItem->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->quoteRepository->expects($this->atLeastOnce())->method('get')->with($quoteId)->willReturn($quote);
        $cartItem->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $this->sharedCatalogLocator->expects($this->atLeastOnce())->method('getSharedCatalogByCustomerGroup')
            ->with($customerGroupId)->willReturn(null);
        $cartItemRepository = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->validateAddProductToCartPlugin->beforeSave($cartItemRepository, $cartItem);
    }

    /**
     * DataProvider beforeSave().
     *
     * @return array
     */
    public function beforeSaveDataProvider()
    {
        return [
            [1, 1, 0],
            [0, 0, 1]
        ];
    }

    /**
     * Test for beforeSave() method with product assigned to a public catalog.
     *
     * @return void
     */
    public function testBeforeSaveWithPublicCatalogProduct(): void
    {
        $quoteId = 2;
        $productSku = 'public-catalog-sku';
        $publicCatalogId = 1;
        $publicCatalogProductsSkus = ['sku', 'public-catalog-sku'];
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->method('getWebsite')
            ->willReturn($website);
        $this->moduleConfig->method('isActive')
            ->willReturn(true);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $quote->method('getCustomerGroupId')
            ->willReturn(1);
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartItem->method('getQuoteId')
            ->willReturn($quoteId);
        $this->quoteRepository->method('get')
            ->with($quoteId)
            ->willReturn($quote);
        $cartItem->method('getSku')
            ->willReturn($productSku);
        $publicCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $publicCatalog->method('getId')
            ->willReturn($publicCatalogId);
        $this->sharedCatalogManagement->method('getPublicCatalog')
            ->willReturn($publicCatalog);
        $this->sharedCatalogManagement->method('isPublicCatalogExist')
            ->willReturn(true);
        $this->sharedCatalogProductManagement
            ->method('getProducts')
            ->with($publicCatalogId)
            ->willReturn($publicCatalogProductsSkus);
        $cartItemRepository = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $result = $this->validateAddProductToCartPlugin->beforeSave($cartItemRepository, $cartItem);

        $this->assertInstanceOf(CartItemInterface::class, $result[0]);
    }
}
