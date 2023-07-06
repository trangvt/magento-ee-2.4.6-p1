<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaypalNegotiableQuote\Plugin\Paypal\Controller\Express;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;
use Magento\Quote\Model\QuoteRepository;

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
     * @var RestrictionInterfaceFactory
     */
    private $restrictionInterfaceFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * AbstractExpress constructor.
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
     * Checks the negotiableQuoteId param placed via request and replace it from session if it is not set.
     * See \Magento\PaypalNegotiableQuote\Plugin\Paypal\Model\Api\NvpPlugin for session-stored negotiable quote ID.
     * See \Magento\NegotiableQuote\Model\Plugin\Checkout\Model\SessionPlugin where the NQ quote replaces active quote.
     *
     * @param \Magento\Paypal\Controller\Express\AbstractExpress $subject
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        \Magento\Paypal\Controller\Express\AbstractExpress $subject
    ) {
        $negotiableQuoteId = $this->request->getParam('negotiableQuoteId')
            ?: $this->checkoutSession->getData('negotiableQuote_' . $this->request->getParam('token'));
        if ($negotiableQuoteId) {
            try {
                $quote = $this->quoteRepository->get($negotiableQuoteId);
                $restriction = $this->restrictionInterfaceFactory->create($quote);
                if ($restriction->isOwner() && $restriction->canProceedToCheckout()) {
                    $this->request->setParams(['negotiableQuoteId' => $negotiableQuoteId]);
                }
            } catch (NoSuchEntityException $e) {
                return;
            }
        }
    }
}
