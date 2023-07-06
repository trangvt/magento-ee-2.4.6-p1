<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\ProductItemTierPriceValidator;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * ProductItemTierPriceValidator unit test.
 */
class ProductItemTierPriceValidatorTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var ProductItemTierPriceValidator
     */
    private $productItemTierPriceValidator;

    /**
     * Set up.
     *
     * @return void.
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->productItemTierPriceValidator = $objectManager->getObject(
            ProductItemTierPriceValidator::class,
            [
                'scopeConfig' => $this->scopeConfig,
                'allowedProductTypes' => ['simple', 'bundle'],
            ]
        );
    }

    /**
     * Test validateDuplicates.
     *
     * @param array $tierPrices
     * @param bool $validationResult
     * @return void
     * @dataProvider validateDuplicatesDataProvider
     */
    public function testValidateDuplicates(array $tierPrices, $validationResult)
    {
        $this->assertEquals($validationResult, $this->productItemTierPriceValidator->validateDuplicates($tierPrices));
    }

    /**
     * DataProvider validateDuplicates.
     *
     * @return array
     */
    public function validateDuplicatesDataProvider()
    {
        return [
            [
                [],
                true
            ],
            [
                [
                    ['delete' => true],
                    ['qty' => 1, 'website_id' => 1],
                    ['qty' => 1, 'website_id' => 1]
                ],
                false
            ],
            [
                [
                    ['delete' => true],
                    ['qty' => 1, 'website_id' => 1],
                    ['qty' => 1, 'website_id' => 0]
                ],
                false
            ],
        ];
    }

    /**
     * Test isTierPriceApplicable method.
     *
     * @param bool $expectedResult
     * @param string $productType
     * @return void
     * @dataProvider isTierPriceApplicableDataProvider
     */
    public function testIsTierPriceApplicable($expectedResult, $productType)
    {
        $this->assertEquals(
            $expectedResult,
            $this->productItemTierPriceValidator->isTierPriceApplicable($productType)
        );
    }

    /**
     * Test for canChangePrice method.
     *
     * @return void
     */
    public function testCanChangePrice()
    {
        $websiteId = null;
        $prices = [0 => ['prices_data0'], 1 => ['prices_data1']];
        $this->scopeConfig->expects($this->once())
            ->method('getValue')->with('catalog/price/scope', 'store')
            ->willReturn(Store::PRICE_SCOPE_GLOBAL);
        $this->assertTrue($this->productItemTierPriceValidator->canChangePrice($prices, $websiteId));
    }

    /**
     * Data provider for isTierPriceApplicable method.
     *
     * @return array
     */
    public function isTierPriceApplicableDataProvider()
    {
        return [
            [true, 'simple'],
            [true, 'bundle'],
            [false, 'configurable'],
            [false, 'virtual'],
            [false, 'giftcard'],
        ];
    }
}
