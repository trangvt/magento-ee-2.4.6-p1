<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteAsyncOrder\Test\Unit\Model;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\AsyncOrder\Api\AsyncPaymentInformationCustomerPublisherInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\NegotiableQuoteAsyncOrder\Model\AsyncPaymentInformationManagementPublisher;
use Magento\NegotiableQuote\Api\PaymentInformationManagementInterface;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AsyncPaymentInformationManagementPublisherTest extends TestCase
{
    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    /**
     * @var AsyncPaymentInformationCustomerPublisherInterface
     */
    private $asyncPaymentInformationCustomerPublisher;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var CustomerCartValidator
     */
    private $validator;

    /**
     * @var OrderManagement
     */
    private $orderManagement;

    /**
     * @var AsyncPaymentInformationManagementPublisher
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->paymentInformationManagement = $this->getMockForAbstractClass(
            PaymentInformationManagementInterface::class
        );
        $this->asyncPaymentInformationCustomerPublisher = $this->getMockForAbstractClass(
            AsyncPaymentInformationCustomerPublisherInterface::class
        );
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->validator = $this->createMock(CustomerCartValidator::class);
        $this->orderManagement = $this->createMock(OrderManagement::class);

        $this->model = $objectManager->getObject(
            AsyncPaymentInformationManagementPublisher::class,
            [
                'paymentInformationManagement' => $this->paymentInformationManagement,
                'asyncPaymentInformationCustomerPublisher' => $this->asyncPaymentInformationCustomerPublisher,
                'deploymentConfig' => $this->deploymentConfig,
                'validator' => $this->validator,
                'orderManagement' => $this->orderManagement
            ]
        );
    }

    public function testPublishAsyncDisabled(): void
    {
        $orderId = 999;
        $cartId = '101';
        $paymentMethod = $this->getMockForAbstractClass(PaymentInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            OrderManagement::ASYNC_ORDER_OPTION_PATH
        )->willReturn(false);

        $this->orderManagement->expects(
            $this->never()
        )->method('getPaymentMethodsForSynchronousMode');

        $this->paymentInformationManagement->expects(
            $this->once()
        )->method('savePaymentInformationAndPlaceOrder')->with(
            $cartId,
            $paymentMethod,
            $billingAddress
        )->willReturn($orderId);

        $this->assertEquals(
            $orderId,
            $this->model->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress)
        );
    }

    public function testPublishAsyncForPaymentMethodsForSynchronousMode(): void
    {
        $orderId = 999;
        $cartId = '101';
        $paymentMethod = $this->getMockForAbstractClass(PaymentInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);
        $paymentMethodsForSynchronousMode = [
            'some payment method 1',
            'some payment method 2'
        ];
        $paymentMethodType = 'some payment method 1';

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            OrderManagement::ASYNC_ORDER_OPTION_PATH
        )->willReturn(true);

        $paymentMethod->expects(
            $this->once()
        )->method('getMethod')->willReturn($paymentMethodType);

        $this->orderManagement->expects(
            $this->once()
        )->method('getPaymentMethodsForSynchronousMode')->willReturn($paymentMethodsForSynchronousMode);

        $this->paymentInformationManagement->expects(
            $this->once()
        )->method('savePaymentInformationAndPlaceOrder')->with(
            $cartId,
            $paymentMethod,
            $billingAddress
        )->willReturn($orderId);

        $this->assertEquals(
            $orderId,
            $this->model->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress)
        );
    }

    public function testPublishAsyncEnabled(): void
    {
        $orderId = 999;
        $cartId = '101';
        $paymentMethod = $this->getMockForAbstractClass(PaymentInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);
        $paymentMethodsForSynchronousMode = [
            'some payment method 1',
            'some payment method 2'
        ];
        $paymentMethodType = 'checkmo';

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            OrderManagement::ASYNC_ORDER_OPTION_PATH
        )->willReturn(true);

        $paymentMethod->expects(
            $this->once()
        )->method('getMethod')->willReturn($paymentMethodType);

        $this->orderManagement->expects(
            $this->once()
        )->method('getPaymentMethodsForSynchronousMode')->willReturn($paymentMethodsForSynchronousMode);

        $this->paymentInformationManagement->expects(
            $this->never()
        )->method('savePaymentInformationAndPlaceOrder');

        $this->validator->expects(
            $this->once()
        )->method('validate')->with(
            $cartId
        );

        $this->asyncPaymentInformationCustomerPublisher->expects(
            $this->once()
        )->method('savePaymentInformationAndPlaceOrder')->with(
            $cartId,
            $paymentMethod,
            $billingAddress
        )->willReturn($orderId);

        $this->assertEquals(
            $orderId,
            $this->model->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress)
        );
    }
}
