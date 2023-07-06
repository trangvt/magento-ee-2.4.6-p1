<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaypalNegotiableQuote\Plugin\Paypal\Controller\Express\AbstractExpress;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;
use Magento\Paypal\Controller\Express\AbstractExpress\Cancel as PaypalCancel;
use Magento\Quote\Model\QuoteRepository;

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
     * @var RestrictionInterfaceFactory
     */
    private $restrictionInterfaceFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * Cancel plugin constructor.
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
     * Plugin which updates the redirect url for PayPal Express payment cancellations.
     *
     * If the cancellation occurred while checking out a negotiable quote, redirect to the negotiable quote details page
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
        $negotiableQuoteId = $this->request->getParam('negotiableQuoteId');
        if ($negotiableQuoteId) {
            try {
                $quote = $this->quoteRepository->get($negotiableQuoteId);
                $restriction = $this->restrictionInterfaceFactory->create($quote);
                if ($restriction->isOwner() && $restriction->canProceedToCheckout()) {
                    $result->setPath(
                        'negotiable_quote/quote/view/quote_id/' . $negotiableQuoteId
                    );
                }
            } catch (NoSuchEntityException $e) {
                return $result;
            }
        }
        return $result;
    }
}
