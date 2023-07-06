<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Model\Price\DuplicatorTierPriceLoader;
use Magento\SharedCatalog\Model\SharedCatalogDuplication;
use Magento\SharedCatalog\Model\SharedCatalogInvalidation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for SharedCatalogDuplication class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogDuplicationTest extends TestCase
{
    /**
     * @var ProductItemManagementInterface|MockObject
     */
    private $productItemManagement;

    /**
     * @var DuplicatorTierPriceLoader|MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var SharedCatalogDuplication
     */
    private $sharedCatalogDuplication;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->productItemManagement = $this->getMockForAbstractClass(
            ProductItemManagementInterface::class
        );

        $this->sharedCatalogInvalidation = $this
            ->getMockBuilder(SharedCatalogInvalidation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->sharedCatalogDuplication = $objectManagerHelper->getObject(
            SharedCatalogDuplication::class,
            [
                'productItemManagement' => $this->productItemManagement,
                'sharedCatalogInvalidation' => $this->sharedCatalogInvalidation
            ]
        );
    }

    /**
     * Unit test for assignProductsToDuplicate().
     *
     * @return void
     */
    public function testAssignProductsToDuplicate()
    {
        $sharedCatalogId = 3;
        $productsSku = ['SKU_1', 'SKU_2'];
        $sharedCatalog = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())->method('checkSharedCatalogExist')
            ->with($sharedCatalogId)->willReturn($sharedCatalog);
        $sharedCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')
            ->willReturn(1);
        $sharedCatalog->expects($this->atLeastOnce())->method('getType')
            ->willReturn(SharedCatalogInterface::TYPE_PUBLIC);
        $this->productItemManagement->expects($this->exactly(2))->method('addItems')
            ->withConsecutive([1, $productsSku], [0, $productsSku])->willReturnSelf();
        $this->sharedCatalogDuplication->assignProductsToDuplicate($sharedCatalogId, $productsSku);
    }

    /**
     * Unit test for assignProductsToDuplicate() with NoSuchEntityException.
     *
     * @return void
     */
    public function testAssignProductsToDuplicateWithNoSuchEntityException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $sharedCatalogId = 3;
        $exception = new NoSuchEntityException();
        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())->method('checkSharedCatalogExist')
            ->with($sharedCatalogId)->willThrowException($exception);
        $this->sharedCatalogDuplication->assignProductsToDuplicate($sharedCatalogId, ['SKU']);
    }

    /**
     * Unit test for assignProductsToDuplicate() with LocalizedException.
     *
     * @return void
     */
    public function testAssignProductsToDuplicateWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $exception = new LocalizedException(__('exception message'));

        $sharedCatalogId = 3;
        $productsSku = ['SKU_1', 'SKU_2'];
        $sharedCatalog = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())->method('checkSharedCatalogExist')
            ->with($sharedCatalogId)->willReturn($sharedCatalog);
        $sharedCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')
            ->willReturn(1);
        $sharedCatalog->expects($this->atLeastOnce())->method('getType')
            ->willReturn(SharedCatalogInterface::TYPE_PUBLIC);
        $this->productItemManagement->expects($this->any())->method('addItems')
            ->with(1, $productsSku)->willThrowException($exception);
        $this->sharedCatalogDuplication->assignProductsToDuplicate($sharedCatalogId, $productsSku);
    }
}
