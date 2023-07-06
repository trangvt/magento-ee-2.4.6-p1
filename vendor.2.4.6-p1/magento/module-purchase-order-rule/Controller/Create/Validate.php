<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Controller\Create;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\Framework\Controller\Result\Json;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\Company\Model\CompanyUser;

/**
 * Controller class for purchase order rule name validation.
 */
class Validate extends AbstractController implements HttpGetActionInterface
{
    /**
     * Required resource for action authorization.
     */
    public const COMPANY_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @param ActionContext $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param CompanyUser $companyUser
     * @param RuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        ActionContext $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        PurchaseOrderConfig $purchaseOrderConfig,
        CompanyUser $companyUser,
        RuleRepositoryInterface $ruleRepository
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->companyUser = $companyUser;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $ruleName = trim($this->getRequest()->getParam('rule_name'));
        $ruleId = $this->getRequest()->getParam('rule_id');

        if (empty($ruleName)) {
            $isValid = false;
        } else {
            try {
                $companyId = $this->companyUser->getCurrentCompanyId();
                $isValid = $this->ruleRepository->isCompanyRuleNameUnique($ruleName, (int)$companyId, $ruleId);
            } catch (LocalizedException $e) {
                $isValid = false;
            }
        }

        $resultJson->setData([
            'isValid' => $isValid
        ]);

        return $resultJson;
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
