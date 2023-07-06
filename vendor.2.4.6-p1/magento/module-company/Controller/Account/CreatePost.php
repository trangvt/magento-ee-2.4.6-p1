<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Controller\Account;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Create company account action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class CreatePost extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    /**
     * @var string
     */
    private $formId = 'company_create';

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $objectHelper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \Magento\Company\Model\Action\Validator\Captcha
     */
    private $captchaValidator;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    private $customerAccountManagement;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    private $customerDataFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Company\Model\Create\Session
     */
    private $companyCreateSession;

    /**
     * @var \Magento\Company\Model\CompanyUser|null
     */
    private $companyUser;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Api\DataObjectHelper $objectHelper
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Company\Model\Action\Validator\Captcha $captchaValidator
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Company\Model\Create\Session $companyCreateSession
     * @param \Magento\Company\Model\CompanyUser|null $companyUser
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Api\DataObjectHelper $objectHelper,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Company\Model\Action\Validator\Captcha $captchaValidator,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Company\Model\Create\Session $companyCreateSession,
        \Magento\Company\Model\CompanyUser $companyUser = null
    ) {
        parent::__construct($context);
        $this->userContext = $userContext;
        $this->logger = $logger;
        $this->objectHelper = $objectHelper;
        $this->formKeyValidator = $formKeyValidator;
        $this->captchaValidator = $captchaValidator;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerDataFactory = $customerDataFactory;
        $this->companyCreateSession = $companyCreateSession;
        $this->companyUser = $companyUser ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Company\Model\CompanyUser::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $request = $this->getRequest();
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('*/account/create');

        if (!$this->validateRequest()) {
            return $resultRedirect;
        }

        try {
            if ($this->checkIfLoggedCustomerIsACompanyMember()) {
                /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
                $resultForward = $this->resultFactory
                    ->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD);
                $resultForward->setModule('company');
                $resultForward->setController('accessdenied');
                $resultForward->forward('index');
                return $resultForward;
            }

            $customer = $this->customerDataFactory->create();
            $customerData = $request->getPost('customer', []);

            $this->objectHelper->populateWithArray(
                $customer,
                $customerData,
                \Magento\Customer\Api\Data\CustomerInterface::class
            );
            $customer = $this->customerAccountManagement->createAccount($customer);
            $this->companyCreateSession->setCustomerId($customer->getId());
            $this->messageManager->addSuccessMessage(
                __('Thank you! We\'re reviewing your request and will contact you soon')
            );
            $resultRedirect->setPath('*/account/index');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred on the server. Your changes have not been saved.')
            );
            $this->logger->critical($e);
        }

        return $resultRedirect;
    }

    /**
     * Validate request
     *
     * @return bool
     */
    private function validateRequest(): bool
    {
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return false;
        }

        if (!$this->captchaValidator->validate($this->formId, $this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Incorrect CAPTCHA'));
            return false;
        }

        return true;
    }

    /**
     * Method checks if logged customer is a company customer
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function checkIfLoggedCustomerIsACompanyMember(): bool
    {
        try {
            return (bool)$this->companyUser->getCurrentCompanyId();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }
}
