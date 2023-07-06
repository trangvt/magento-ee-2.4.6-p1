<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Controller\Role;

use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyUser;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Controller for Role creation and editing
 */
class Edit extends \Magento\Company\Controller\AbstractAction implements HttpGetActionInterface
{
    /**
     * Authorization level of a company session.
     */
    public const COMPANY_RESOURCE = 'Magento_Company::roles_edit';

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @param Context $context
     * @param CompanyContext $companyContext
     * @param LoggerInterface $logger
     * @param CompanyUser $companyUser
     * @param RoleRepositoryInterface $roleRepository
     */
    public function __construct(
        Context $context,
        CompanyContext $companyContext,
        LoggerInterface $logger,
        CompanyUser $companyUser,
        RoleRepositoryInterface $roleRepository
    ) {
        parent::__construct($context, $companyContext, $logger);
        $this->companyUser = $companyUser;
        $this->roleRepository = $roleRepository;
    }

    /**
     * Roles and permissions edit.
     *
     * @return void|Redirect
     * @throws \RuntimeException
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Add New Role'));
        $id = $this->getRequest()->getParam('id');
        if ($id !== null) {
            try {
                $role = $this->roleRepository->get($id);
            } catch (NoSuchEntityException $e) {
                return $this->addMessageAndRedirect();
            }

            if ($role->getCompanyId() != $this->companyUser->getCurrentCompanyId()) {
                return $this->addMessageAndRedirect();
            }
            $resultPage->getConfig()->getTitle()->set(__('Edit Role'));
        }
        return $resultPage;
    }

    /**
     * Adds error message and redirects to role list page
     *
     * @return Redirect
     */
    private function addMessageAndRedirect()
    {
        $this->messageManager->addErrorMessage(__('Bad Request'));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/role/');
    }
}
