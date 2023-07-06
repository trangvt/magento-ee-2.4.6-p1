<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Plugin;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\NegotiableQuoteAttributeLoader;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\NegotiableQuote;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteRepository\LoadHandler;

/**
 * Class for applying negotiable extension attributes to a quote on load if possible.
 */
class LoadHandlerPlugin
{
    /**
     * @var NegotiableQuoteAttributeLoader
     */
    private $attributeLoader;

    /**
     * @var NegotiableQuote
     */
    private $negotiableQuoteHelper;

    /**
     * @param NegotiableQuoteAttributeLoader $attributeLoader
     * @param NegotiableQuote $negotiableQuoteHelper
     */
    public function __construct(NegotiableQuoteAttributeLoader $attributeLoader, NegotiableQuote $negotiableQuoteHelper)
    {
        $this->attributeLoader = $attributeLoader;
        $this->negotiableQuoteHelper = $negotiableQuoteHelper;
    }

    /**
     * Plugin to apply negotiable attributes to a quote before loading
     *
     * @param LoadHandler $subject
     * @param CartInterface $quote
     * @return CartInterface[]
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeLoad(LoadHandler $subject, CartInterface $quote): array
    {
        if (!$quote->getExtensionAttributes() || !$quote->getExtensionAttributes()->getNegotiableQuote()) {
            $quote = $this->attributeLoader->loadNegotiableAttributes($quote);
        }

        // A negotiable quote's is_active flag should only be true during nq-specific operations, blocking operations
        // meant for carts/non-negotiable quotes from being able to execute
        if ($quote->getExtensionAttributes()->getNegotiableQuote()
            && $quote->getExtensionAttributes()->getNegotiableQuote()->getIsRegularQuote() !== null) {
            if ($this->negotiableQuoteHelper->isNegotiableQuoteOperation()) {
                $quote->setIsActive(true);
            } else {
                $quote->setIsActive(false);
            }
        }
        return [$quote];
    }
}
