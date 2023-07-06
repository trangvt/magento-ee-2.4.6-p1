<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Users;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * Company customer user data provider
 */
class Customer
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get customer company data
     *
     * @param CustomerInterface $customer
     * @return CompanyCustomerInterface|null
     */
    public function getCustomerCompanyAttributes(CustomerInterface $customer): ?CompanyCustomerInterface
    {
        $customerCompanyAttributes = null;

        if ($customer && $customer->getExtensionAttributes()
            && $customer->getExtensionAttributes()->getCompanyAttributes()
        ) {
            $customerCompanyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        }

        return $customerCompanyAttributes;
    }

    /**
     * Get customer by id
     *
     * @param int $customerId
     * @return CustomerInterface
     */
    public function getCustomerById(int $customerId): CustomerInterface
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('Customer with id "%customer_id" does not exist.', ['customer_id' => $customerId]),
                $e
            );
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }
}
