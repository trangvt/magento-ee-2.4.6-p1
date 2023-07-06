<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Resolver;

use Magento\Company\Api\CompanyManagementInterface;
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
use Magento\PurchaseOrderRuleGraphQl\Model\Formatter\Rule as RuleFormatter;

/**
 * Resolver for the purchase order rule
 */
class Rule implements ResolverInterface
{
    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var RuleRepositoryInterface
     */
    private RuleRepositoryInterface $ruleRepository;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var RuleFormatter
     */
    private RuleFormatter $ruleFormatter;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @param CompanyManagementInterface $companyManagement
     * @param RuleRepositoryInterface $ruleRepository
     * @param ResolverAccess $resolverAccess
     * @param RuleFormatter $ruleFormatter
     * @param Uid $uid
     * @param array $allowedResources
     */
    public function __construct(
        CompanyManagementInterface $companyManagement,
        RuleRepositoryInterface $ruleRepository,
        ResolverAccess $resolverAccess,
        RuleFormatter $ruleFormatter,
        Uid $uid,
        array $allowedResources = []
    ) {
        $this->companyManagement = $companyManagement;
        $this->ruleRepository = $ruleRepository;
        $this->resolverAccess = $resolverAccess;
        $this->ruleFormatter = $ruleFormatter;
        $this->uid = $uid;
        $this->allowedResources = $allowedResources;
    }

    /**
     * Resolve PurchaseOrderApprovalRuleMetadata type
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws LocalizedException
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
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);

        if (empty($args['uid'])) {
            throw new GraphQlInputException(__('Required parameter "uid" is missing.'));
        }

        $company = $this->companyManagement->getByCustomerId($context->getUserId());

        try {
            $rule = $this->ruleRepository->get($this->uid->decode((string) $args['uid']));
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlInputException(__('Rule with id "%1" does not exist.', $args['uid']), $exception);
        }

        if ($rule->getCompanyId() !== (int)$company->getId()) {
            throw new GraphQlInputException(__('Rule with id "%1" does not exist.', $args['uid']));
        }

        return $this->ruleFormatter->getRuleData($rule);
    }
}
