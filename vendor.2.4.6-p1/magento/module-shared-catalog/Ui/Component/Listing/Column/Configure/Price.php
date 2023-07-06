<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Ui\Component\Listing\Column\Configure;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Price column component.
 */
class Price extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Column name.
     */
    const NAME = 'column.price';

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $components [optional]
     * @param array $data [optional]
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->localeCurrency = $localeCurrency;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $currency = $this->getCurrency();

            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = $currency->toCurrency(sprintf("%f", $item[$fieldName]));
                    if (isset($item['max_' . $fieldName])) {
                        $item['max_' . $fieldName] = $currency->toCurrency(sprintf("%f", $item['max_' . $fieldName]));
                    }
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get base currency for selected store/website scope.
     *
     * @return \Magento\Framework\Currency
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCurrency()
    {
        $websiteId = null;
        if ($this->context->getFilterParam('store_id') !== null) {
            $store = $this->storeManager->getGroup($this->context->getFilterParam('store_id'));
            $websiteId = $store->getWebsiteId();
        }
        $websiteId = $this->context->getFilterParam('websites', $websiteId);
        $website = $this->storeManager->getWebsite($websiteId);
        $currencyCode = $website->getBaseCurrencyCode();

        return $this->localeCurrency->getCurrency($currencyCode);
    }
}
