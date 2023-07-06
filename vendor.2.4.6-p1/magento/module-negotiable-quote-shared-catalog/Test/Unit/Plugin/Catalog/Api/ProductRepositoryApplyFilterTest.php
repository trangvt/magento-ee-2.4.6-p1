<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Plugin\Catalog\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuoteSharedCatalog\Model\SharedCatalog\ProductItem\Retrieve;
use Magento\NegotiableQuoteSharedCatalog\Plugin\Catalog\Api\ProductRepositoryApplyFilter;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Model\Config;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProductRepositoryApplyFilter plugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductRepositoryApplyFilterTest extends TestCase
{
    /**
     * @var ProductRepositoryApplyFilter
     */
    private $productRepositoryPlugin;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $subject;

    /**
     * @var ProductInterface|MockObject
     */
    private $product;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Retrieve|MockObject
     */
    private $sharedCatalogProductItemRetriever;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->subject = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemRetriever = $this->getMockBuilder(Retrieve::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->productRepositoryPlugin = $objectManager->getObject(
            ProductRepositoryApplyFilter::class,
            [
                'config' => $this->config,
                'request' => $this->request,
                'quoteRepository' => $this->quoteRepository,
                'storeManager' => $this->storeManager,
                'sharedCatalogProductItemRetriever' => $this->sharedCatalogProductItemRetriever
            ]
        );
    }

    /**
     * Test for getById() method when Shared Catalog is disabled.
     *
     * @return void
     */
    public function testConfigDisabledAfterGetById()
    {
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $result = $this->productRepositoryPlugin->afterGetById($this->subject, $this->product);
        $this->assertEquals($result, $this->product);
    }

    /**
     * Test for get() method when Shared Catalog is disabled.
     *
     * @return void
     */
    public function testConfigDisabledAfterGet()
    {
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $result = $this->productRepositoryPlugin->afterGet($this->subject, $this->product);
        $this->assertEquals($result, $this->product);
    }

    /**
     * Test for getById() method when Shared Catalog is enabled.
     *
     * @param int $throwException
     * @return void
     * @dataProvider afterGetDataProvider
     */
    public function testAfterGetById($throwException)
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturn(true);
        $this->prepareBody($throwException);
        $result = $this->productRepositoryPlugin->afterGetById($this->subject, $this->product);
        $this->assertEquals($result, $this->product);
    }

    /**
     * Test for get() method when Shared Catalog is enabled.
     *
     * @param int $throwException
     * @return void
     * @dataProvider afterGetDataProvider
     */
    public function testAfterGet($throwException)
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturn(true);
        $this->prepareBody($throwException);
        $result = $this->productRepositoryPlugin->afterGet($this->subject, $this->product);
        $this->assertEquals($result, $this->product);
    }

    /**
     * Test for get() method when customer group ID is empty.
     *
     * @return void
     */
    public function testAfterGetWithEmptyCustomerGroupId()
    {
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturn(true);
        $this->config->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $quote->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn(null);
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturn($quote);
        $this->product->expects($this->never())
            ->method('getData');

        $result = $this->productRepositoryPlugin->afterGet($this->subject, $this->product);
        $this->assertEquals($result, $this->product);
    }

    /**
     * Prepare the body of main tests.
     *
     * @param int $throwException
     * @return void
     */
    private function prepareBody($throwException)
    {
        $customerGroupId = 1;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $quote->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);
        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->willReturn($quote);
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $this->product->expects($this->once())
            ->method('getData')
            ->willReturnMap([
                ['sku', null, 'testsku']
            ]);
        $items = $this->getMockBuilder(
            ProductItemInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        if ($throwException == 1) {
            $items = null;
            $this->expectException(NoSuchEntityException::class);
            $this->expectExceptionMessage(
                'The product that was requested doesn\'t exist. Verify the product and try again.'
            );
        }
        $this->sharedCatalogProductItemRetriever
            ->expects($this->once())
            ->method('retrieve')
            ->willReturn($items);
    }

    /**
     * Data provider for testAfterGetById test.
     *
     * @return array
     */
    public function afterGetDataProvider()
    {
        return [
            [
                'throwException' => 0
            ],
            [
                'throwException' => 1
            ]
        ];
    }
}
