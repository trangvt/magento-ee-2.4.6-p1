<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Backend\Model\Session\Quote;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;

/**
 * Class for Company Credit.
 */
class CompanyCredit extends \Magento\Payment\Block\Form
{
    /**
     * CompanyCredit order template.
     *
     * @var string
     */
    protected $_template = 'Magento_CompanyCredit::form/companycredit.phtml';

    /**
     * Credit Data Provider
     *
     * @var CreditDataProviderInterface
     */
    private $creditDataProvider;

    /**
     * Company Management
     *
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * @var WebsiteCurrency
     */
    private $websiteCurrency;

    /**
     * @var Quote
     */
    private $sessionQuote;

    /**
     * Credit Data
     *
     * @var CreditDataInterface
     */
    private $credit;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CreditDataProviderInterface $creditDataProvider
     * @param CompanyManagementInterface $companyManagement
     * @param PriceCurrencyInterface $priceFormatter
     * @param WebsiteCurrency $websiteCurrency
     * @param Quote $sessionQuote
     * @param array $data
     */
    public function __construct(
        Context $context,
        CreditDataProviderInterface $creditDataProvider,
        CompanyManagementInterface $companyManagement,
        PriceCurrencyInterface $priceFormatter,
        WebsiteCurrency $websiteCurrency,
        Quote $sessionQuote,
        array $data = []
    ) {
        $this->creditDataProvider = $creditDataProvider;
        $this->companyManagement = $companyManagement;
        $this->priceFormatter = $priceFormatter;
        $this->websiteCurrency = $websiteCurrency;
        $this->sessionQuote = $sessionQuote;
        parent::__construct($context, $data);
    }

    /**
     * Verify if the order total exceeds the available credit.
     *
     * @return bool
     */
    public function hasExceededCreditLimit()
    {
        $credit = $this->getCredit();
        if ($credit == null) {
            return true;
        }

        if ($credit->getExceedLimit()) {
            return false;
        }

        $grandTotal = $this->sessionQuote->getQuote()->getData("grand_total");
        $creditAvailableLimit = $credit->getAvailableLimit();
        return ((float)$creditAvailableLimit < $grandTotal);
    }

    /**
     * Get credit.
     *
     * @return CreditDataInterface|null
     */
    public function getCredit()
    {
        if ($this->credit) {
            return $this->credit;
        }

        $company  = null;
        $quote = $this->sessionQuote->getQuote();
        $customerId = $quote->getData('customer_id');

        if ($customerId) {
            $company = $this->companyManagement->getByCustomerId($customerId);
        }

        if (!$company) {
            return null;
        }

        $this->credit = $this->creditDataProvider->get($company->getId());

        return $this->credit;
    }

    /**
     * Get current user credit balance.
     *
     * @return string
     */
    public function getCurrentCustomerCreditBalance()
    {
        $credit = $this->getCredit();
        $creditAvailableLimit = ($credit !== null) ? $credit->getAvailableLimit() : 0;

        return __(
            '(Available Credit: %1)',
            $this->priceFormatter->format(
                $creditAvailableLimit,
                false,
                \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                null,
                $this->getCreditCurrency()
            )
        );
    }

    /**
     * Get credit currency.
     *
     * @return \Magento\Directory\Model\Currency
     */
    private function getCreditCurrency()
    {
        $creditCurrencyCode = null;
        if ($credit = $this->getCredit()) {
            $creditCurrencyCode = $credit->getCurrencyCode();
        }
        return $this->websiteCurrency->getCurrencyByCode($creditCurrencyCode);
    }
}
