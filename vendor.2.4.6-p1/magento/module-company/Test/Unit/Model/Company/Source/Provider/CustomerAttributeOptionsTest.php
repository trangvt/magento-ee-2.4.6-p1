<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Company\Source\Provider;

use Magento\Company\Model\Company\Source\Provider\CustomerAttributeOptions;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Eav\Model\Entity\AttributeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerAttributeOptionsTest extends TestCase
{
    /**
     * @var AttributeFactory|MockObject
     */
    private $attributeFactory;

    /**
     * @var CustomerAttributeOptions|MockObject
     */
    private $provider;

    /**
     * @var string
     */
    private $attributeCode = 'gender';

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->attributeFactory = $this->createPartialMock(
            AttributeFactory::class,
            [
                'create',
            ]
        );

        $this->provider = $this->getMockForAbstractClass(
            CustomerAttributeOptions::class,
            [$this->attributeFactory]
        );
    }

    /**
     * @covers \Magento\Company\Model\Company\Source\Provider\CustomerAttributeOptions::loadOptions
     *
     * @return void
     */
    public function testLoadOptions(): void
    {
        $label = 'label';
        $value = 'value';
        $result = [['label' => $label, 'value' => $value]];
        $attribute = $this->createPartialMock(
            Attribute::class,
            [
                'getOptions',
                'loadByCode',
            ]
        );
        $option = $this->createPartialMock(
            Option::class,
            [
                'getLabel',
                'getValue',
            ]
        );
        $this->attributeFactory->expects($this->once())->method('create')->willReturn($attribute);
        $attribute->expects($this->once())
            ->method('loadByCode')
            ->with('customer', $this->attributeCode);
        $attribute->expects($this->once())->method('getOptions')->willReturn([$option]);
        $option->expects($this->once())->method('getLabel')->willReturn($label);
        $option->expects($this->once())->method('getValue')->willReturn($value);
        $this->assertEquals(
            $this->provider->loadOptions($this->attributeCode),
            $result
        );
    }
}
