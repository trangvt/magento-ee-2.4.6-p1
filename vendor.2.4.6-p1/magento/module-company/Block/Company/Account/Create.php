<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Block\Company\Account;

use Magento\Company\Model\CountryInformationProvider;
use Magento\Customer\Helper\Address;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Directory\Helper\Data;

/**
 * Company account create block.
 *
 * @api
 * @since 100.0.0
 */
class Create extends Template
{
    /**
     * @var CountryInformationProvider
     */
    private $countryInformationProvider;

    /**
     * @var Address
     */
    private $addressHelper;

    /**
     * @param Context $context
     * @param CountryInformationProvider $countryInformationProvider
     * @param Address $addressHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CountryInformationProvider $countryInformationProvider,
        Address $addressHelper,
        array $data = []
    ) {
        $data['directoryDataHelper'] = ObjectManager::getInstance()->get(Data::class);
        parent::__construct($context, $data);
        $this->countryInformationProvider = $countryInformationProvider;
        $this->addressHelper = $addressHelper;
    }

    /**
     * Get config
     *
     * @param string $path
     * @return string|null
     */
    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Retrieve form posting url
     *
     * @return string
     */
    public function getPostActionUrl()
    {
        return $this->getUrl('*/account/createPost');
    }

    /**
     * Get countries list
     *
     * @return array
     */
    public function getCountriesList()
    {
        return $this->countryInformationProvider->getCountriesList();
    }

    /**
     * Retrieve form data
     *
     * @return mixed
     */
    public function getFormData()
    {
        $data = $this->getData('form_data');
        if ($data === null) {
            $data = new DataObject();
            $this->setData('form_data', $data);
        }
        return $data;
    }

    /**
     * Get default country id
     *
     * @return string
     */
    public function getDefaultCountryId()
    {
        return $this->_scopeConfig->getValue(
            'general/country/default',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get attribute validation class
     *
     * @param string $attributeCode
     * @return string
     */
    public function getAttributeValidationClass($attributeCode)
    {
        return $this->addressHelper->getAttributeValidationClass($attributeCode);
    }
}
