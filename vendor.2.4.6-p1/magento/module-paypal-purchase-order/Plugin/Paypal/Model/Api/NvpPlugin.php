<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaypalPurchaseOrder\Plugin\Paypal\Model\Api;

use Magento\PurchaseOrder\Model\Access\Validator\PlaceOrder as AccessValidator;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;

/**
 * Plugin for PayPal API to persist purchase order ID by token.
 */
class NvpPlugin
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
     * Plugin constructor.
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
     * Storing purchaseOrderId for PayPal checkout by token.
     * See \Magento\PaypalPurchaseOrder\Plugin\Paypal\Controller\Express\AbstractExpress where it is used.
     *
     * @param \Magento\Paypal\Model\Api\Nvp $subject
     * @param \Closure $proceed
     * @param string $key
     * @param null $index
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetData(
        \Magento\Paypal\Model\Api\Nvp $subject,
        \Closure $proceed,
        string $key = '',
        $index = null
    ) {
        if ($key === 'token') {
            $token = $proceed($key, $index);
            $purchaseOrderId = $this->request->getParam('purchaseOrderId');
            if ($purchaseOrderId && $this->accessValidator->validatePlaceOrder((int)$purchaseOrderId)) {
                $this->checkoutSession->setData('purchaseOrder_' . $token, $purchaseOrderId);
            }
            return $token;
        } else {
            return $proceed($key, $index);
        }
    }
}
