<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Modifier;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Ui\DataProvider\Modifier\PriceByType;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for PriceByType modifier.
 */
class PriceByTypeTest extends TestCase
{
    /**
     * @var PriceByType
     */
    private $modifier;

    /**
     * @var ModifierInterface|MockObject
     */
    private $complexModifier;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->complexModifier = $this->getMockBuilder(ModifierInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->modifier = $objectManager->getObject(
            PriceByType::class,
            [
                'modifiers' => ['simple' => $this->complexModifier],
            ]
        );
    }

    /**
     * Test modifyData method without items.
     * @return void
     */
    public function testModifyDataWithoutItems()
    {
        $data = ['items' => null];
        $this->complexModifier->expects($this->never())->method('modifyData');
        $this->assertEquals($data, $this->modifier->modifyData($data));
    }

    /**
     * Test modifyData method with simple product items.
     * @return void
     */
    public function testModifyDataWithSimpleItems()
    {
        $data = ['items' => [['type_id' => 'simple']]];
        $dataExpect = ['items' => [['type_id' => 'simple', 'website_id' => 0]]];
        $this->complexModifier->expects($this->once())->method('modifyData')->willReturnArgument(0);
        $this->assertEquals($dataExpect, $this->modifier->modifyData($data));
    }

    /**
     * Test modifyData method without simple items.
     * @return void
     */
    public function testModifyDataWithOtherItems()
    {
        $data = ['items' => [['type_id' => 'other']]];
        $dataExpect = ['items' => [['type_id' => 'other', 'website_id' => 0]]];
        $this->complexModifier->expects($this->once())->method('modifyData')->willReturnArgument(0);
        $this->assertEquals($dataExpect, $this->modifier->modifyData($data));
    }

    /**
     * Test modifyMeta method.
     * @return void
     */
    public function testModifyMeta()
    {
        $data = ['modifyMeta'];
        $this->complexModifier->expects($this->once())->method('modifyMeta')->willReturnArgument(0);
        $this->assertEquals($data, $this->modifier->modifyMeta($data));
    }
}
