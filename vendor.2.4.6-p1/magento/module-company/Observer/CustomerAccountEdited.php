<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Company\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Observer for Customer Account after edit event
 */
class CustomerAccountEdited implements ObserverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CustomerResource
     */
    protected $customerResource;

    /**
     * CustomerAccountEdited constructor
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param RequestInterface $request
     * @param CustomerResource $customerResource
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RequestInterface $request,
        CustomerResource $customerResource
    ) {
        $this->customerRepository = $customerRepository;
        $this->request = $request;
        $this->customerResource = $customerResource;
    }

    /**
     * Customer account after edit processing
     *
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function execute(Observer $observer)
    {
        $email = $observer->getEmail();
        $customer = $this->customerRepository->get($email);
        $customerData = $this->request->getParam('customer');
        if ($customer->getExtensionAttributes() !== null
            && $customer->getExtensionAttributes()->getCompanyAttributes() !== null
            && isset($customerData['extension_attributes']['company_attributes']['job_title'])
        ) {
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            $companyAttributes->setCustomerId($customer->getId())
                ->setJobTitle($customerData['extension_attributes']['company_attributes']['job_title']);
            $this->customerResource->saveAdvancedCustomAttributes($companyAttributes);
        }

        return $this;
    }
}
