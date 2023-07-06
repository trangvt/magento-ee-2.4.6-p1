<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\AdvancedCheckout\Block;

use Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\Description;
use Magento\Backend\Block\Widget\Button;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SharedCatalog\Model\Config;
use Magento\SharedCatalog\Model\SharedCatalogProductsLoader;
use Magento\SharedCatalog\Plugin\AdvancedCheckout\Block\RenderConfigureButton;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ConfigureButtonPlugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RenderConfigureButtonTest extends TestCase
{
    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var SharedCatalogProductsLoader|MockObject
     */
    private $productsLoader;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var RenderConfigureButton
     */
    private $renderConfigureButton;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productsLoader = $this->getMockBuilder(SharedCatalogProductsLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->renderConfigureButton = $objectManagerHelper->getObject(
            RenderConfigureButton::class,
            [
                'serializer' => $this->serializer,
                'storeManager' => $this->storeManager,
                'config' => $this->config,
                'quoteRepository' => $this->quoteRepository,
                'productsLoader' => $this->productsLoader,
                'request' => $this->request
            ]
        );
    }

    /**
     * Test for aroundGetConfigureButtonHtml().
     *
     * @return void
     */
    public function testAroundGetConfigureButtonHtml()
    {
        $productId = 1;
        $customerGroupId = 3;
        $quoteId = 4;
        $quoteIdParamKey = 'quote_id';
        $sku = 'sku';
        $productSkus = ['sku', 'product_sku'];
        $buttonHtml = 'button_html';
        $item = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getIsConfigureDisabled'])
            ->getMock();
        $item->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $item->expects($this->atLeastOnce())->method('getIsConfigureDisabled')->willReturn(false);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['canConfigure'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getId')->willReturn($productId);
        $product->expects($this->atLeastOnce())->method('canConfigure')->willReturn(true);
        $encodedProductId = json_encode($productId);
        $encodedSku = json_encode($sku);
        $this->serializer->expects($this->atLeastOnce())->method('serialize')
            ->willReturnOnConsecutiveCalls($encodedProductId, $encodedSku);
        $button = $this->getMockBuilder(Button::class)
            ->disableOriginalConstructor()
            ->getMock();
        $button->expects($this->atLeastOnce())->method('toHtml')->willReturn($buttonHtml);
        $layout = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $layout->expects($this->atLeastOnce())->method('createBlock')->willReturn($button);
        $this->request->expects($this->atLeastOnce())->method('getParam')->with($quoteIdParamKey)->willReturn($quoteId);
        $subject = $this->getMockBuilder(Description::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'getProduct', 'escapeHtml', 'getLayout', 'getRequest'])
            ->getMock();
        $subject->expects($this->atLeastOnce())->method('getItem')->willReturn($item);
        $subject->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $subject->expects($this->never())->method('escapeHtml');
        $subject->expects($this->atLeastOnce())->method('getLayout')->willReturn($layout);
        $subject->expects($this->atLeastOnce())->method('getRequest')->willReturn($this->request);
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $website->expects($this->atLeastOnce())->method('getId')->willReturn(2);
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->config->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->quoteRepository->expects($this->atLeastOnce())->method('get')->with($quoteId)->willReturn($quote);
        $this->productsLoader->expects($this->atLeastOnce())->method('getAssignedProductsSkus')
            ->with($customerGroupId)->willReturn($productSkus);
        $closure = function () {
            return;
        };

        $this->assertEquals(
            $buttonHtml,
            $this->renderConfigureButton->aroundGetConfigureButtonHtml($subject, $closure)
        );
    }
}
