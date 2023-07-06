<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Observer\DeleteProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteProductTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DeleteProduct|MockObject
     */
    private $deleteProduct;

    /**
     * @var ProductItemRepositoryInterface|MockObject
     */
    private $itemRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->itemRepositoryMock =
            $this->getMockBuilder(ProductItemRepositoryInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->itemRepositoryMock = $this->getMockForAbstractClass(
            ProductItemRepositoryInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['getList', 'getItems', 'delete']
        );
        $this->itemRepositoryMock->method('getList')->willReturn($this->itemRepositoryMock);
        $productItem = $this->getMockForAbstractClass(ProductItemInterface::class);
        $this->itemRepositoryMock->method('getItems')->willReturn([$productItem]);
        $this->searchCriteriaBuilderMock = $this->createPartialMock(
            SearchCriteriaBuilder::class,
            ['create', 'addFilter']
        );
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteria);
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->deleteProduct = $this->objectManagerHelper->getObject(
            DeleteProduct::class,
            [
                'itemRepository' => $this->itemRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->createPartialMock(Product::class, ['getSku']);
        $product->method('getSku')->willReturn('sku1');
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getProduct'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('getProduct')->willReturn($product);
        $observer->method('getEvent')->willReturn($event);
        $result = $this->deleteProduct->execute($observer);
        $this->assertEquals($this->deleteProduct, $result);
    }
}
