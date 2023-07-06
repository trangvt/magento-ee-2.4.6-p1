<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Modifier\PriceByType;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\Form\Storage\PriceCalculator;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Ui\DataProvider\Modifier\PriceByType\Simple;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\SharedCatalog\Ui\DataProvider\Modifier\PriceByType\Simple class.
 */
class SimpleTest extends TestCase
{
    /**
     * @var Simple
     */
    private $modifier;

    /**
     * @var PriceCalculator|MockObject
     */
    private $calculator;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->calculator = $this->getMockBuilder(PriceCalculator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->modifier = $objectManager->getObject(
            Simple::class,
            [
                'priceCalculator' => $this->calculator,
                'request' => $this->request,
            ]
        );
    }

    /**
     * Test modifyData method.
     *
     * @return void
     */
    public function testModifyData()
    {
        $data = [
            'entity_id' => 1,
            'price' => 150,
            'website_id' => 0,
            'sku' => 'sku_1'
        ];
        $result = [
            'entity_id' => 1,
            'new_price' => 100,
            'price' => 150,
            'website_id' => 0,
            'sku' => 'sku_1'
        ];
        $this->request->expects($this->once())
            ->method('getParam')
            ->with(UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY)
            ->willReturn('some_key');
        $this->calculator->expects($this->once())
            ->method('calculateNewPriceForProduct')
            ->with('some_key', 'sku_1', 150, 0)
            ->willReturn(100);

        $this->assertEquals($result, $this->modifier->modifyData($data));
    }

    /**
     * Test modifyData method without entity_id in data.
     *
     * @return void
     */
    public function testModifyDataWitoutItem()
    {
        $data = [
            'price' => 150,
            'website_id' => 0,
        ];
        $this->calculator->expects($this->never())->method('calculateNewPriceForProduct');

        $this->assertEquals($data, $this->modifier->modifyData($data));
    }

    /**
     * Test modifyMeta method.
     *
     * @return void
     */
    public function testModifyMeta()
    {
        $data = ['modifyMeta'];
        $this->assertEquals($data, $this->modifier->modifyMeta($data));
    }
}
