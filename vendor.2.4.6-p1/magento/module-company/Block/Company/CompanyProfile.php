<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Block\Company;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CountryInformationProvider;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\User\Model\UserFactory;
use Magento\User\Api\Data\UserInterface;
use Magento\User\Model\User;
use Magento\Directory\Helper\Data;

/**
 * @api
 * Company Profile block.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.0
 */
class CompanyProfile extends Template
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var CountryInformationProvider
     */
    private $countryInformationProvider;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CompanyInterface
     */
    private $company = null;

    /**
     * @var CustomerNameGenerationInterface
     */
    private $customerViewHelper;

    /**
     * @var CustomerInterface
     */
    private $companyAdmin;

    /**
     * @var UserInterface
     */
    private $salesRepresentative;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @inheritdoc
     *
     * @param Context $context
     * @param UserContextInterface $userContext
     * @param CompanyManagementInterface $companyManagement
     * @param CountryInformationProvider $countryInformationProvider
     * @param UserFactory $userFactory
     * @param ManagerInterface $messageManager
     * @param CustomerNameGenerationInterface $customerViewHelper
     * @param AuthorizationInterface $authorization
     * @param array $data
     */
    public function __construct(
        Context $context,
        UserContextInterface $userContext,
        CompanyManagementInterface $companyManagement,
        CountryInformationProvider $countryInformationProvider,
        UserFactory $userFactory,
        ManagerInterface $messageManager,
        CustomerNameGenerationInterface $customerViewHelper,
        AuthorizationInterface $authorization,
        array $data = []
    ) {
        $data['directoryDataHelper'] = ObjectManager::getInstance()->get(Data::class);
        parent::__construct($context, $data);
        $this->userContext = $userContext;
        $this->companyManagement = $companyManagement;
        $this->countryInformationProvider = $countryInformationProvider;
        $this->userFactory = $userFactory;
        $this->messageManager = $messageManager;
        $this->customerViewHelper = $customerViewHelper;
        $this->authorization = $authorization;
    }

    /**
     * Checks if account view is allowed.
     *
     * @return bool
     */
    public function isViewAccountAllowed()
    {
        return $this->authorization->isAllowed('Magento_Company::view_account');
    }

    /**
     * Checks if account edit is allowed.
     *
     * @return bool
     */
    public function isEditAccountAllowed()
    {
        return $this->authorization->isAllowed('Magento_Company::edit_account');
    }

    /**
     * Checks if address view is allowed.
     *
     * @return bool
     */
    public function isViewAddressAllowed()
    {
        return $this->authorization->isAllowed('Magento_Company::view_address');
    }

    /**
     * Checks if address edit is allowed.
     *
     * @return bool
     */
    public function isEditAddressAllowed()
    {
        return $this->authorization->isAllowed('Magento_Company::edit_address');
    }

    /**
     * Checks if contacts view is allowed.
     *
     * @return bool
     */
    public function isViewContactsAllowed()
    {
        return $this->authorization->isAllowed('Magento_Company::contacts');
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
     * Get form messages
     *
     * @return array
     */
    public function getFormMessages()
    {
        $messagesList = [];
        $messagesCollection = $this->messageManager->getMessages(true);

        if ($messagesCollection && $messagesCollection->getCount()) {
            $messages = $messagesCollection->getItems();
            foreach ($messages as $message) {
                $messagesList[] = $message->getText();
            }
        }

        return $messagesList;
    }

    /**
     * Is edit link displayed
     *
     * @return bool
     */
    public function isEditLinkDisplayed()
    {
        return $this->authorization->isAllowed('Magento_Company::edit_account')
            || $this->authorization->isAllowed('Magento_Company::edit_address');
    }

    /**
     * Get current customer's company
     *
     * @return CompanyInterface
     */
    public function getCustomerCompany()
    {
        if ($this->company !== null) {
            return $this->company;
        }

        $customerId = $this->userContext->getUserId();

        if ($customerId) {
            $this->company = $this->companyManagement->getByCustomerId($customerId);
        }

        return $this->company;
    }

    /**
     * Gets company street label
     *
     * @param CompanyInterface $company
     * @return string
     */
    public function getCompanyStreetLabel(CompanyInterface $company)
    {
        $streetLabel = '';
        $streetData = $company->getStreet();
        $streetLabel .= (!empty($streetData[0])) ? $streetData[0] : '';
        $streetLabel .= (!empty($streetData[1])) ? ' ' . $streetData[1] : '';

        return $streetLabel;
    }

    /**
     * Is company address displayed
     *
     * @param CompanyInterface $company
     * @return bool
     */
    public function isCompanyAddressDisplayed(CompanyInterface $company)
    {
        return $company->getCountryId() ? true : false;
    }

    /**
     * Get company address string
     *
     * @param CompanyInterface $company
     * @return string
     */
    public function getCompanyAddressString(CompanyInterface $company)
    {
        $addressParts = [];

        $addressParts[] = $company->getCity();
        $addressParts[] = $this->countryInformationProvider->getActualRegionName(
            $company->getCountryId(),
            $company->getRegionId(),
            $company->getRegion()
        );
        $addressParts[] = $company->getPostcode();

        return implode(', ', array_filter($addressParts));
    }

    /**
     * Get company country label
     *
     * @param CompanyInterface $company
     * @return string
     */
    public function getCompanyCountryLabel(CompanyInterface $company)
    {
        return $this->countryInformationProvider->getCountryNameByCode($company->getCountryId());
    }

    /**
     * Get company admin name
     *
     * @param CompanyInterface $company
     * @return string
     */
    public function getCompanyAdminName(CompanyInterface $company)
    {
        $companyAdmin = $this->getCompanyAdmin($company);

        return ($companyAdmin && $companyAdmin->getId())
            ? $this->customerViewHelper->getCustomerName($companyAdmin) : '';
    }

    /**
     * Get company admin job title
     *
     * @param CompanyInterface $company
     * @return string
     */
    public function getCompanyAdminJobTitle(CompanyInterface $company)
    {
        $jobTitle = '';
        $companyAdmin = $this->getCompanyAdmin($company);

        if ($companyAdmin && $companyAdmin->getId()) {
            $extensionAttributes = $companyAdmin->getExtensionAttributes()->getCompanyAttributes();

            if ($extensionAttributes) {
                $jobTitle = $extensionAttributes->getJobTitle();
            }
        }

        return $jobTitle;
    }

    /**
     * Get company admin email
     *
     * @param CompanyInterface $company
     * @return string
     */
    public function getCompanyAdminEmail(CompanyInterface $company)
    {
        $companyAdmin = $this->getCompanyAdmin($company);

        return ($companyAdmin && $companyAdmin->getId()) ? $companyAdmin->getEmail() : '';
    }

    /**
     * Get sales representative name
     *
     * @param CompanyInterface $company
     * @return string
     */
    public function getSalesRepresentativeName(CompanyInterface $company)
    {
        $salesRepresentative = $this->getSalesRepresentative($company);

        return ($salesRepresentative && $salesRepresentative->getId()) ? $salesRepresentative->getName() : '';
    }

    /**
     * Get sales representative email
     *
     * @param CompanyInterface $company
     * @return string
     */
    public function getSalesRepresentativeEmail(CompanyInterface $company)
    {
        $salesRepresentative = $this->getSalesRepresentative($company);

        return ($salesRepresentative && $salesRepresentative->getId()) ? $salesRepresentative->getEmail() : '';
    }

    /**
     * Get company admin
     *
     * @param CompanyInterface $company
     * @return CustomerInterface
     */
    protected function getCompanyAdmin(CompanyInterface $company)
    {
        if ($this->companyAdmin === null) {
            $this->companyAdmin = $this->companyManagement->getAdminByCompanyId($company->getId());
        }

        return $this->companyAdmin;
    }

    /**
     * Get company sales representative
     *
     * @param CompanyInterface $company
     * @return User
     */
    private function getSalesRepresentative(CompanyInterface $company)
    {
        if ($this->salesRepresentative !== null) {
            return $this->salesRepresentative;
        }

        $salesRepresentativeId = $company->getSalesRepresentativeId();
        if ($salesRepresentativeId) {
            $this->salesRepresentative = $this->userFactory->create()->load($salesRepresentativeId);
        }

        return $this->salesRepresentative;
    }
}
