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
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Product\Price\Adjust;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ProductItemTierPriceValidator;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Adjust controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AdjustTest extends TestCase
{
    /**
     * @var Adjust
     */
    private $controller;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Wizard|MockObject
     */
    private $wizardStorage;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var ProductItemTierPriceValidator|MockObject
     */
    private $productItemTierPriceValidator;

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
        $this->resultJsonFactory = $this
            ->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productItemTierPriceValidator = $this
            ->getMockBuilder(ProductItemTierPriceValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filter = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionFactory = $this
            ->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);
        $filter->expects($this->once())
            ->method('getCollection')->with($this->collection)->willReturn($this->collection);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Adjust::class,
            [
                '_request' => $this->request,
                'filter' => $filter,
                'collectionFactory' => $collectionFactory,
                'resultJsonFactory' => $this->resultJsonFactory,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'productItemTierPriceValidator' => $this->productItemTierPriceValidator,
            ]
        );
    }

    /**
     * Test for method Execute without price.
     *
     * @return void
     */
    public function testExecuteWithoutPrice()
    {
        $this->request->expects($this->once())->method('getParam')->with('value')->willReturn(1000);
        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory->expects($this->never())->method('create')->willReturn($this->wizardStorage);
        $result = ['data' => ['status' => false, 'error' => __("Discount value cannot be outside the range 0-100")]];
        $json->expects($this->once())->method('setJsonData')
            ->with(json_encode($result, JSON_NUMERIC_CHECK))->willReturnSelf();
        $this->resultJsonFactory->expects($this->once())
            ->method('create')->willReturn($json);
        $this->assertEquals($json, $this->controller->execute());
    }

    /**
     * Test for method Execute with price.
     *
     * @return void
     */
    public function testExecuteWithPrice()
    {
        $productSku = 'ProductSKU';
        $configureKey = 'test';
        $websiteId = 2;
        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(['value'], ['configure_key'], ['website_id'])
            ->willReturnOnConsecutiveCalls(20, $configureKey, $websiteId);
        $this->wizardStorageFactory->expects($this->once())->method('create')
            ->with(['key' => $configureKey])->willReturn($this->wizardStorage);
        $this->collection->expects($this->once())->method('addFieldToSelect')->with('price')->willReturnSelf();
        $this->collection->expects($this->once())->method('addFieldToFilter')
            ->with('type_id', ['nin' => []])->willReturnSelf();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $this->wizardStorage->expects($this->atLeastOnce())
            ->method('getProductPrices')
            ->with($productSku)
            ->willReturn([]);
        $this->collection->expects($this->once())
            ->method('getIterator')->willReturn(new \ArrayIterator([$product, $product]));
        $product->expects($this->atLeastOnce())
            ->method('getTypeId')->willReturnOnConsecutiveCalls(
                Type::TYPE_CODE,
                \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            );
        $this->productItemTierPriceValidator->expects($this->atLeastOnce())->method('isTierPriceApplicable')
            ->withConsecutive(
                [Type::TYPE_CODE],
                [\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE]
            )
            ->willReturnOnConsecutiveCalls(true, false);
        $this->productItemTierPriceValidator->expects($this->atLeastOnce())
            ->method('canChangePrice')
            ->with([], $websiteId)
            ->willReturnOnConsecutiveCalls(true, false);
        $product->expects($this->once())->method('getPrice')->willReturn(10);
        $this->wizardStorage->expects($this->once())
            ->method('setTierPrices')
            ->with(
                [
                    $productSku => [
                        [
                            'qty' => 1,
                            'price' => 8.0,
                            'value_type' => 'fixed',
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
        $json->expects($this->once())->method('setJsonData')
            ->with(json_encode($result, JSON_NUMERIC_CHECK))->willReturnSelf();
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($json);
        $this->assertEquals($json, $this->controller->execute());
    }
}
