<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Controller\Delete;

use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyUser;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;

/**
 * Controller for deleting purchase order rules
 */
class Index extends AbstractController implements HttpPostActionInterface
{
    /**
     * Required resource for action authorization.
     */
    const COMPANY_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

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
     * Execute the delete request from the user
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');

        try {
            $this->validate($id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The selected rule does not exist.'));
            return $resultRedirect->setRefererUrl();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setRefererUrl();
        }

        try {
            $this->ruleRepository->deleteById($id);

            $this->messageManager->addSuccessMessage(__('The rule has been deleted.'));
            return $resultRedirect->setPath('purchaseorderrule/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setRefererUrl();
        }
    }

    /**
     * Validate if the user can delete this rule
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
            && $this->companyContext->isResourceAllowed(self::COMPANY_RESOURCE);
    }
}
