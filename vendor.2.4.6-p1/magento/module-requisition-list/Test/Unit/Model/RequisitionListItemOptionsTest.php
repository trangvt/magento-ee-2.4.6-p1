<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Model\RequisitionListItemOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for RequisitionListItemOptions model.
 */
class RequisitionListItemOptionsTest extends TestCase
{
    /**
     * @var ProductInterface|MockObject
     */
    private $product;

    /**
     * @var OptionInterface|MockObject
     */
    private $option;

    /**
     * @var string
     */
    private $optionCode = 'option_code';

    /**
     * @var RequisitionListItemOptions
     */
    private $requisitionListItemOptions;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->option = $this
            ->getMockBuilder(OptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->requisitionListItemOptions = $objectManager->getObject(
            RequisitionListItemOptions::class,
            [
                '_data' => [
                    'product' => $this->product,
                    'options' => [$this->optionCode => $this->option],
                ],
            ]
        );
    }

    /**
     * Test for getProduct method.
     *
     * @return void
     */
    public function testGetProduct()
    {
        $this->assertEquals($this->product, $this->requisitionListItemOptions->getProduct());
    }

    /**
     * Test for getOptionByCode method.
     *
     * @return void
     */
    public function testGetOptionByCode()
    {
        $this->assertEquals($this->option, $this->requisitionListItemOptions->getOptionByCode($this->optionCode));
    }

    /**
     * Test for getFileDownloadParams method.
     *
     * @return void
     */
    public function testGetFileDownloadParams()
    {
        $this->assertNull($this->requisitionListItemOptions->getFileDownloadParams());
    }
}
