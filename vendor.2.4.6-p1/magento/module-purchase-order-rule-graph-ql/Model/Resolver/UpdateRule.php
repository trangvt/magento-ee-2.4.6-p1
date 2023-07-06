<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRuleGraphQl\Model\Formatter\Rule;
use Magento\PurchaseOrderRuleGraphQl\Model\UpdateRule as RuleUpdater;

/**
 * Resolver for the purchase order rule update mutation
 */
class UpdateRule implements ResolverInterface
{
    /**
     * @var RuleUpdater
     */
    private RuleUpdater $ruleUpdater;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var Rule
     */
    private Rule $ruleFormatter;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @var RuleRepositoryInterface
     */
    private RuleRepositoryInterface $ruleRepository;

    /**
     * @param ResolverAccess $resolverAccess
     * @param RuleUpdater $ruleUpdater
     * @param Rule $ruleFormatter
     * @param Uid $uid
     * @param RuleRepositoryInterface $ruleRepository
     * @param array $allowedResources
     */
    public function __construct(
        ResolverAccess $resolverAccess,
        RuleUpdater $ruleUpdater,
        Rule $ruleFormatter,
        Uid $uid,
        RuleRepositoryInterface $ruleRepository,
        array $allowedResources = []
    ) {
        $this->resolverAccess = $resolverAccess;
        $this->ruleUpdater = $ruleUpdater;
        $this->ruleFormatter = $ruleFormatter;
        $this->uid = $uid;
        $this->ruleRepository = $ruleRepository;
        $this->allowedResources = $allowedResources;
    }

    /**
     * Resolve CreatePurchaseOrderApprovalRule mutation
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        /** @var \Magento\GraphQl\Model\Query\ContextInterface $context */
        if ($context->getExtensionAttributes()->getIsCustomer() === false) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);

        try {
            $rule = $this->ruleRepository->get($this->uid->decode($args['input']['uid']));
            $updatedRule = $this->ruleRepository->save($this->ruleUpdater->update($rule, $args['input']));
        } catch (\Exception $exception) {
            throw new GraphQlInputException(__($exception->getMessage()));
        }
        return $this->ruleFormatter->getRuleData($updatedRule);
    }
}
