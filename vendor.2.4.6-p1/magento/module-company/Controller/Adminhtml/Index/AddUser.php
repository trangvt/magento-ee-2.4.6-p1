<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Adminhtml\Index;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\FileUploaderDataResolver;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Validator\EmailAddress;
use Magento\Framework\Validator\IntUtils;
use Magento\Framework\Validator\ValidateException;
use Magento\Framework\Validator\ValidatorChain;

/**
 * Class for add user to the company in admin panel on company edit page.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddUser extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * @var \Magento\Company\Model\CustomerRetriever
     */
    private $customerRetriever;

    /**
     * @var \Magento\Company\Api\CompanyManagementInterface
     */
    protected $companyManagement;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var FileUploaderDataResolver
     */
    private $fileUploaderDataResolver;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Company\Model\CustomerRetriever $customerRetriever
     * @param \Magento\Company\Api\CompanyManagementInterface $companyManagement
     * @param \Psr\Log\LoggerInterface $logger
     * @param CustomerRegistry|null $customerRegistry
     * @param FileUploaderDataResolver|null $fileUploaderDataResolver
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Company\Model\CustomerRetriever $customerRetriever,
        \Magento\Company\Api\CompanyManagementInterface $companyManagement,
        \Psr\Log\LoggerInterface $logger,
        ?CustomerRegistry $customerRegistry = null,
        ?FileUploaderDataResolver $fileUploaderDataResolver = null
    ) {
        parent::__construct($context);
        $this->customerRetriever = $customerRetriever;
        $this->companyManagement = $companyManagement;
        $this->logger = $logger;
        $this->customerRegistry = $customerRegistry ??
            ObjectManager::getInstance()->get(CustomerRegistry::class);
        $this->fileUploaderDataResolver = $fileUploaderDataResolver ??
            ObjectManager::getInstance()->get(FileUploaderDataResolver::class);
    }

    /**
     * @inheritdoc
     *
     * @throws \InvalidArgumentException
     */
    public function execute()
    {
        try {
            $email = $this->getRequestedEmail();
            $websiteId = $this->getRequestedWebsiteId();

            $customer = $this->customerRetriever->retrieveForWebsite(
                $email,
                $websiteId
            );
            if ($customer) {
                $result = $this->getCustomerData($customer);
            } else {
                $result = [
                    'is_new_customer' => true
                ];
            }
        } catch (LocalizedException $e) {
            $result = [
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => __('Something went wrong.')
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($result);
        return $response;
    }

    /**
     * Get requested email.
     *
     * @return string
     * @throws LocalizedException|ValidateException
     */
    private function getRequestedEmail()
    {
        $email = $this->getRequest()->getParam('email');
        $isValidEmail = ValidatorChain::is(
            $email,
            EmailAddress::class
        );
        if (!$isValidEmail) {
            throw new LocalizedException(
                __('Invalid value of "%value" provided for the email field.', ['value' => $email])
            );
        }
        return $email;
    }

    /**
     * Retrieve requested website ID.
     *
     * @return int|null
     * @throws LocalizedException
     */
    private function getRequestedWebsiteId()
    {
        /** @var string|null $websiteId */
        $websiteId = $this->getRequest()->getParam('website_id');
        if ($websiteId !== null) {
            if (!ValidatorChain::is($websiteId, IntUtils::class)) {
                throw new LocalizedException(
                    __(
                        'Invalid value "%value" given for the website ID field.',
                        ['value' => $websiteId]
                    )
                );
            }
            $websiteId = (int)$websiteId;
        }

        return $websiteId;
    }

    /**
     * Get customer data.
     *
     * @param CustomerInterface $customer
     * @return array
     */
    private function getCustomerData(CustomerInterface $customer)
    {
        $isCompanyUser = false;

        try {
            $company = $this->companyManagement->getByCustomerId($customer->getId());
            if ($company) {
                $isCompanyUser = $company->getId()
                    && ($company->getId() != $this->getRequest()->getParam('companyId'));
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e);
        }

        $jobTitle = '';
        $isActive = "0";

        if ($customer->getExtensionAttributes()
            && $customer->getExtensionAttributes()->getCompanyAttributes()
        ) {
            $jobTitle = $customer->getExtensionAttributes()->getCompanyAttributes()->getJobTitle();
            $isActive = $customer->getExtensionAttributes()->getCompanyAttributes()->getStatus();
        }

        $result = [
            'customer' => $customer->getId(),
            'firstname' => $customer->getFirstname(),
            'prefix' => $customer->getPrefix(),
            'middlename' => $customer->getMiddlename(),
            'lastname' => $customer->getLastname(),
            'suffix' => $customer->getSuffix(),
            'gender' => $customer->getGender(),
            'is_company_user' => $isCompanyUser,
            'job_title' => $jobTitle,
            'is_active' => boolval($isActive),
            'website_id' => $customer->getWebsiteId()
        ];
        $customAttributes = $customer->getCustomAttributes();
        foreach ($customAttributes as $attribute) {
            $result[$attribute->getAttributeCode()] = $attribute->getValue();
        }
        /** @var Customer $customerEntity */
        $customerEntity = $this->customerRegistry->retrieve($customer->getId());
        $this->fileUploaderDataResolver->overrideFileUploaderData($customerEntity, $result);

        return $result;
    }
}
