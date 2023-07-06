<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\Action\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Element\Multiline;

/**
 * Class for populating customer object.
 */
class Populator
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var DataObjectHelper
     */
    private $objectHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerFactory
     * @param DataObjectHelper $objectHelper
     * @param StoreManagerInterface $storeManager
     * @param EavConfig|null $eavConfig
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        DataObjectHelper $objectHelper,
        StoreManagerInterface $storeManager,
        ?EavConfig $eavConfig = null
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->objectHelper = $objectHelper;
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig ?: ObjectManager::getInstance()
            ->get(EavConfig::class);
    }

    /**
     * Populate customer.
     *
     * @param array $data
     * @param CustomerInterface|null $customer [optional]
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function populate(array $data, CustomerInterface $customer = null)
    {
        if ($customer === null) {
            $customer = $this->customerFactory->create();
            $actionId = 'customer_account_edit-';
        } else {
            $actionId = 'customer_account_create-';
        }

        $customerId = $customer->getId();
        $data = $this->populateDateAttributeDataKey($actionId, $data);
        $this->objectHelper->populateWithArray(
            $customer,
            $data,
            \Magento\Customer\Api\Data\CustomerInterface::class
        );
        $this->setCustomerCustomMultilineAttribute($customer);
        $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
        $customer->setStoreId($this->storeManager->getStore()->getId());
        $customer->setId($customerId);

        return $customer;
    }

    /**
     * Set customer custom multiline attribute
     *
     * @param CustomerInterface $customer
     * @return void
     * @throws LocalizedException
     */
    private function setCustomerCustomMultilineAttribute(CustomerInterface $customer): void
    {
        $customCustomerAttributes = $customer->getCustomAttributes();
        if ($customCustomerAttributes) {
            foreach ($customCustomerAttributes as $customAttributeKey => $customerCustomAttribute) {
                $attributeCode = $customerCustomAttribute->getAttributeCode();
                $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attributeCode);
                $attributeType = $attribute->getFrontendInput();

                if ($attributeType == Multiline::NAME) {
                    $multilineValues = $customerCustomAttribute->getValue();
                    if (!empty($multilineValues) && is_array($multilineValues)) {
                        $multilineAttributeValues = implode("\n", $customerCustomAttribute->getValue());
                        $customerCustomAttribute->setValue($multilineAttributeValues);
                    }
                }
            }
        }
    }

    /**
     * Populate date attribute data key
     *
     * @param string $actionId
     * @param array $data
     * @return array
     */
    private function populateDateAttributeDataKey(string $actionId, array $data): array
    {
        $dataKeys = preg_grep('/' . $actionId . '/', array_keys($data));
        if ($dataKeys) {
            foreach ($dataKeys as $key) {
                if (isset($data[$key]) && $data[$key] != null) {
                    $dataStringArr = explode($actionId, $key);
                    $customAttributeKey = $dataStringArr[count($dataStringArr) - 1];
                    $data[$customAttributeKey] = $data[$key];
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }
}
