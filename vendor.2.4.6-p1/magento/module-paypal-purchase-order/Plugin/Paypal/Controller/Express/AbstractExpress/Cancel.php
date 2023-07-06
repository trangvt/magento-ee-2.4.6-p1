<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaypalPurchaseOrder\Plugin\Paypal\Controller\Express\AbstractExpress;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Paypal\Controller\Express\AbstractExpress\Cancel as PaypalCancel;
use Magento\PurchaseOrder\Model\Access\Validator\PlaceOrder as AccessValidator;

/**
 * Plugin for PayPal Express cancel controller.
 */
class Cancel
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AccessValidator
     */
    private $accessValidator;

    /**
     * Cancel plugin constructor.
     *
     * @param RequestInterface $request
     * @param AccessValidator $accessValidator
     */
    public function __construct(
        RequestInterface $request,
        AccessValidator $accessValidator
    ) {
        $this->request = $request;
        $this->accessValidator = $accessValidator;
    }

    /**
     * Plugin which updates the redirect url for PayPal Express payment cancellations.
     *
     * If the cancellation occurred while checking out a purchase order, redirect to the purchase order details page
     * instead of the active shopping cart.
     *
     * @param PaypalCancel $subject
     * @param Redirect $result
     * @return Redirect
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        PaypalCancel $subject,
        Redirect $result
    ) {
        $purchaseOrderId = $this->request->getParam('purchaseOrderId');
        if ($purchaseOrderId && $this->accessValidator->validatePlaceOrder((int)$purchaseOrderId)) {
            $result->setPath(
                'purchaseorder/purchaseorder/view/request_id/' . $purchaseOrderId
            );
        }

        return $result;
    }
}
