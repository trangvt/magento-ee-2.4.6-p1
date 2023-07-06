<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\Company\Model\CompanyUser;
use Magento\PurchaseOrderRule\Model\RuleFactory;
use Magento\PurchaseOrderRule\Model\RuleRepository;
use Magento\Framework\App\RequestInterface;
use Magento\PurchaseOrderRule\Model\Rule\Validate\ValidateRuleBeforeSave;

/**
 * Resolver for the purchase order rule creation mutation
 */
class GetRule
{
    private const KEY_APPROVERS = 'approvers';
    private const KEY_APPLIES_TO = 'applies_to';
    private const KEY_CONDITIONS = 'conditions';

    /**
     * @var RuleFactory
     */
    private RuleFactory $ruleFactory;

    /**
     * @var RuleRepository
     */
    private RuleRepository $ruleRepository;

    /**
     * @var ValidateRuleBeforeSave
     */
    private ValidateRuleBeforeSave $validateRuleBeforeSave;

    /**
     * @var CompanyUser
     */
    private CompanyUser $companyUser;

    /**
     * @var CreateCondition
     */
    private CreateCondition $createCondition;

    /**
     * @var SetRuleApprovers
     */
    private SetRuleApprovers $setRuleApprovers;

    /**
     * @param CompanyUser $companyUser
     * @param RuleFactory $ruleFactory
     * @param RuleRepository $ruleRepository
     * @param ValidateRuleBeforeSave $validateRuleBeforeSave
     * @param CreateCondition $createCondition
     * @param SetRuleApprovers $setRuleApprovers
     */
    public function __construct(
        CompanyUser $companyUser,
        RuleFactory $ruleFactory,
        RuleRepository $ruleRepository,
        ValidateRuleBeforeSave $validateRuleBeforeSave,
        CreateCondition $createCondition,
        SetRuleApprovers $setRuleApprovers
    ) {
        $this->companyUser = $companyUser;
        $this->ruleFactory = $ruleFactory;
        $this->ruleRepository = $ruleRepository;
        $this->validateRuleBeforeSave = $validateRuleBeforeSave;
        $this->createCondition = $createCondition;
        $this->setRuleApprovers = $setRuleApprovers;
    }

    /**
     * Depends on the action, creates new rule with provided data or updates existing rule
     *
     * @param RequestInterface $request
     * @param int $userId
     * @return RuleInterface
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(RequestInterface $request, int $userId): RuleInterface
    {
        $ruleParams = $request->getParams();
        $ruleId = isset($ruleParams[RuleInterface::KEY_ID]) ? (int)$ruleParams[RuleInterface::KEY_ID] : null;

        $ruleData = [
            RuleInterface::KEY_NAME => $ruleParams[RuleInterface::KEY_NAME] ?? '',
            RuleInterface::KEY_DESCRIPTION => $ruleParams[RuleInterface::KEY_DESCRIPTION] ?? null,
            RuleInterface::KEY_IS_ACTIVE => $ruleParams[RuleInterface::KEY_IS_ACTIVE] ?? false,
        ];

        if ($ruleId) {
            $rule = $this->ruleRepository->get((int) $ruleId);
            foreach ($ruleData as $key => $value) {
                $rule->setData($key, $value);
            }
        } else {
            $rule = $this->ruleFactory->create(['data' => $ruleData]);
            $rule->setCreatedBy($userId);
            $rule->setCompanyId((int) $this->companyUser->getCurrentCompanyId());
        }

        if (isset($ruleParams[self::KEY_APPROVERS])) {
            $this->setRuleApprovers->execute($rule, $ruleParams[self::KEY_APPROVERS]);
        }

        $rule = $this->processAppliesTo($rule, $ruleParams);

        if (isset($ruleParams[self::KEY_CONDITIONS]) && is_array($ruleParams[self::KEY_CONDITIONS])) {
            $rule->setConditionsSerialized(
                $this->createCondition->execute($ruleParams[self::KEY_CONDITIONS])->toString()
            );
        } else {
            $rule->setConditionsSerialized('{}');
        }

        $this->validateRuleBeforeSave->execute($rule);

        return $rule;
    }

    /**
     * Set applies to roles to the rule instance
     *
     * @param RuleInterface $rule
     * @param array $ruleParams
     * @return RuleInterface
     */
    private function processAppliesTo(RuleInterface $rule, array $ruleParams): RuleInterface
    {
        if (!empty($ruleParams[self::KEY_APPLIES_TO])) {
            $rule->setAppliesToAll(false);
            $rule->setAppliesToRoleIds($ruleParams[self::KEY_APPLIES_TO]);
            return $rule;
        }

        if (isset($ruleParams[RuleInterface::KEY_APPLIES_TO_ALL])
            && $ruleParams[RuleInterface::KEY_APPLIES_TO_ALL] === '1'
        ) {
            $rule->setAppliesToAll(true);
            return $rule;
        }

        return $rule;
    }
}
