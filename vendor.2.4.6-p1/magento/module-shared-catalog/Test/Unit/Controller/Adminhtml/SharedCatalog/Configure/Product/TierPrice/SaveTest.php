<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Product\TierPrice;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Product\TierPrice\Save;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ProductItemTierPriceValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for tier price Save controller.
 */
class SaveTest extends TestCase
{
    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var ProductItemTierPriceValidator|MockObject
     */
    private $productItemTierPriceValidator;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var FormatInterface|MockObject
     */
    private $format;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var Save
     */
    private $controller;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productItemTierPriceValidator = $this
            ->getMockBuilder(ProductItemTierPriceValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultJsonFactory = $this
            ->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->format = $this->getMockBuilder(FormatInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Save::class,
            [
                '_request' => $this->request,
                'productItemTierPriceValidator' => $this->productItemTierPriceValidator,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'resultJsonFactory' => $this->resultJsonFactory,
                'format' => $this->format,
                'productRepository' => $this->productRepository,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $tierPrices = [
            [
                'qty' => 1,
                'website_id' => 1,
                'value_type' => 'percent',
                'price' => 10,
                'percentage_value' => 5,
            ],
            [
                'delete' => true
            ]
        ];
        $productId = 1;
        $productSku = 'ProductSKU';
        $storage = $this
            ->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(['tier_price', []], ['product_id'], ['configure_key'])
            ->willReturnOnConsecutiveCalls($tierPrices, $productId, 'configure_key');
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())
            ->method('getById')->with($productId)->willReturn($product);
        $this->productItemTierPriceValidator->expects($this->once())
            ->method('validateDuplicates')
            ->with($tierPrices)
            ->willReturn(true);
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => 'configure_key'])
            ->willReturn($storage);
        $storage->expects($this->once())->method('deleteTierPrices')->with($productSku);
        $this->format->expects($this->exactly(4))
            ->method('getNumber')
            ->withConsecutive([1], [1], [10], [5])
            ->willReturnOnConsecutiveCalls(1, 1, 10, 5);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $storage->expects($this->once())
            ->method('setTierPrices')
            ->with(
                [
                    $productSku => [
                        [
                            'qty' => 1,
                            'website_id' => 1,
                            'value_type' => 'percent',
                            'is_changed' => true,
                            'price' => 10,
                            'percentage_value' => 5,
                        ],
                    ],
                ]
            );
        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = ['data' => ['status' => 1]];
        $json->expects($this->once())
            ->method('setJsonData')
            ->with(json_encode($result, JSON_NUMERIC_CHECK))
            ->willReturnSelf();
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($json);
        $this->assertEquals($json, $this->controller->execute());
    }

    /**
     * Test Execute method with duplicate tier prices.
     *
     * @return void
     */
    public function testExecuteWithInvalidPrice()
    {
        $tierPrices = [
            [
                'qty' => 1,
                'website_id' => 1,
                'value_type' => 'percent',
                'price' => 10,
                'percentage_value' => 5,
            ],
            [
                'delete' => true
            ]
        ];
        $productId = 1;
        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(['tier_price', []], ['product_id'])
            ->willReturnOnConsecutiveCalls($tierPrices, $productId);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())
            ->method('getById')->with($productId)->willReturn($product);
        $this->productItemTierPriceValidator->expects($this->once())
            ->method('validateDuplicates')
            ->with($tierPrices)
            ->willReturn(false);
        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = [
            'data' => ['status' => false, 'error' => __("We found a duplicate website, tier price or quantity.")]
        ];
        $json->expects($this->once())
            ->method('setJsonData')
            ->with(json_encode($result, JSON_NUMERIC_CHECK))
            ->willReturnSelf();
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($json);
        $this->assertEquals($json, $this->controller->execute());
    }
}
