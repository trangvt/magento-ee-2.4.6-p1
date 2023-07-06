<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\QuickCheckout\Model\NoHtmlValidator;
use Magento\Sales\Model\Order\Payment;

/**
 * Refund response handler
 */
class RefundTransactionHandler implements HandlerInterface
{
    /**
     * @var NoHtmlValidator
     */
    private $noHtmlValidator;

    /**
     * @param NoHtmlValidator $noHtmlValidator
     */
    public function __construct(
        NoHtmlValidator $noHtmlValidator
    ) {
        $this->noHtmlValidator = $noHtmlValidator;
    }

    /**
     * Handle transaction ids
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided.');
        }

        if (!$this->validateResponse($response)) {
            throw new \InvalidArgumentException('Invalid response.');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $transactionReference = $response['reference'];
        $transactionId = $response['id'];

        /** @var $payment Payment */
        $payment->setTransactionId($transactionReference);
        $payment->setTransactionAdditionalInfo('transaction_id', $transactionId);
        $payment->setTransactionAdditionalInfo('reference', $transactionReference);

        $payment->setIsTransactionClosed(false);
    }

    /**
     * Validate response
     *
     * @param array $response
     * @return bool
     */
    private function validateResponse(array $response) : bool
    {
        if (empty($response)
            || empty($response['reference'])
            || empty($response['id'])
        ) {
            return false;
        }
        if (!$this->noHtmlValidator->validate($response['reference'])
            || !$this->noHtmlValidator->validate($response['id'])
        ) {
            return false;
        }
        return true;
    }
}