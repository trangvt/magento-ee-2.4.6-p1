<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Account;

use InvalidArgumentException;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\View\Result\Page;

/**
 * Controller Class Create to render Company Form page
 */
class Create extends Action implements HttpGetActionInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CompanyManagementInterface|null
     */
    private $companyManagement;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CompanyManagementInterface|null $companyManagement
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CompanyManagementInterface $companyManagement = null
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->companyManagement = $companyManagement ?:
            ObjectManager::getInstance()->get(CompanyManagementInterface::class);
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        if ($this->customerSession->isLoggedIn()) {
            $company = $this->companyManagement->getByCustomerId($this->customerSession->getCustomerId());
            if ($company) {
                /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
                $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
                $resultForward->setModule('company');
                $resultForward->setController('accessdenied');
                $resultForward->forward('index');
                return $resultForward;
            }
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('New Company'));
        return $resultPage;
    }
}
