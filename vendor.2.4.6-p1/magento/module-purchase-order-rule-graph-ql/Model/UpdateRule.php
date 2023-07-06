<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\PurchaseOrderRule\Model\Rule\CreateCondition;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Model\Rule\SetRuleApprovers;
use Magento\PurchaseOrderRule\Model\Rule\Validate\ValidateRuleBeforeSave;

/**
 * Purchase Order Approval Rule updater
 */
class UpdateRule
{
    private const KEY_APPROVERS = 'approvers';
    private const KEY_APPLIES_TO = 'applies_to';

    /**
     * @var ValidateRuleBeforeSave
     */
    private ValidateRuleBeforeSave $validateRuleBeforeSave;

    /**
     * @var CreateCondition
     */
    private CreateCondition $createCondition;

    /**
     * @var SetRuleApprovers
     */
    private SetRuleApprovers $setRuleApprovers;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @param ValidateRuleBeforeSave $validateRuleBeforeSave
     * @param CreateCondition $createCondition
     * @param SetRuleApprovers $setRuleApprovers
     * @param Uid $uid
     */
    public function __construct(
        ValidateRuleBeforeSave $validateRuleBeforeSave,
        CreateCondition $createCondition,
        SetRuleApprovers $setRuleApprovers,
        Uid $uid
    ) {
        $this->validateRuleBeforeSave = $validateRuleBeforeSave;
        $this->createCondition = $createCondition;
        $this->setRuleApprovers = $setRuleApprovers;
        $this->uid = $uid;
    }

    /**
     * Updates PO approval rule from provided params
     *
     * @param RuleInterface $rule
     * @param array $args
     * @return RuleInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     */
    public function update(RuleInterface $rule, array $args): RuleInterface
    {
        if (isset($args[RuleInterface::KEY_NAME])) {
            $rule->setName($args[RuleInterface::KEY_NAME]);
        }

        if (isset($args[RuleInterface::KEY_DESCRIPTION])) {
            $rule->setDescription($args[RuleInterface::KEY_DESCRIPTION]);
        }

        if (isset($args['status'])) {
            $rule->setIsActive($args['status'] === 'ENABLED');
        }

        if (isset($args[self::KEY_APPLIES_TO])) {
            if (empty($args[self::KEY_APPLIES_TO])) {
                $rule->setAppliesToAll(true);
            } else {
                $rule->setAppliesToAll(false);
                $rule->setAppliesToRoleIds(
                    array_map(
                        function ($id) {
                            return $this->uid->decode((string)$id);
                        },
                        $args[self::KEY_APPLIES_TO]
                    )
                );
            }
        }

        if (isset($args['condition'])) {
            $rule->setConditionsSerialized($this->createCondition->execute([$args['condition']])->toString());
        }

        if (isset($args[self::KEY_APPROVERS])) {
            $this->setRuleApprovers->execute(
                $rule,
                array_map(
                    function ($id) {
                        return $this->uid->decode((string)$id);
                    },
                    $args[self::KEY_APPROVERS]
                )
            );
        }

        $this->validateRuleBeforeSave->execute($rule);

        return $rule;
    }
}
