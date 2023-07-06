<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteAsyncOrder\Model;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\AsyncOrder\Api\AsyncPaymentInformationCustomerPublisherInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\NegotiableQuoteAsyncOrder\Api\AsyncPaymentInformationManagementInterface;
use Magento\NegotiableQuote\Api\PaymentInformationManagementInterface;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\Framework\App\DeploymentConfig;

class AsyncPaymentInformationManagementPublisher implements AsyncPaymentInformationManagementInterface
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
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param AsyncPaymentInformationCustomerPublisherInterface $asyncPaymentInformationCustomerPublisher
     * @param DeploymentConfig $deploymentConfig
     * @param CustomerCartValidator $validator
     * @param OrderManagement $orderManagement
     */
    public function __construct(
        PaymentInformationManagementInterface $paymentInformationManagement,
        AsyncPaymentInformationCustomerPublisherInterface $asyncPaymentInformationCustomerPublisher,
        DeploymentConfig $deploymentConfig,
        CustomerCartValidator $validator,
        OrderManagement $orderManagement
    ) {
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->asyncPaymentInformationCustomerPublisher = $asyncPaymentInformationCustomerPublisher;
        $this->deploymentConfig = $deploymentConfig;
        $this->validator = $validator;
        $this->orderManagement = $orderManagement;
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformationAndPlaceOrder(
        string $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        if (!$this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            return $this->paymentInformationManagement
                ->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
        }
        if (in_array($paymentMethod->getMethod(), $this->orderManagement->getPaymentMethodsForSynchronousMode())) {
            return $this->paymentInformationManagement
                ->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
        }
        $this->validator->validate($cartId);
        return $this->asyncPaymentInformationCustomerPublisher
            ->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
    }
}
