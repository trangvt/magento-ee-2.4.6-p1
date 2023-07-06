<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Controller\View;

use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyUser;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRule\Controller\Edit\Index as PurchaseOrderRuleControllerEdit;

/**
 * Controller class for purchase order rule view.
 */
class Index implements HttpGetActionInterface
{
    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param RuleRepositoryInterface $ruleRepository
     * @param CompanyUser $companyUser
     * @param CompanyContext $companyContext
     * @param ResultFactory $resultFactory
     * @param ResponseInterface $response
     */
    public function __construct(
        RequestInterface $request,
        ManagerInterface $messageManager,
        PurchaseOrderConfig $purchaseOrderConfig,
        RuleRepositoryInterface $ruleRepository,
        CompanyUser $companyUser,
        CompanyContext $companyContext,
        ResultFactory $resultFactory,
        ResponseInterface $response
    ) {
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->ruleRepository = $ruleRepository;
        $this->companyUser = $companyUser;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->companyContext = $companyContext;
        $this->resultFactory = $resultFactory;
        $this->response = $response;
    }

    /**
     * Purchase order rule edit form.
     *
     * @return ResponseInterface|ResultInterface|Page
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $ruleId = $this->request->getParam('rule_id');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->companyContext->getCustomerId()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        if (!$this->isAllowed()) {
            if ($this->companyContext->isCurrentUserCompanyUser()) {
                return $resultRedirect->setPath('company/accessdenied');
            }
            return $resultRedirect->setPath('noroute');
        }

        if ($this->companyContext->isResourceAllowed(PurchaseOrderRuleControllerEdit::RULE_EDIT_RESOURCE)) {
            return $resultRedirect->setPath('purchaseorderrule/edit', ['rule_id' => $ruleId]);
        }

        try {
            $this->validate($ruleId);
        } catch (NoSuchEntityException $e) {
            return $resultRedirect->setPath('noroute');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('purchaseorderrule/index');
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('View Approval Rule'));

        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');

        if ($navigationBlock) {
            $navigationBlock->setActive('purchaseorderrule/');
        }

        return $resultPage;
    }

    /**
     * Validate if the user can view this rule
     *
     * @param string $id
     * @throws NoSuchEntityException
     * @throws LocalizedException
     *
     * @return void
     */
    private function validate($id)
    {
        $rule = $this->ruleRepository->get($id);

        if (!$rule || ($rule && (int) $rule->getCompanyId() !== (int) $this->companyUser->getCurrentCompanyId())) {
            throw new NoSuchEntityException();
        }
    }

    /**
     * Check if this action is allowed.
     *
     * Verify that the user belongs to a company with purchase orders enabled.
     * Verify that the user has the required permission to perform the action.
     *
     * @return bool
     */
    private function isAllowed()
    {
        return $this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite()
            && (
                $this->companyContext->isResourceAllowed(PurchaseOrderRuleControllerEdit::RULE_VIEW_RESOURCE) ||
                $this->companyContext->isResourceAllowed(PurchaseOrderRuleControllerEdit::RULE_EDIT_RESOURCE)
            );
    }
}
