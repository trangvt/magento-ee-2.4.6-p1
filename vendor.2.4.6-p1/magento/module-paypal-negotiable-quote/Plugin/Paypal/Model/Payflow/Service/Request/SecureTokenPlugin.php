<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PaypalNegotiableQuote\Plugin\Paypal\Model\Payflow\Service\Request;

use Magento\Framework\App\RequestInterface;
use Magento\Paypal\Model\Payflow\Service\Request\SecureToken;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;
use Magento\Quote\Model\QuoteRepository;

class SecureTokenPlugin
{
    /**
     * @var string
     */
    private $redirectRoutePath = 'paypal/transparent/redirect';

    /**
     * @var string
     */
    private $negotiableQuoteIdParam = 'negotiableQuoteId';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var RestrictionInterfaceFactory
     */
    private $restrictionInterfaceFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @param RequestInterface $request
     * @param UrlInterface $url
     * @param RestrictionInterfaceFactory $restrictionInterfaceFactory
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $url,
        RestrictionInterfaceFactory $restrictionInterfaceFactory,
        QuoteRepository $quoteRepository
    ) {
        $this->request = $request;
        $this->url = $url;
        $this->restrictionInterfaceFactory = $restrictionInterfaceFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Add negotiable quote id param to url
     *
     * @param SecureToken $subject
     * @param Quote $quote
     * @param array $urls
     * @return array
     */
    public function beforeRequestToken(SecureToken $subject, Quote $quote, array $urls = [])
    {
        $negotiableQuoteId = (int) $this->request->getParam($this->negotiableQuoteIdParam);
        if ($negotiableQuoteId) {
            try {
                $quote = $this->quoteRepository->get($negotiableQuoteId);
                $restriction = $this->restrictionInterfaceFactory->create($quote);
                if ($restriction->isOwner() && $restriction->canProceedToCheckout()) {
                    $routePath = $this->redirectRoutePath . '/' . $this->negotiableQuoteIdParam . '/' . $negotiableQuoteId;
                    $urls['return_url'] = $urls['return_url'] ?? $this->url->getUrl($routePath);
                    $urls['error_url'] = $urls['error_url'] ?? $this->url->getUrl($routePath);
                    $urls['cancel_url'] = $urls['cancel_url'] ?? $this->url->getUrl($routePath);
                }
            } catch (NoSuchEntityException $e) {
                return [$quote, $urls];
            }
        }
        return [$quote, $urls];
    }
}
