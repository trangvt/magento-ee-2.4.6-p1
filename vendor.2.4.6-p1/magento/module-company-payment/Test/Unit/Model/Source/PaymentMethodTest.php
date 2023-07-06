<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\CompanyPayment\Test\Unit\Model\Source;

use Magento\CompanyPayment\Model\Source\PaymentMethod;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    /**
     * @var PaymentMethodListInterface|MockObject
     */
    private $paymentMethodList;

    /**
     * @var StoreResolverInterface|MockObject
     */
    private $storeResolver;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var PaymentMethod
     */
    private $paymentMethod;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->paymentMethodList = $this->createMock(
            PaymentMethodListInterface::class
        );
        $this->storeResolver = $this->createMock(
            StoreResolverInterface::class
        );
        $this->appState = $this->createMock(
            State::class
        );

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->paymentMethod = $objectManager->getObject(
            PaymentMethod::class,
            [
                'paymentMethodList' => $this->paymentMethodList,
                'storeResolver' => $this->storeResolver,
                'appState' => $this->appState,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    /**
     * Test for method toOptionArray.
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $storeId = 1;
        $paymentMethodNames = ['paymentMethod3', 'paymentMethod1', 'paymentMethod1'];
        $paymentMethodCodes = ['PM1', 'PM2', 'PM3'];

        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();

        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $paymentMethod1 = $this->createMock(
            PaymentMethodInterface::class
        );
        $paymentMethod2 = $this->createMock(
            PaymentMethodInterface::class
        );
        $paymentMethod3 = $this->createMock(
            PaymentMethodInterface::class
        );
        $this->appState->expects($this->once())
            ->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $paymentMethod1->expects($this->atLeastOnce())->method('getTitle')->willReturn($paymentMethodNames[0]);
        $paymentMethod2->expects($this->atLeastOnce())->method('getTitle')->willReturn($paymentMethodNames[1]);
        $paymentMethod3->expects($this->atLeastOnce())->method('getTitle')->willReturn($paymentMethodNames[2]);
        $paymentMethod1->expects($this->exactly(2))->method('getCode')->willReturn($paymentMethodCodes[0]);
        $paymentMethod2->expects($this->exactly(3))->method('getCode')->willReturn($paymentMethodCodes[1]);
        $paymentMethod3->expects($this->exactly(3))->method('getCode')->willReturn($paymentMethodCodes[2]);
        $paymentMethod1->expects($this->once())->method('getIsActive')->willReturn(false);
        $paymentMethod2->expects($this->once())->method('getIsActive')->willReturn(true);
        $paymentMethod3->expects($this->once())->method('getIsActive')->willReturn(true);
        $this->paymentMethodList->expects($this->once())->method('getList')
            ->with($storeId)->willReturn([$paymentMethod1, $paymentMethod2, $paymentMethod3]);
        $this->assertEquals(
            [
                ['value' => $paymentMethodCodes[2], 'label' => $paymentMethodNames[2] . ' ' . $paymentMethodCodes[2]],
                ['value' => $paymentMethodCodes[1], 'label' => $paymentMethodNames[1] . ' ' . $paymentMethodCodes[1]],
                ['value' => $paymentMethodCodes[0], 'label' => $paymentMethodNames[0] . ' (disabled)'],
            ],
            $this->paymentMethod->toOptionArray()
        );
    }
}
