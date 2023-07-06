<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyPayment\Test\Unit\Plugin\Quote;

use Magento\CompanyPayment\Plugin\Quote\PaymentMethodManagementPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Checks\Composite;
use Magento\Payment\Model\Checks\SpecificationFactory;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentMethodManagementPluginTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var SpecificationFactory|MockObject
     */
    private $methodSpecificationFactory;

    /**
     * @var PaymentMethodManagementPlugin
     */
    private $paymentMethodManagementPlugin;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $quote = $this->createMock(Quote::class);
        $this->quoteRepository
            ->method('get')
            ->willReturn($quote);
        $this->methodSpecificationFactory = $this->createPartialMock(
            SpecificationFactory::class,
            ['create']
        );
        $specification = $this->createMock(Composite::class);
        $specification->expects($this->any())
            ->method('isApplicable')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->methodSpecificationFactory
            ->method('create')
            ->willReturn($specification);

        $objectManager = new ObjectManager($this);
        $this->paymentMethodManagementPlugin = $objectManager->getObject(
            PaymentMethodManagementPlugin::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'methodSpecificationFactory' => $this->methodSpecificationFactory
            ]
        );
    }

    /**
     * Test aroundGetList.
     *
     * @return void
     */
    public function testAroundGetList(): void
    {
        $paymentMethodManagement = $this->getMockForAbstractClass(PaymentMethodManagementInterface::class);
        $paymentMethod = $this->getMockForAbstractClass(MethodInterface::class);
        $additionalPaymentMethod = $this->getMockForAbstractClass(MethodInterface::class);
        $closure = function () use ($paymentMethod, $additionalPaymentMethod) {
            return [
                $paymentMethod,
                $additionalPaymentMethod,
            ];
        };

        $result = $this->paymentMethodManagementPlugin->aroundGetList(
            $paymentMethodManagement,
            $closure,
            1
        );
        $expectedResult = ['1' => $additionalPaymentMethod];

        $this->assertEquals($expectedResult, $result);
    }
}
