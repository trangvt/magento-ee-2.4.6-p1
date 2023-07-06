<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaypalPurchaseOrder\Plugin\Paypal\Controller\Express;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\PurchaseOrder\Model\Access\Validator\PlaceOrder as AccessValidator;

/**
 * Plugin for controllers derived from Magento\Paypal\Controller\Express\AbstractExpress.
 */
class AbstractExpress
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var AccessValidator
     */
    private $accessValidator;

    /**
     * AbstractExpress constructor.
     *
     * @param RequestInterface $request
     * @param Session $checkoutSession
     * @param AccessValidator $accessValidator
     */
    public function __construct(
        RequestInterface $request,
        Session $checkoutSession,
        AccessValidator $accessValidator
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->accessValidator = $accessValidator;
    }

    /**
     * Checks the purchaseOrderId param placed via request and replace it from session if it is not set.
     * See \Magento\PaypalPurchaseOrder\Plugin\Paypal\Model\Api\NvpPlugin for session-stored purchase order ID.
     * See \Magento\PurchaseOrder\Plugin\Checkout\Model\SessionPlugin where the PO quote replaces active quote.
     *
     * @param \Magento\Paypal\Controller\Express\AbstractExpress $subject
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        \Magento\Paypal\Controller\Express\AbstractExpress $subject
    ) {
        $purchaseOrderId = $this->request->getParam('purchaseOrderId')
            ?: $this->checkoutSession->getData('purchaseOrder_' . $this->request->getParam('token'));
        if ($purchaseOrderId && $this->accessValidator->validatePlaceOrder((int)$purchaseOrderId)) {
            $this->request->setParams(['purchaseOrderId' => $purchaseOrderId]);
        }
    }
}
