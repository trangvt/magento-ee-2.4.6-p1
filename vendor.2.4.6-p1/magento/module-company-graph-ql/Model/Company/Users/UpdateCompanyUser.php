<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Users;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\Action\Customer\Assign;
use Magento\Company\Model\Action\Customer\Populator;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Execute company user updating
 */
class UpdateCompanyUser
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var Populator
     */
    private $customerPopulator;

    /**
     * @var Assign
     */
    private $roleAssigner;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param Populator $customerPopulator
     * @param Assign $roleAssigner
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CompanyRepositoryInterface $companyRepository,
        Populator $customerPopulator,
        Assign $roleAssigner
    ) {
        $this->companyRepository = $companyRepository;
        $this->customerRepository = $customerRepository;
        $this->customerPopulator = $customerPopulator;
        $this->roleAssigner = $roleAssigner;
    }

    /**
     * Update company user
     *
     * @param CustomerInterface $customer
     * @param array $customerData
     * @return CustomerInterface
     * @throws GraphQlInputException
     */
    public function execute(CustomerInterface $customer, array $customerData): CustomerInterface
    {
        try {
            $company = $this->companyRepository->get(
                $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
            );
            $customer = $this->customerPopulator->populate($customerData, $customer);
            $this->customerRepository->save($customer);

            if (isset($customerData['role_id']) && $company->getSuperUserId() !== $customerData['id']) {
                $this->roleAssigner->assignCustomerRole($customer, $customerData['role_id']);
            }
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $customer;
    }
}
