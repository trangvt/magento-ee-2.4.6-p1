<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteWeee\Plugin\Helper;

use Magento\Framework\Pricing\Render as PricingRender;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Helper\Quote as NegotiableQuoteHelper;
use Magento\NegotiableQuoteWeee\Block\Item\Price\Renderer;
use Magento\Quote\Api\Data\CartItemInterface;

/**
 * Update item price and item total renderers.
 */
class QuotePlugin
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @param LayoutInterface $layout
     */
    public function __construct(
        LayoutInterface $layout
    ) {
        $this->layout = $layout;
    }

    /**
     * Change item price renderer.
     *
     * @param NegotiableQuoteHelper $subject
     * @param \Closure $proceed
     * @param CartItemInterface $item
     * @param string|null $quoteCurrency
     * @param string|null $baseCurrency
     * @return string|float
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetFormattedCatalogPrice(
        NegotiableQuoteHelper $subject,
        \Closure $proceed,
        CartItemInterface $item,
        $quoteCurrency = null,
        $baseCurrency = null
    ) {
        $block = $this->layout->createBlock(Renderer::class);
        $block
            ->setTemplate('Magento_Weee::item/price/unit.phtml')
            ->setItem($item)
            ->setZone(PricingRender::ZONE_SALES);

        return $block->toHtml();
    }

    /**
     * Change item total renderer.
     *
     * @param NegotiableQuoteHelper $subject
     * @param \Closure $proceed
     * @param CartItemInterface $item
     * @param string|null $quoteCurrency
     * @param string|null $baseCurrency
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetItemTotal(
        NegotiableQuoteHelper $subject,
        \Closure $proceed,
        CartItemInterface $item,
        $quoteCurrency = null,
        $baseCurrency = null
    ) {
        $block = $this->layout->createBlock(Renderer::class);
        $block
            ->setTemplate('Magento_Weee::item/price/row.phtml')
            ->setItem($item)
            ->setZone(PricingRender::ZONE_SALES);

        return $block->toHtml();
    }
}
