<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Copier;
use Magento\Framework\Exception\LocalizedException;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Model\ProductSharedCatalogsLoader;
use Magento\SharedCatalog\Plugin\Catalog\Model\Product\AssignSharedCatalogOnDuplicateProductPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for AssignSharedCatalogOnDuplicateProductPlugin plugin.
 */
class AssignSharedCatalogOnDuplicateProductPluginTest extends TestCase
{
    /**
     * @var AssignSharedCatalogOnDuplicateProductPlugin|MockObject
     */
    private $assignSharedCatalogOnDuplicateProductPlugin;

    /**
     * @var ProductSharedCatalogsLoader|MockObject
     */
    private $productSharedCatalogsLoaderMock;

    /**
     * @var ProductManagementInterface|MockObject
     */
    private $sharedCatalogProductManagement;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->productSharedCatalogsLoaderMock = $this->getMockBuilder(
            ProductSharedCatalogsLoader::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogProductManagement = $this->getMockBuilder(
            ProductManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assignSharedCatalogOnDuplicateProductPlugin = new AssignSharedCatalogOnDuplicateProductPlugin(
            $this->productSharedCatalogsLoaderMock,
            $this->sharedCatalogProductManagement,
            $this->loggerMock
        );
    }

    /**
     * Test for aroundCopy() method.
     *
     * @return void
     */
    public function testAfterCopy()
    {
        $sku = 'sku';
        $sharedCatalogId = 1;

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCopyMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $sharedCatalogMock = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productSharedCatalogsLoaderMock->expects($this->once())->method('getAssignedSharedCatalogs')
            ->willReturn([$sharedCatalogMock]);
        $sharedCatalogMock->expects($this->once())
            ->method('getId')
            ->willReturn($sharedCatalogId);

        $exception = new LocalizedException(__('test'));
        $this->sharedCatalogProductManagement->expects($this->once())
            ->method('assignProducts')
            ->with($sharedCatalogId, [$productCopyMock])
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())->method('critical')->with($exception);

        $subject = $this->getMockBuilder(Copier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(
            $productMock,
            $this->assignSharedCatalogOnDuplicateProductPlugin->afterCopy($subject, $productCopyMock, $productMock)
        );
    }
}
