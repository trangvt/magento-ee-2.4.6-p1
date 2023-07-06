<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Price;

use Magento\Catalog\Api\Data\TierPriceInterface;
use Magento\Catalog\Api\TierPriceStorageInterface;
use Magento\Catalog\Model\Config\Source\ProductPriceOptionsInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\Price\DuplicatorTierPriceLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for TierPricesLoader.
 */
class DuplicatorTierPriceLoaderTest extends TestCase
{
    /**
     * @var TierPriceStorageInterface|MockObject
     */
    private $tierPriceStorage;

    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private $customerGroupRepository;

    /**
     * @var DuplicatorTierPriceLoader
     */
    private $tierPriceLoader;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tierPriceStorage = $this->getMockBuilder(TierPriceStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerGroupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->tierPriceLoader = $objectManagerHelper->getObject(
            DuplicatorTierPriceLoader::class,
            [
                'tierPriceStorage' => $this->tierPriceStorage,
                'customerGroupRepository' => $this->customerGroupRepository,
            ]
        );
    }

    /**
     * Unit test for load().
     *
     * @param string $priceType
     * @param string $priceTypeValue
     * @param string $valueKey
     * @return void
     * @dataProvider loadDataProvider
     */
    public function testLoad($priceType, $priceTypeValue, $valueKey)
    {
        $sku = 'sku';
        $skus = [$sku];
        $customerGroupId = 1;
        $qty = 2;
        $websiteId = 1;
        $price = 10.00;
        $customerGroupCode = 'Customer Group';
        $tierPrice = $this->getMockBuilder(TierPriceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $tierPrice->expects($this->atLeastOnce())->method('getCustomerGroup')->willReturn($customerGroupCode);
        $tierPrice->expects($this->atLeastOnce())->method('getQuantity')->willReturn($qty);
        $tierPrice->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $tierPrice->expects($this->atLeastOnce())->method('getPriceType')->willReturn($priceType);
        $tierPrice->expects($this->atLeastOnce())->method('getPrice')->willReturn($price);
        $tierPrice->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $this->tierPriceStorage->expects($this->atLeastOnce())->method('get')->with($skus)->willReturn([$tierPrice]);
        $customerGroup = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerGroup->expects($this->atLeastOnce())->method('getCode')->willReturn($customerGroupCode);
        $this->customerGroupRepository->expects($this->atLeastOnce())->method('getById')->with($customerGroupId)
            ->willReturn($customerGroup);
        $result = [
            $sku => [
                [
                    'qty' => $qty,
                    'website_id' => $websiteId,
                    'is_changed' => true,
                    $valueKey => $price,
                    'value_type' => $priceTypeValue
                ]
            ]
        ];

        $this->assertEquals($result, $this->tierPriceLoader->load($skus, $customerGroupId));
    }

    /**
     * DataProvider for testLoad().
     *
     * @return array
     */
    public function loadDataProvider()
    {
        return [
            [TierPriceInterface::PRICE_TYPE_FIXED, ProductPriceOptionsInterface::VALUE_FIXED, 'price'],
            [TierPriceInterface::PRICE_TYPE_DISCOUNT, ProductPriceOptionsInterface::VALUE_PERCENT, 'percentage_value']
        ];
    }
}
