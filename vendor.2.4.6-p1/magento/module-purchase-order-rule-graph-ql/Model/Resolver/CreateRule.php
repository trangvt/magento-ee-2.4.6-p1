<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Resolver;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleInterfaceFactory;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRuleGraphQl\Model\Formatter\Rule;
use Magento\PurchaseOrderRuleGraphQl\Model\UpdateRule as UpdateRuleModel;

/**
 * Resolver for the purchase order rule creation mutation
 */
class CreateRule implements ResolverInterface
{
    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var UpdateRuleModel
     */
    private UpdateRuleModel $updateRule;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var Rule
     */
    private Rule $ruleFormatter;

    /**
     * @var RuleInterfaceFactory
     */
    private RuleInterfaceFactory $ruleFactory;

    /**
     * @var RuleRepositoryInterface
     */
    private RuleRepositoryInterface $ruleRepository;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @param CompanyManagementInterface $companyManagement
     * @param ResolverAccess $resolverAccess
     * @param UpdateRuleModel $updateRule
     * @param Rule $ruleFormatter
     * @param RuleInterfaceFactory $ruleFactory
     * @param RuleRepositoryInterface $ruleRepository
     * @param array $allowedResources
     */
    public function __construct(
        CompanyManagementInterface $companyManagement,
        ResolverAccess $resolverAccess,
        UpdateRuleModel $updateRule,
        Rule $ruleFormatter,
        RuleInterfaceFactory $ruleFactory,
        RuleRepositoryInterface $ruleRepository,
        array $allowedResources = []
    ) {
        $this->companyManagement = $companyManagement;
        $this->resolverAccess = $resolverAccess;
        $this->updateRule = $updateRule;
        $this->ruleFormatter = $ruleFormatter;
        $this->ruleFactory = $ruleFactory;
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
     * @throws GraphQlInputException
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
        $userId = $context->getUserId();
        $company = $this->companyManagement->getByCustomerId($userId);

        try {
            $rule = $this->ruleFactory->create(
                [
                    'data' => [
                        RuleInterface::KEY_CREATED_BY => $userId,
                        RuleInterface::KEY_COMPANY_ID => (int) $company->getId()
                    ]
                ]
            );
            $createdRule = $this->ruleRepository->save($this->updateRule->update($rule, $args['input']));
        } catch (\Exception $exception) {
            throw new GraphQlInputException(__($exception->getMessage()));
        }
        return $this->ruleFormatter->getRuleData($createdRule);
    }
}
