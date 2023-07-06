<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Users;

use Magento\Company\Api\CompanyUserManagerInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Model\Action\Customer\Assign;
use Magento\Company\Model\Action\Customer\Populator;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyUser;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Execute company user creating
 */
class CreateCompanyUser
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Assign
     */
    private $roleAssigner;

    /**
     * @var CompanyUserManagerInterface
     */
    private $userManager;

    /**
     * @var CompanyUser
     */
    private $userHelper;

    /**
     * @var Populator
     */
    private $customerPopulator;

    /**
     * @var AccountManagementInterface
     */
    private $customerManager;

    /**
     * @var Structure
     */
    private $structureManager;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyUser $userHelper
     * @param Populator $customerPopulator
     * @param CompanyUserManagerInterface $userManager
     * @param AccountManagementInterface $customerManager
     * @param Structure $structureManager
     * @param Assign $roleAssigner
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CompanyUser $userHelper,
        Populator $customerPopulator,
        CompanyUserManagerInterface $userManager,
        AccountManagementInterface $customerManager,
        Structure $structureManager,
        Assign $roleAssigner
    ) {
        $this->customerRepository = $customerRepository;
        $this->userHelper = $userHelper;
        $this->customerPopulator = $customerPopulator;
        $this->userManager = $userManager;
        $this->customerManager = $customerManager;
        $this->structureManager = $structureManager;
        $this->roleAssigner = $roleAssigner;
    }

    /**
     * Create a company user
     *
     * @param array $userData
     * @return CustomerInterface|null
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(array $userData)
    {
        $customer = $this->checkCustomerAccount($userData['email']);
        $customer = $this->customerPopulator->populate($userData, $customer);
        if ($customer->getId()) {
            $this->sendInvitationToExisting($customer);
            throw new GraphQlInputException(
                __(
                    'Invitation was sent to an existing customer, '
                    . 'they will be added to your organization once they accept the invitation.'
                )
            );
        }

        $this->customerManager->createAccount($customer);
        $customer = $this->customerRepository->get($customer->getEmail());

        if (isset($userData['target_id'])) {
            $this->addCustomerToStructure($customer, $userData['target_id']);
        }

        try {
            $this->roleAssigner->assignCustomerRole($customer, $userData['role_id']);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlInputException(__('Value "%1" is incorrect.', $userData['role_id']));
        }

        return $customer;
    }

    /**
     * Check customer's account
     *
     * @param string $email
     * @return CustomerInterface|null
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    private function checkCustomerAccount(string $email): ?CustomerInterface
    {
        try {
            $customer = $this->customerRepository->get($email);
            if ($this->hasCustomerCompany($customer)) {
                throw new GraphQlInputException(
                    __('A customer with the same email already assigned to company.')
                );
            }
        } catch (NoSuchEntityException $e) {
            $customer = null;
        }

        return $customer;
    }

    /**
     * When trying to assign existing customer then sending them an invite first.
     *
     * @param CustomerInterface $customer
     * @return void
     * @throws LocalizedException
     */
    private function sendInvitationToExisting(CustomerInterface $customer): void
    {
        if (!$companyId = $this->userHelper->getCurrentCompanyId()) {
            throw new LocalizedException(__('Meant to be initiated by a company customer'));
        }
        /** @var CompanyCustomerInterface $companyAttributes */
        $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        $companyAttributes->setCustomerId($customer->getId());
        $companyAttributes->setCompanyId($companyId);
        $this->userManager->sendInvitation($companyAttributes, null);
    }

    /**
     * Has customer company.
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    private function hasCustomerCompany(CustomerInterface $customer): bool
    {
        return $customer->getExtensionAttributes()
            && $customer->getExtensionAttributes()->getCompanyAttributes()
            && (int)$customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId() > 0;
    }

    /**
     * Add customer to structure.
     *
     * @param CustomerInterface $customer
     * @param int $targetId
     * @return void
     * @throws LocalizedException
     */
    private function addCustomerToStructure(CustomerInterface $customer, $targetId)
    {
        $structure = $this->structureManager->getStructureByCustomerId($customer->getId());
        if ($structure && $targetId && $structure->getId()) {
            $this->structureManager->removeCustomerNode($customer->getId());
            $this->structureManager->addNode(
                $customer->getId(),
                StructureInterface::TYPE_CUSTOMER,
                $targetId
            );
        }
    }
}
