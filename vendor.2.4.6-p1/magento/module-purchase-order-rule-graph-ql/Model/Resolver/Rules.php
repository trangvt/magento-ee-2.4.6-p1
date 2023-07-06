<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Resolver;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRuleGraphQl\Model\Formatter\Rule as RuleFormatter;

/**
 * Resolver for the purchase order rules
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rules implements ResolverInterface
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
     * @var SearchCriteriaBuilderFactory
     */
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    /**
     * @var SortOrderBuilder
     */
    private SortOrderBuilder $sortOrderBuilder;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @param CompanyManagementInterface $companyManagement
     * @param RuleRepositoryInterface $ruleRepository
     * @param ResolverAccess $resolverAccess
     * @param RuleFormatter $ruleFormatter
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SortOrderBuilder $sortOrderBuilder
     * @param array $allowedResources
     */
    public function __construct(
        CompanyManagementInterface $companyManagement,
        RuleRepositoryInterface $ruleRepository,
        ResolverAccess $resolverAccess,
        RuleFormatter $ruleFormatter,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SortOrderBuilder $sortOrderBuilder,
        array $allowedResources = []
    ) {
        $this->companyManagement = $companyManagement;
        $this->ruleRepository = $ruleRepository;
        $this->resolverAccess = $resolverAccess;
        $this->ruleFormatter = $ruleFormatter;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sortOrderBuilder = $sortOrderBuilder;
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

        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        $pageSize = $args['pageSize'] ?? 20;
        $currentPage = $args['currentPage'] ?? 1;

        $company = $this->companyManagement->getByCustomerId($context->getUserId());

        $sortOrder = $this->sortOrderBuilder
            ->setField(RuleInterface::KEY_ID)
            ->setDescendingDirection()
            ->create();

        $searchResult = $this->ruleRepository->getList(
            $this->searchCriteriaBuilderFactory->create()
                ->setCurrentPage($currentPage)
                ->setPageSize($pageSize)
                ->addFilter(RuleInterface::KEY_COMPANY_ID, $company->getId())
                ->addSortOrder($sortOrder)
                ->create()
        );

        return [
            'items' => array_map(
                function (RuleInterface $rule) {
                    return $this->ruleFormatter->getRuleData($rule);
                },
                $searchResult->getItems()
            ),
            'total_count' => $searchResult->getTotalCount(),
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $searchResult->getSearchCriteria()->getCurrentPage(),
                'total_pages' => $pageSize ? ((int)ceil($searchResult->getTotalCount() / $pageSize)) : 0
            ],
        ];
    }
}
