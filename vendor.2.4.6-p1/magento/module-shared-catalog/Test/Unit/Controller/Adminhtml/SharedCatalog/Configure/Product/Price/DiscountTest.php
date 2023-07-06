<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure\Product\Price;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Product\Price\Discount;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ProductItemTierPriceValidator;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Discount controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DiscountTest extends TestCase
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
     * @var Filter|MockObject
     */
    private $filter;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Collection|MockObject
     */
    private $productCollection;

    /**
     * @var Discount
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
        $this->productItemTierPriceValidator = $this->getMockBuilder(
            ProductItemTierPriceValidator::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->filter = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultJsonFactory = $this
            ->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($this->productCollection);
        $this->filter->expects($this->once())
            ->method('getCollection')
            ->with($this->productCollection)
            ->willReturn($this->productCollection);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Discount::class,
            [
                '_request' => $this->request,
                'productItemTierPriceValidator' => $this->productItemTierPriceValidator,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'filter' => $this->filter,
                'collectionFactory' => $this->collectionFactory,
                'resultJsonFactory' => $this->resultJsonFactory,
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
        $productSku = 'ProductSKU';
        $price = 10;
        $websiteId = 2;
        $storage = $this
            ->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(['value'], ['configure_key'], ['website_id'])
            ->willReturnOnConsecutiveCalls($price, 'configure_key', $websiteId);
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => 'configure_key'])
            ->willReturn($storage);
        $this->productCollection->expects($this->once())->method('addFieldToSelect')->with('price')->willReturnSelf();
        $this->productCollection->expects($this->once())
            ->method('getIterator')->willReturn(new \ArrayIterator([$product, $product]));
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $storage->expects($this->atLeastOnce())
            ->method('getProductPrices')
            ->with($productSku)
            ->willReturn([]);
        $product->expects($this->exactly(2))
            ->method('getTypeId')
            ->willReturnOnConsecutiveCalls(
                Type::TYPE_CODE,
                \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            );
        $this->productItemTierPriceValidator->expects($this->exactly(2))->method('isTierPriceApplicable')
            ->withConsecutive(
                [Type::TYPE_CODE],
                [\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE]
            )
            ->willReturnOnConsecutiveCalls(true, false);
        $this->productItemTierPriceValidator->expects($this->atLeastOnce())
            ->method('canChangePrice')
            ->with([], $websiteId)
            ->willReturnOnConsecutiveCalls(true, false);
        $storage->expects($this->once())
            ->method('setTierPrices')
            ->with(
                [
                    $productSku => [
                        [
                            'qty' => 1,
                            'percentage_value' => $price,
                            'value_type' => 'percent',
                            'website_id' => $websiteId,
                            'is_changed' => true,
                        ]
                    ],
                ]
            );
        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = ['data' => ['status' => true]];
        $json->expects($this->once())
            ->method('setJsonData')
            ->with(json_encode($result, JSON_NUMERIC_CHECK))
            ->willReturnSelf();
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($json);
        $this->assertEquals($json, $this->controller->execute());
    }

    /**
     * Test Execute method with negative price.
     *
     * @return void
     */
    public function testExecuteWithInvalidPrice()
    {
        $price = -15;
        $storage = $this
            ->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->once())->method('getParam')->with('value')->willReturn($price);
        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory->expects($this->never())->method('create')->willReturn($storage);
        $result = ['data' => ['status' => false, 'error' => __("Discount value cannot be outside the range 0-100")]];
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
