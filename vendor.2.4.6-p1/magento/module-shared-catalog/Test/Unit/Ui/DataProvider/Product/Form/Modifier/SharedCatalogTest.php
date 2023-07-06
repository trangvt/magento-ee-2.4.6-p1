<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\ProductSharedCatalogsLoader;
use Magento\SharedCatalog\Ui\DataProvider\Product\Form\Modifier\SharedCatalog;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SharedCatalogTest extends TestCase
{
    /**
     * @var LocatorInterface|MockObject
     */
    private $locator;

    /**
     * @var ProductSharedCatalogsLoader|MockObject
     */
    private $productSharedCatalogsLoader;

    /**
     * @var SharedCatalog
     */
    private $sharedCatalog;

    protected function setUp(): void
    {
        $this->locator = $this->getMockForAbstractClass(LocatorInterface::class);
        $this->productSharedCatalogsLoader = $this->createMock(ProductSharedCatalogsLoader::class);

        $this->sharedCatalog = (new ObjectManager($this))->getObject(SharedCatalog::class, [
            'locator' => $this->locator,
            'productSharedCatalogsLoader' => $this->productSharedCatalogsLoader,
        ]);
    }

    public function testModifyData()
    {
        $productId = 2;
        $sku = 'sku';
        $sharedCatalogIdFirst = 3;
        $sharedCatalogIdSecond = 4;

        $product = $this->getMockForAbstractClass(ProductInterface::class);
        $product->expects($this->atLeastOnce())->method('getId')->willReturn($productId);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);

        $sharedCatalogFirst = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $sharedCatalogFirst->expects($this->atLeastOnce())->method('getId')->willReturn($sharedCatalogIdFirst);
        $sharedCatalogSecond = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $sharedCatalogSecond->expects($this->atLeastOnce())->method('getId')->willReturn($sharedCatalogIdSecond);
        $sharedCatalogs = [$sharedCatalogFirst, $sharedCatalogSecond];

        $this->locator->expects($this->once())->method('getProduct')->willReturn($product);
        $this->productSharedCatalogsLoader
            ->expects($this->once())
            ->method('getAssignedSharedCatalogs')
            ->with($sku)
            ->willReturn($sharedCatalogs);

        $expectedResult = [
            $productId => [
                SharedCatalog::DATA_SOURCE_DEFAULT => [
                    'shared_catalog' => [
                        $sharedCatalogIdFirst,
                        $sharedCatalogIdSecond,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedResult, $this->sharedCatalog->modifyData([]));
    }

    public function testModifyMeta()
    {
        $data = ['data'];
        $this->assertEquals($data, $this->sharedCatalog->modifyMeta($data));
    }
}
