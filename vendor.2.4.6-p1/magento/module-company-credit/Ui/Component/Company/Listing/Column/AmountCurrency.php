<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CompanyCredit\Ui\Component\Company\Listing\Column;

use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * AmountCurrency validate the price currency and prepare data source
 */
class AmountCurrency extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * @var \Magento\CompanyCredit\Model\WebsiteCurrency
     */
    private $websiteCurrency;

    /**
     * Fields whose display is dependent on other fields.
     *
     * @var array
     */
    private $dependentFields = [
        'balance' => 'credit_limit',
        'credit_limit' => 'balance'
    ];

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter
     * @param WebsiteCurrency $websiteCurrency
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter,
        WebsiteCurrency $websiteCurrency,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->priceFormatter = $priceFormatter;
        $this->websiteCurrency = $websiteCurrency;
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $currencyCode = (isset($item['currency_code'])) ? $item['currency_code'] : null;
                $currency = $this->websiteCurrency->getCurrencyByCode($currencyCode);
                if (isset($item[$fieldName]) && $item[$fieldName] != 0
                    || isset($item[$this->dependentFields[$fieldName]])
                    && $item[$this->dependentFields[$fieldName]] != null
                ) {
                    $item[$fieldName] = $this->priceFormatter->format(
                        $item[$fieldName],
                        false,
                        \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                        null,
                        $currency
                    );
                } else {
                    $item[$fieldName] = null;
                }
            }
        }

        return $dataSource;
    }
}
