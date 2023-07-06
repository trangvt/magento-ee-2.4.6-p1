<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Controller\Edit;

use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyUser;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Controller class for purchase order rule form.
 */
class Index extends AbstractController implements HttpGetActionInterface
{
    /**
     * Required resource for action authorization.
     */
    /**
     * Required resource for view action authorization.
     */
    const RULE_VIEW_RESOURCE = 'Magento_PurchaseOrderRule::view_approval_rules';

    /**
     * Required resource for edit action authorization.
     */
    const RULE_EDIT_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

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
     * @param ActionContext $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param RuleRepositoryInterface $ruleRepository
     * @param CompanyUser $companyUser
     */
    public function __construct(
        ActionContext $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        PurchaseOrderConfig $purchaseOrderConfig,
        RuleRepositoryInterface $ruleRepository,
        CompanyUser $companyUser
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->ruleRepository = $ruleRepository;
        $this->companyUser = $companyUser;
    }

    /**
     * Purchase order rule edit form.
     *
     * @return Page|Redirect
     */
    public function execute()
    {
        $ruleId = $this->_request->getParam('rule_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $this->validate($ruleId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The selected rule does not exist.'));
            return $resultRedirect->setPath('purchaseorderrule/create');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('purchaseorderrule/create');
        }

        if (!$this->companyContext->isResourceAllowed(self::RULE_EDIT_RESOURCE)) {
            return $resultRedirect->setPath('purchaseorderrule/view', ['rule_id' => $ruleId]);
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Edit Approval Rule'));
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');

        if ($navigationBlock) {
            $navigationBlock->setActive('purchaseorderrule/');
        }

        return $resultPage;
    }

    /**
     * Validate if the user can edit this rule
     *
     * @param int $id
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function validate($id)
    {
        if (!$id) {
            throw new NoSuchEntityException();
        }

        $rule = $this->ruleRepository->get((int) $id);

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
    protected function isAllowed()
    {
        return $this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite()
            && (
                $this->companyContext->isResourceAllowed(self::RULE_EDIT_RESOURCE) ||
                $this->companyContext->isResourceAllowed(self::RULE_VIEW_RESOURCE)
            );
    }
}
