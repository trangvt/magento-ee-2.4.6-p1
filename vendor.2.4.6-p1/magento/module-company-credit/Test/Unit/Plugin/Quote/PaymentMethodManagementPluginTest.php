<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Quote;

use Magento\CompanyCredit\Plugin\Quote\PaymentMethodManagementPlugin;
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
        $this->quoteRepository = $this->createMock(
            CartRepositoryInterface::class
        );
        $this->methodSpecificationFactory = $this->createMock(
            SpecificationFactory::class
        );
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
     * Test aroundGetList method.
     */
    public function testAroundGetList(): void
    {
        $cartId = 1;
        $quote = $this->createMock(
            Quote::class
        );

        $this->quoteRepository->expects(static::once())
            ->method('get')
            ->willReturn($quote);
        $specification = $this->createMock(
            Composite::class
        );
        $method = $this->createMock(
            MethodInterface::class
        );
        $specification->expects($this->any())
            ->method('isApplicable')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->methodSpecificationFactory->expects(static::once())
            ->method('create')
            ->willReturn($specification);
        $subject = $this->getMockBuilder(PaymentMethodManagementInterface::class)
            ->getMockForAbstractClass();
        $proceed = function () use ($method) {
            return [$method, $method];
        };

        $this->assertCount(1, $this->paymentMethodManagementPlugin->aroundGetList($subject, $proceed, $cartId));
    }
}
