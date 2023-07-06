<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PaypalNegotiableQuote\Plugin\Paypal\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Paypal\Model\PayflowConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;
use Magento\Quote\Model\QuoteRepository;

class PayflowConfigPlugin
{
    /**
     * @var string
     */
    private $placeOrderUrlConfig = 'place_order_url';

    /**
     * @var string
     */
    private $negotiableQuoteIdParam = 'negotiableQuoteId';

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
     * Add negotiable quote id param to url
     *
     * @param PayflowConfig $subject
     * @param string $result
     * @param string $key
     * @return string
     */
    public function afterGetValue(PayflowConfig $subject, $result, $key)
    {
        $negotiableQuoteId = $this->request->getParam($this->negotiableQuoteIdParam);
        if ($negotiableQuoteId && $key == $this->placeOrderUrlConfig) {
            try {
                $quote = $this->quoteRepository->get($negotiableQuoteId);
                $restriction = $this->restrictionInterfaceFactory->create($quote);
                if ($restriction->isOwner() && $restriction->canProceedToCheckout()) {
                    $result = rtrim($result, '/');
                    $result .= '/' . $this->negotiableQuoteIdParam . '/' . $negotiableQuoteId;
                }
            } catch (NoSuchEntityException $e) {
                return $result;
            }
        }
        return $result;
    }
}
