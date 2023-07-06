<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Block;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyUser;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRule\Model\RuleConditionPool;
use Psr\Log\LoggerInterface;

/**
 * Purchase order block abstract class
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.2.0
 */
class Form extends Template
{
    /**
     * Required resource for edit action authorization.
     */
    const RULE_EDIT_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var RoleManagementInterface
     */
    private $roleManagement;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var RuleConditionPool
     */
    private $ruleConditionPool;

    /**
     * @var array
     */
    private $ruleFormData;

    /**
     * @var PriceCurrencyInterface
     */
    private $amountCurrency;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @param TemplateContext $context
     * @param CompanyContext $companyContext
     * @param RoleRepositoryInterface $roleRepository
     * @param CompanyUser $companyUser
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param Session $customerSession
     * @param RoleManagementInterface $roleManagement
     * @param RuleConditionPool $ruleConditionPool
     * @param PriceCurrencyInterface $amountCurrency
     * @param RuleRepositoryInterface $ruleRepository
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TemplateContext $context,
        CompanyContext $companyContext,
        RoleRepositoryInterface $roleRepository,
        CompanyUser $companyUser,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        Session $customerSession,
        RoleManagementInterface $roleManagement,
        RuleConditionPool $ruleConditionPool,
        PriceCurrencyInterface $amountCurrency,
        RuleRepositoryInterface $ruleRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->companyContext = $companyContext;
        $this->roleRepository = $roleRepository;
        $this->companyUser = $companyUser;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->roleManagement = $roleManagement;
        $this->ruleConditionPool = $ruleConditionPool;
        $this->amountCurrency = $amountCurrency;
        $this->ruleRepository = $ruleRepository;
        $this->initPurchaseOrderRuleData();
    }

    /**
     * Retrieve purchase order rule data
     *
     * @return array
     * @since 100.2.0
     */
    public function getPurchaseOrderRuleData()
    {
        return $this->ruleFormData;
    }

    /**
     * Get all company roles
     *
     * @return RoleInterface[]
     * @since 100.2.0
     */
    public function getCompanyRoles()
    {
        try {
            $companyId = $this->companyUser->getCurrentCompanyId();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(RoleInterface::COMPANY_ID, $companyId)
                ->create();
            return $this->roleRepository->getList($searchCriteria)->getItems();
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return [];
        }
    }

    /**
     * Get all company approver roles
     *
     * @return RoleInterface[]
     * @since 100.2.0
     */
    public function getCompanyApproverRoles()
    {
        try {
            $companyId = $this->companyUser->getCurrentCompanyId();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(RoleInterface::COMPANY_ID, $companyId)
                ->create();
            $roles = $this->roleRepository->getList($searchCriteria)->getItems();
            array_unshift($roles, $this->roleManagement->getManagerRole());
            array_unshift($roles, $this->roleManagement->getAdminRole());
            return $roles;
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return [];
        }
    }

    /**
     * Create initial purchase order rule data
     */
    private function initPurchaseOrderRuleData()
    {
        $ruleId = (int) $this->getRequest()->getParam('rule_id');
        $ruleData = $ruleId ? $this->getPurchaseOrderRuleDataById($ruleId) : $this->getDefaultPurchaseOrderRuleData();

        $sessionRuleData = $this->customerSession->getPurchaseOrderRuleFormData(true);

        if (is_array($sessionRuleData) && !empty($sessionRuleData)) {
            $ruleData = array_replace_recursive($ruleData, $sessionRuleData);
        }

        $this->ruleFormData = $ruleData;
    }

    /**
     * Get rule condition types
     *
     * @return array
     */
    public function getRuleConditions()
    {
        return $this->ruleConditionPool->getConditions();
    }

    /**
     * Get default rule data.
     *
     * @return array
     */
    private function getDefaultPurchaseOrderRuleData()
    {
        return [
            'rule_id' => null,
            'is_active' => 1,
            'name' => '',
            'description' => '',
            'applies_to_all' => '1',
            'conditions' => [
                [
                    'attribute' => '',
                    'operator' => '>',
                    'value' => '',
                    'currency_code' => ''
                ]
            ],
            'approvers' => [],
            'applies_to' => [],
        ];
    }

    /**
     * Get existing rule data by rule id.
     *
     * @param int $ruleId
     * @return array
     */
    private function getPurchaseOrderRuleDataById(int $ruleId)
    {
        try {
            $rule = $this->ruleRepository->get((int) $ruleId);
            foreach (array_keys($this->getDefaultPurchaseOrderRuleData()) as $key) {
                $ruleData[$key] = $rule->getData($key);
            }
            $ruleData['conditions'] = $rule->getConditions()->asArray()['conditions'];
            $ruleData['approvers'] = $this->getApproverRoleIds($rule);
            $ruleData['applies_to'] = $rule->getAppliesToRoleIds();
            return $ruleData;
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return null;
        }
    }

    /**
     * Get the approver role IDs for the rule.
     *
     * @param RuleInterface $rule
     * @return array
     */
    private function getApproverRoleIds(RuleInterface $rule) : array
    {
        $approverRoleIds = $rule->getApproverRoleIds();
        if ($rule->isManagerApprovalRequired()) {
            array_unshift($approverRoleIds, $this->roleManagement->getManagerRole()->getId());
        }
        if ($rule->isAdminApprovalRequired()) {
            array_unshift($approverRoleIds, $this->roleManagement->getAdminRole()->getId());
        }
        return $approverRoleIds;
    }

    /**
     * Check if it is edit form of existing rule
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEdit() : bool
    {
        return (int) $this->getRequest()->getParam('rule_id') ? true : false;
    }

    /**
     * Check if user have edit rule permission
     *
     * @return bool
     * @since 100.2.0
     */
    public function isSaveAllowed() : bool
    {
        return $this->companyContext->isResourceAllowed(self::RULE_EDIT_RESOURCE);
    }
}
