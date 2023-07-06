<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCreditGraphQl\Model\Resolver;

use Magento\CompanyCredit\Model\PaymentMethodStatus;
use Magento\CompanyCreditGraphQl\Model\Credit\HistoryType;
use Magento\CompanyCreditGraphQl\Model\Credit\OperationExtractor;
use Magento\CompanyCreditGraphQl\Model\CreditHistory as CreditHistoryModel;
use Magento\CompanyCreditGraphQl\Model\History\User;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Company credit history resolver
 */
class CreditHistory implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var CreditHistoryModel
     */
    private $creditHistory;

    /**
     * @var OperationExtractor
     */
    private $operationExtractor;

    /**
     * @var User
     */
    private $historyUser;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @var PaymentMethodStatus
     */
    private $paymentMethodStatus;

    /**
     * @var HistoryType
     */
    private $historyType;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param CreditHistoryModel $creditHistory
     * @param OperationExtractor $operationExtractor
     * @param User $historyUser
     * @param ResolverAccess $resolverAccess
     * @param PaymentMethodStatus $paymentMethodStatus
     * @param HistoryType $historyType
     * @param array $allowedResources
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CreditHistoryModel $creditHistory,
        OperationExtractor $operationExtractor,
        User $historyUser,
        ResolverAccess $resolverAccess,
        PaymentMethodStatus $paymentMethodStatus,
        HistoryType $historyType,
        array $allowedResources = []
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->creditHistory = $creditHistory;
        $this->operationExtractor = $operationExtractor;
        $this->historyUser = $historyUser;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->paymentMethodStatus = $paymentMethodStatus;
        $this->historyType = $historyType;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if (!$this->paymentMethodStatus->isEnabled()) {
            throw new GraphQlInputException(__('"Payment on Account" is disabled.'));
        }

        $this->validateArgs($args);

        $company = $value['model'];
        $this->resolverAccess->isAllowed($this->allowedResources);

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        if (isset($args['filter']['updated_by'])) {
            $userIds = $this->historyUser->getHistoryUserIdsByName(
                $args['filter']['updated_by'],
                (int)$company->getId()
            );

            $searchCriteriaBuilder->addFilter('user_id', $userIds, 'in');
        }
        if (isset($args['filter']['operation_type'])) {
            $searchCriteriaBuilder->addFilter(
                'type',
                $this->historyType->getHistoryTypeId($args['filter']['operation_type'])
            );
        }
        if (isset($args['filter']['custom_reference_number'])) {
            $searchCriteriaBuilder->addFilter(
                'custom_reference_number',
                $args['filter']['custom_reference_number']
            );
        }

        $searchCriteriaBuilder->addFilter('company_id', $company->getEntityId());
        $searchCriteriaBuilder->setPageSize($args['pageSize']);
        $searchCriteriaBuilder->setCurrentPage($args['currentPage']);

        $searchResults = $this->creditHistory->getList($searchCriteriaBuilder->create());

        return [
            'items' => $this->getCompanyCreditOperations($searchResults),
            'page_info' => [
                'page_size' => $args['pageSize'],
                'current_page' => $searchResults->getSearchCriteria()->getCurrentPage(),
                'total_pages' => $args['pageSize'] ?
                    ((int)ceil($searchResults->getTotalCount() / $args['pageSize'])) : 0
            ],
            'total_count' => $searchResults->getTotalCount()
        ];
    }

    /**
     * Get company credit operations
     *
     * @param SearchResultsInterface $searchResults
     * @return array
     */
    private function getCompanyCreditOperations(SearchResultsInterface $searchResults): array
    {
        $companyCreditOperations = [];

        foreach ($searchResults->getItems() as $creditOperation) {
            $operationItem = $this->operationExtractor->extractOperation($creditOperation);
            $companyCreditOperations[] = $operationItem;
        }

        return $companyCreditOperations;
    }

    /**
     * Validate resolver arguments
     *
     * @param array $args
     * @throws GraphQlInputException
     */
    private function validateArgs(array $args): void
    {
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
    }
}
