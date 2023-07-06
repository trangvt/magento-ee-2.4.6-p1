<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaypalPurchaseOrder\Plugin\Paypal\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Paypal\Model\ExpressConfigProvider as PaypalExpressConfigProvider;
use Magento\PurchaseOrder\Model\Access\Validator\PlaceOrder as AccessValidator;

/**
 * Plugin for PayPal Express config provider.
 */
class ExpressConfigProvider
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
     * Plugin constructor.
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
     * Plugin which updates the target urls for PayPal Express relative to purchase orders.
     *
     * If a purchaseOrderId is provided as a request parameter during checkout, pass it along to all
     * configured PayPal urls.
     *
     * @param PaypalExpressConfigProvider $subject
     * @param array $config
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetConfig(
        PaypalExpressConfigProvider $subject,
        array $config
    ) {
        $purchaseOrderId = $this->request->getParam('purchaseOrderId');

        if ($purchaseOrderId && $this->accessValidator->validatePlaceOrder((int)$purchaseOrderId)) {
            $purchaseOrderParam = 'purchaseOrderId/' . (int)$purchaseOrderId;
            $this->updateInContextUrls($config, $purchaseOrderParam);
            $this->updateRedirectUrls($config, $purchaseOrderParam);
        }

        return $config;
    }

    /**
     * Updates the urls for PayPal Express when in-context mode is enabled.
     *
     * Adds the purchaseOrderId as a query parameter.
     *
     * @param array $config
     * @param string $purchaseOrderParam
     */
    private function updateInContextUrls(array &$config, string $purchaseOrderParam)
    {
        if (isset($config['payment']['paypalExpress']['inContextConfig']['clientConfig'])) {
            $clientConfig = &$config['payment']['paypalExpress']['inContextConfig']['clientConfig'];
            $clientConfig['getTokenUrl'] .= $purchaseOrderParam;
            $clientConfig['onAuthorizeUrl'] .= $purchaseOrderParam;
            $clientConfig['onCancelUrl'] .= $purchaseOrderParam;
        }
    }

    /**
     * Updates the PayPal Express urls when in-context mode is disabled.
     *
     * Adds the purchaseOrderId as a query parameter.
     *
     * @param array $config
     * @param string $purchaseOrderParam
     */
    private function updateRedirectUrls(array &$config, string $purchaseOrderParam)
    {
        if (isset($config['payment']['paypalExpress']['redirectUrl'])) {
            foreach ($config['payment']['paypalExpress']['redirectUrl'] as $code => &$redirectUrl) {
                $redirectUrl .= $purchaseOrderParam;
            }
        }
    }
}
