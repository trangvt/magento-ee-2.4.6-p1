<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Product\Price;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Product\Price\Save;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ProductItemTierPriceValidator;
use PHPUnit\Framework\MockObject\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var FormatInterface|MockObject
     */
    private $valueFormatter;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ProductItemTierPriceValidator|MockObject
     */
    private $productItemTierPriceValidator;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var Save
     */
    private $save;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueFormatter = $this->getMockBuilder(FormatInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productItemTierPriceValidator = $this
            ->getMockBuilder(ProductItemTierPriceValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository = $this
            ->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->save = $objectManager->getObject(
            Save::class,
            [
                '_request' => $this->request,
                'resultJsonFactory' => $this->resultJsonFactory,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'valueFormatter' => $this->valueFormatter,
                'productItemTierPriceValidator' => $this->productItemTierPriceValidator,
                'productRepository' => $this->productRepository,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @param string $customPrice
     * @param int $numericPrice
     * @param Invocation $setPriceInvocation
     * @param Invocation $deletePriceInvocation
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($customPrice, $numericPrice, $setPriceInvocation, $deletePriceInvocation)
    {
        $configureKey = 'configure_key_value';
        $productId = 1;
        $productSku = 'ProductSKU';
        $priceType = 'fixed';
        $requestParamConfigureKey = UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY;
        $websiteId = 2;
        $prices = [
            [
                'product_id' => $productId,
                'custom_price' => $customPrice,
                'website_id' => $websiteId,
                'price_type' => $priceType,
            ],
            []
        ];
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->withConsecutive(
                ['prices'],
                [$requestParamConfigureKey]
            )
            ->willReturnOnConsecutiveCalls($prices, $configureKey);
        $wizardStorage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')->with(['key' => $configureKey])->willReturn($wizardStorage);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())
            ->method('getById')->with($productId)->willReturn($product);
        $this->valueFormatter->expects($this->once())
            ->method('getNumber')->with($customPrice)->willReturn($numericPrice);
        $wizardStorage->expects($this->atLeastOnce())
            ->method('getProductPrices')->with($productId)->willReturn([]);
        $this->productItemTierPriceValidator->expects($this->atLeastOnce())
            ->method('canChangePrice')->with([], $websiteId)->willReturn(true);
        $product->expects($this->once())->method('getSku')->willReturn($productSku);
        $wizardStorage->expects($setPriceInvocation)
            ->method('setTierPrices')->with([
                $productSku => [
                    [
                        'qty' => 1,
                        ProductAttributeInterface::CODE_PRICE => $numericPrice,
                        'value_type' => $priceType,
                        'website_id' => $websiteId,
                        'is_changed' => true,
                    ],
                ]
            ]);
        $wizardStorage->expects($deletePriceInvocation)->method('deleteTierPrice')->with($productSku, 1, $websiteId);
        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $json->expects($this->once())->method('setJsonData')
            ->with(json_encode(['data' => ['status' => 1]], JSON_NUMERIC_CHECK))->willReturnSelf();
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($json);
        $this->assertEquals($json, $this->save->execute());
    }

    /**
     * Data provider for testExecute.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            ['$15', 15, $this->once(), $this->never()],
            ['-$15', -15, $this->never(), $this->once()],
            ['$0', 0, $this->once(), $this->never()],
        ];
    }
}
