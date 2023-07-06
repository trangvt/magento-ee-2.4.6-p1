<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaypalNegotiableQuote\Plugin\Paypal\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;
use Magento\Paypal\Model\ExpressConfigProvider as PaypalExpressConfigProvider;
use Magento\Quote\Model\QuoteRepository;

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
     * @var RestrictionInterfaceFactory
     */
    private $restrictionInterfaceFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * Plugin constructor.
     *
     * @param RequestInterface $request
     * @param RestrictionInterfaceFactory $restrictionInterfaceFactory
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        RequestInterface $request,
        RestrictionInterfaceFactory $restrictionInterfaceFactory,
        QuoteRepository $quoteRepository
    ) {
        $this->request = $request;
        $this->restrictionInterfaceFactory = $restrictionInterfaceFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Plugin which updates the target urls for PayPal Express relative to negotiable quotes.
     *
     * If a negotiableQuoteId is provided as a request parameter during checkout, pass it along to all
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
        $negotiableQuoteId = $this->request->getParam('negotiableQuoteId');

        if ($negotiableQuoteId) {
            try {
                $quote = $this->quoteRepository->get($negotiableQuoteId);
                $restriction = $this->restrictionInterfaceFactory->create($quote);
                if ($restriction->isOwner() && $restriction->canProceedToCheckout()) {
                    $negotiableQuoteParam = 'negotiableQuoteId/' . (int)$negotiableQuoteId;
                    $this->updateInContextUrls($config, $negotiableQuoteParam);
                    $this->updateRedirectUrls($config, $negotiableQuoteParam);
                }
            } catch (NoSuchEntityException $e) {
                return $config;
            }
        }
        return $config;
    }

    /**
     * Updates the urls for PayPal Express when in-context mode is enabled.
     *
     * Adds the negotiableQuoteId as a query parameter.
     *
     * @param array $config
     * @param string $negotiableQuoteParam
     */
    private function updateInContextUrls(array &$config, string $negotiableQuoteParam)
    {
        if (isset($config['payment']['paypalExpress']['inContextConfig']['clientConfig'])) {
            $clientConfig = &$config['payment']['paypalExpress']['inContextConfig']['clientConfig'];
            $clientConfig['getTokenUrl'] .= $negotiableQuoteParam;
            $clientConfig['onAuthorizeUrl'] .= $negotiableQuoteParam;
            $clientConfig['onCancelUrl'] .= $negotiableQuoteParam;
        }
    }

    /**
     * Updates the PayPal Express urls when in-context mode is disabled.
     *
     * Adds the negotiableQuoteId as a query parameter.
     *
     * @param array $config
     * @param string $negotiableQuoteParam
     */
    private function updateRedirectUrls(array &$config, string $negotiableQuoteParam)
    {
        if (isset($config['payment']['paypalExpress']['redirectUrl'])) {
            foreach ($config['payment']['paypalExpress']['redirectUrl'] as $code => &$redirectUrl) {
                $redirectUrl .= $negotiableQuoteParam;
            }
        }
    }
}
