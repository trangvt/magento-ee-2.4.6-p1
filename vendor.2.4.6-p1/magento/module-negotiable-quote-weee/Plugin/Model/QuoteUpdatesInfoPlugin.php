<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteWeee\Plugin\Model;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\NegotiableQuote\Model\QuoteUpdatesInfo;
use Magento\NegotiableQuoteWeee\Model\WeeeDataResolver;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Weee\Helper\Data as WeeeHelper;

/**
 * Add weee attributes data to negotiable quote items.
 */
class QuoteUpdatesInfoPlugin
{
    /**
     * @var WeeeHelper
     */
    private $weeeHelper;

    /**
     * @var WeeeDataResolver
     */
    private $weeeDataResolver;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param WeeeHelper $weeeHelper
     * @param WeeeDataResolver $weeeDataResolver
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        WeeeHelper $weeeHelper,
        WeeeDataResolver $weeeDataResolver,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->weeeHelper = $weeeHelper;
        $this->weeeDataResolver = $weeeDataResolver;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Add weee attributes data to negotiable quote items.
     *
     * @param QuoteUpdatesInfo $model
     * @param array $data
     * @param CartInterface $quote
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetQuoteUpdatedData(
        QuoteUpdatesInfo $model,
        array $data,
        CartInterface $quote
    ): array {
        $dataItems = $data['items'] ?? [];
        $isWeeeEnabled = $this->weeeHelper->isEnabled($quote->getStoreId());
        $hashedQuoteItems = [];
        $weeeSubtotal = '';

        if ($isWeeeEnabled) {
            $quoteItems = $quote->getAllVisibleItems();

            foreach ($quoteItems as $quoteItem) {
                $hashedQuoteItems[$quoteItem->getData('itemHash')] = $quoteItem;
            }

            $weeeSubtotal = $this->formatPrice(
                $quote,
                (float)$this->weeeHelper->getBaseTotalAmounts($quoteItems, $quote->getStore())
            );
        }

        $data['subtotalWeee'] = $weeeSubtotal;
        $data['quoteWeee'] = $weeeSubtotal;

        foreach ($dataItems as $key => $dataItem) {
            $weeeData = [];

            if ($isWeeeEnabled) {
                $fptExclTax = '';
                $fptInclTax = '';
                $quoteItem = $hashedQuoteItems[$dataItem['itemHash']];
                if ($quoteItem !== false) {
                    $fptExclTax = $this->formatPrice(
                        $quote,
                        (float)$this->weeeDataResolver->getQuoteItemBaseWeeeAmountWithoutTax($quoteItem)
                    );

                    $fptInclTax = $this->formatPrice(
                        $quote,
                        (float)$this->weeeDataResolver->getQuoteItemBaseWeeeAmountWithTax($quoteItem)
                    );

                    $subtotalTax = $this->formatPrice(
                        $quote,
                        (float)$this->weeeDataResolver->getItemSubtotalTaxValue($quoteItem)
                    );
                }

                $weeeData = [
                    'fptExclTaxEnable' => $this->weeeDataResolver->isWeeeAttributeWithoutTaxVisible($quote),
                    'fptExclTax' => $fptExclTax,
                    'fptInclTaxEnable' => $this->weeeDataResolver->isWeeeAttributeWithTaxVisible($quote),
                    'fptInclTax' => $fptInclTax,
                ];
                $data['items'][$key]['subtotalTax'] = $subtotalTax;
            }

            $weeeData['isWeeeEnabled'] = $isWeeeEnabled;
            $data['items'][$key]['weeeData'] = $weeeData;

            unset($dataItem['itemHash']);
        }

        return $data;
    }

    /**
     * Format price.
     *
     * @param CartInterface $quote
     * @param float $price
     * @return string
     */
    private function formatPrice(CartInterface $quote, float $price): string
    {
        return $this->priceCurrency->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $quote->getCurrency()->getBaseCurrencyCode()
        );
    }
}
