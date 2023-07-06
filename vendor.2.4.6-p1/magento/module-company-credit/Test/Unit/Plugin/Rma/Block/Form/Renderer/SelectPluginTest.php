<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Rma\Block\Form\Renderer;

use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Plugin\Rma\Block\Form\Renderer\SelectPlugin;
use Magento\Eav\Model\Attribute;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Form\Renderer\Select;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for select payment methods.
 */
class SelectPluginTest extends TestCase
{
    /**
     * @var Registry|MockObject
     */
    private $coreRegistry;

    /**
     * @var SelectPlugin
     */
    private $selectPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->coreRegistry = $this->createMock(
            Registry::class
        );

        $objectManager = new ObjectManager($this);
        $this->selectPlugin = $objectManager->getObject(
            SelectPlugin::class,
            [
                'coreRegistry' => $this->coreRegistry,
            ]
        );
    }

    /**
     * Test for afterGetOptions method.
     *
     * @return void
     */
    public function testAfterGetOptions()
    {
        $result = [
            ['label' => 'Option 1', 'value' => 1],
            ['label' => 'Option 2', 'value' => 2],
            ['label' => 'Store Credit', 'value' => 3],
        ];
        $select = $this->createMock(Select::class);
        $attribute = $this->createMock(Attribute::class);
        $select->expects($this->once())->method('getAttributeObject')->willReturn($attribute);
        $attribute->expects($this->once())->method('getAttributeCode')->willReturn('resolution');
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $this->coreRegistry->expects($this->once())->method('registry')->with('current_order')->willReturn($order);
        $order->expects($this->once())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $this->assertEquals(array_slice($result, 0, 2), $this->selectPlugin->afterGetOptions($select, $result));
    }
}
