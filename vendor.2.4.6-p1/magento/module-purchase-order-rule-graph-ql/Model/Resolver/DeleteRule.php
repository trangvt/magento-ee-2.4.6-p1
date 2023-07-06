<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Resolver;

use Exception;
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
use Magento\PurchaseOrderGraphQl\Model\GetErrorType;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;

/**
 * Resolver for the purchase order rule
 */
class DeleteRule implements ResolverInterface
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
     * @var GetErrorType
     */
    private GetErrorType $getErrorType;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @param CompanyManagementInterface $companyManagement
     * @param RuleRepositoryInterface $ruleRepository
     * @param GetErrorType $getErrorType
     * @param ResolverAccess $resolverAccess
     * @param Uid $uid
     * @param array $allowedResources
     */
    public function __construct(
        CompanyManagementInterface $companyManagement,
        RuleRepositoryInterface $ruleRepository,
        GetErrorType $getErrorType,
        ResolverAccess $resolverAccess,
        Uid $uid,
        array $allowedResources = []
    ) {
        $this->companyManagement = $companyManagement;
        $this->ruleRepository = $ruleRepository;
        $this->getErrorType = $getErrorType;
        $this->resolverAccess = $resolverAccess;
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
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);
        if (empty($args['input']['approval_rule_uids'])) {
            throw new GraphQlInputException(__('Required parameter "approval_rule_uids" is missing.'));
        }

        $approvalRuleIds = $args['input']['approval_rule_uids'];
        $errors = [];

        $company = $this->companyManagement->getByCustomerId($context->getUserId());
        foreach ($approvalRuleIds as $approvalRuleId) {
            try {
                $rule = $this->ruleRepository->get($this->uid->decode($approvalRuleId));
                if ($rule->getCompanyId() !== (int)$company->getId()) {
                    $message = __('Rule with id "%1" does not exist.', $this->uid->decode($approvalRuleId));
                    $errors[] = [
                        'message' => $message,
                        'type' => $this->getErrorType->execute(new NoSuchEntityException($message))
                    ];
                    continue;
                }
                $this->ruleRepository->delete($rule);
            } catch (Exception $exception) {
                $errors[] = [
                    'message' => $exception->getMessage(),
                    'type' => $this->getErrorType->execute($exception)
                ];
            }
        }

        return ['errors' => $errors];
    }
}
