<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Helper\Company;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\SettingsProvider;

/**
 * Negotiable quote customer model with related validation methods
 */
class Customer
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SettingsProvider
     */
    private $settingsProvider;

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var Company
     */
    private $companyHelper;

    /**
     * @var RestrictionInterface
     */
    private $restriction;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var NegotiableQuote
     */
    private $negotiableQuoteHelper;

    private const ALL_RESOURCE = 'Magento_NegotiableQuote::all';
    private const MANAGE_RESOURCE = 'Magento_NegotiableQuote::manage';
    private const PROCEED_TO_CHECKOUT_RESOURCE = 'Magento_NegotiableQuote::checkout';
    private const VIEW_RESOURCE = 'Magento_NegotiableQuote::view_quotes';
    private const VIEW_SUBS_RESOURCE = 'Magento_NegotiableQuote::view_quotes_sub';

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param SettingsProvider $settingsProvider
     * @param CompanyManagementInterface $companyManagement
     * @param Company $companyHelper
     * @param RestrictionInterface $restriction
     * @param Structure $structure
     * @param NegotiableQuote $negotiableQuoteHelper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        SettingsProvider $settingsProvider,
        CompanyManagementInterface $companyManagement,
        Company $companyHelper,
        RestrictionInterface $restriction,
        Structure $structure,
        NegotiableQuote $negotiableQuoteHelper
    ) {
        $this->customerRepository = $customerRepository;
        $this->settingsProvider = $settingsProvider;
        $this->companyManagement = $companyManagement;
        $this->companyHelper = $companyHelper;
        $this->restriction = $restriction;
        $this->structure = $structure;
        $this->negotiableQuoteHelper = $negotiableQuoteHelper;
    }

    /**
     * Get customer data
     *
     * @param int $customerId
     * @return CustomerInterface
     * @throws GraphQlNoSuchEntityException
     */
    public function getCustomer(int $customerId): CustomerInterface
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__("The customer ID does not exist."));
        }
        return $customer;
    }

    /**
     * Verify that negotiable quotes are enabled and the current customer has view permission
     *
     * @param int $customerId
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function validateCanView(int $customerId): void
    {
        $this->validateNegotiableQuotesEnabled($customerId);

        if (!$this->restriction->isAllowed(self::VIEW_RESOURCE)) {
            throw new GraphQlAuthorizationException(
                __("The current customer does not have permission to view negotiable quotes.")
            );
        }
    }

    /**
     * Verify that negotiable quotes are enabled and the current customer has manage permission
     *
     * @param int $customerId
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function validateCanManage(int $customerId): void
    {
        $this->validateNegotiableQuotesEnabled($customerId);

        if (!$this->restriction->isAllowed(self::MANAGE_RESOURCE)) {
            throw new GraphQlAuthorizationException(
                __("The current customer does not have permission to manage negotiable quotes.")
            );
        }
    }

    /**
     * Verify that the current customer has checkout permission
     *
     * @param int $customerId
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function validateCanProceedToCheckout(int $customerId): void
    {
        $this->validateNegotiableQuotesEnabled($customerId);

        if (!$this->restriction->isAllowed(self::PROCEED_TO_CHECKOUT_RESOURCE)) {
            throw new GraphQlAuthorizationException(
                __("The current customer does not have permission to checkout negotiable quotes.")
            );
        }
    }

    /**
     * Verify that negotiable quotes are enabled and can be accessed by the specified customer.
     *
     * The following checks are performed: the NegotiableQuote module is enabled,
     * the customer is a member of a company, and negotiable quotes are enabled for that company
     *
     * @param int $customerId
     * @return void
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function validateNegotiableQuotesEnabled(int $customerId): void
    {
        if ($customerId === 0) {
            throw new GraphQlAuthorizationException(
                __('The current user is not a registered customer and cannot perform operations on negotiable quotes.')
            );
        }
        $customer = $this->getCustomer($customerId);

        if (!$this->settingsProvider->isModuleEnabled()) {
            throw new GraphQlAuthorizationException(__("The Negotiable Quote module is not enabled."));
        }

        $company = $this->companyManagement->getByCustomerId($customerId);
        if (!$this->settingsProvider->isCurrentUserCompanyUser() || $company === null) {
            throw new GraphQlAuthorizationException(__("The current customer does not belong to a company."));
        }

        $quoteConfig = $this->companyHelper->getQuoteConfig($company);
        if (!$quoteConfig->getIsQuoteEnabled() ||
            $customer->getExtensionAttributes()->getCompanyAttributes()->getStatus()
            != CompanyInterface::STATUS_APPROVED
        ) {
            throw new GraphQlAuthorizationException(
                __("Negotiable quotes are not enabled for the current customer's company.")
            );
        }

        $this->negotiableQuoteHelper->setIsNegotiableQuoteOperation(true);
    }

    /**
     * Get the list of customer ids whose quotes the given customer is able to view
     *
     * @param int $customerId
     * @return int[]
     */
    public function getViewableCustomerIds(int $customerId): array
    {
        $company = $this->companyManagement->getByCustomerId($customerId);
        if (!$this->settingsProvider->isCurrentUserCompanyUser() || $company === null) {
            return [$customerId];
        }

        $subordinateIds = [];
        if (((int) $company->getSuperUserId()) === $customerId
            || $this->restriction->isAllowed(self::VIEW_SUBS_RESOURCE)
        ) {
            $subordinateIds = $this->structure->getAllowedChildrenIds($customerId);
        }
        $subordinateIds[] = $customerId;

        return array_unique($subordinateIds);
    }
}
