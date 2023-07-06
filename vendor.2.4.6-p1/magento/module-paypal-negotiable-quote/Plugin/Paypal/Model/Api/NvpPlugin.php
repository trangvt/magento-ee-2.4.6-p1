<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaypalNegotiableQuote\Plugin\Paypal\Model\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\QuoteRepository;

/**
 * Plugin for PayPal API to persist negotiable quote ID by token.
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
     * @param Session $checkoutSession
     * @param RestrictionInterfaceFactory $restrictionInterfaceFactory
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        RequestInterface $request,
        Session $checkoutSession,
        RestrictionInterfaceFactory $restrictionInterfaceFactory,
        QuoteRepository $quoteRepository
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->restrictionInterfaceFactory = $restrictionInterfaceFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Storing negotiableQuoteId for PayPal checkout by token.
     * See \Magento\PaypalNegotiableQuote\Plugin\Paypal\Controller\Express\AbstractExpress where it is used.
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
            $negotiableQuoteId = $this->request->getParam('negotiableQuoteId');
            if ($negotiableQuoteId) {
                try {
                    $quote = $this->quoteRepository->get($negotiableQuoteId);
                    $restriction = $this->restrictionInterfaceFactory->create($quote);
                    if ($restriction->isOwner() && $restriction->canProceedToCheckout()) {
                        $this->checkoutSession->setData('negotiableQuote_' . $token, $negotiableQuoteId);
                    }
                } catch (NoSuchEntityException $e) {
                    return $token;
                }
            }
            return $token;
        } else {
            return $proceed($key, $index);
        }
    }
}
