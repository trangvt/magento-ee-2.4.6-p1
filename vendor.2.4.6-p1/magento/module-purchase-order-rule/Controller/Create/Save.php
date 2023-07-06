<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Controller\Create;

use Magento\Company\Model\CompanyContext;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRule\Model\Rule\GetRule;
use Psr\Log\LoggerInterface;

/**
 * Controller class for purchase order rule form.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends AbstractController implements HttpPostActionInterface
{
    /**
     * Required resource for action authorization.
     */
    private const COMPANY_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

    /**
     * @var PurchaseOrderConfig
     */
    private PurchaseOrderConfig $purchaseOrderConfig;

    /**
     * @var GetRule
     */
    private GetRule $getRule;

    /**
     * @var RuleRepositoryInterface
     */
    private RuleRepositoryInterface $ruleRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @param ActionContext $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param RuleRepositoryInterface $ruleRepository
     * @param LoggerInterface $logger
     * @param Session $customerSession
     * @param GetRule $getRule
     */
    public function __construct(
        ActionContext $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        PurchaseOrderConfig $purchaseOrderConfig,
        RuleRepositoryInterface $ruleRepository,
        LoggerInterface $logger,
        Session $customerSession,
        GetRule $getRule
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->getRule = $getRule;
    }

    /**
     * Consume data from form and create new Purchase Order rule in database
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute()
    {
        $request = $this->getRequest();
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->customerSession->setPurchaseOrderRuleFormData($request->getPostValue());

        try {
            $rule = $this->getRule->execute($request, (int) $this->customerSession->getCustomerId());
            $successMessage = $rule->getId()
                ? __('The approval rule has been updated.')
                : __('The approval rule has been created.');

            $this->ruleRepository->save($rule);
            $this->messageManager->addSuccessMessage($successMessage);
            $this->customerSession->setPurchaseOrderRuleFormData([]);
            return $resultRedirect->setPath('purchaseorderrule');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setRefererUrl();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $resultRedirect->setRefererUrl();
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
