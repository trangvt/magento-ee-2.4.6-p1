<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Model for getting all negotiable quote data by filter
 */
class GetAllNegotiableQuoteByFilter
{
    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var Quote
     */
    private $quoteHelper;

    /**
     * @var RestrictionInterface
     */
    private $restriction;

    /**
     * @var QuoteIdMask
     */
    private $quoteIdMaskResource;

    /**
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param Customer $customer
     * @param Quote $quoteHelper
     * @param RestrictionInterface $restriction
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SortOrderBuilder $sortOrderBuilder,
        Customer $customer,
        Quote $quoteHelper,
        RestrictionInterface $restriction,
        QuoteIdMask $quoteIdMaskResource
    ) {
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customer = $customer;
        $this->quoteHelper = $quoteHelper;
        $this->restriction = $restriction;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }

    /**
     * Get all negotiable quote data by the custom filter
     *
     * @param array $filterArgs
     * @param int $currentPage
     * @param int $pageSize
     * @param int $customerId
     * @param WebsiteInterface $website
     * @param array $sortArgs
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(
        array $filterArgs,
        int $currentPage,
        int $pageSize,
        int $customerId,
        WebsiteInterface $website,
        array $sortArgs
    ): array {
        $this->customer->validateCanView($customerId);
        $validStoreIds = $website->getStoreIds();

        $viewableCustomerIds = $this->customer->getViewableCustomerIds($customerId);
        $filterGroups = $this->createFilterGroups($filterArgs);
        $this->searchCriteriaBuilder->setFilterGroups($filterGroups);
        $this->searchCriteriaBuilder->addFilter('customer_id', $viewableCustomerIds, 'in');
        $this->searchCriteriaBuilder->addFilter('store_id', $validStoreIds, 'in');
        $this->searchCriteriaBuilder->setCurrentPage($currentPage);
        $this->searchCriteriaBuilder->setPageSize($pageSize);
        $sortOptions = $this->getSortOptions();
        $sortOrders = $this->getSortOrders($sortArgs);
        $this->searchCriteriaBuilder->setSortOrders($sortOrders);
        $quotes = $this->negotiableQuoteRepository->getList($this->searchCriteriaBuilder->create());

        $countLists = (int)$quotes->getTotalCount();
        $data = $this->getQuoteFormatData($countLists, $pageSize, $currentPage);

        $quotes = $quotes->getItems();
        $quoteIds = [];
        /** @var CartInterface $quote */
        foreach ($quotes as $quote) {
            $quoteIds[] = (int)$quote->getId();
        }
        $maskedIds = $this->quoteIdMaskResource->getMaskedQuoteIds($quoteIds);

        foreach ($quotes as $quote) {
            $maskedQuoteId = $maskedIds[(int)$quote->getId()];
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
            $this->restriction->setQuote($quote);
            $snapshotQuote = !$this->restriction->canSubmit() ? $this->quoteHelper->getSnapshotQuote($quote) : null;
            $data['items'][$maskedQuoteId] = [
                'uid' => $maskedQuoteId,
                'status' => $negotiableQuote->getStatus(),
                'name' => $negotiableQuote->getQuoteName(),
                'created_at' => $snapshotQuote ? $snapshotQuote->getCreatedAt() : $quote->getCreatedAt(),
                'updated_at' => $snapshotQuote ? $snapshotQuote->getUpdatedAt() : $quote->getUpdatedAt(),
                'model' => $snapshotQuote ?: $quote,
            ];
        }
        $data['sort_fields']['default'] = $sortOptions['default']['value'];
        unset($sortOptions["default"]);
        $data['sort_fields']['options'] = $sortOptions;

        if ($data['total_count'] !== 0 && ($data['page_info']['total_pages'] < $data['page_info']['current_page'])) {
            throw new GraphQlInputException(
                __(
                    'The specified currentPage value %1 is greater than the number of pages available.',
                    $data['page_info']['current_page']
                )
            );
        }

        return $data;
    }

    /**
     * Get sort orders based on input args
     *
     * @param array $sortArgs
     * @return array
     * @throws GraphQlInputException
     */
    private function getSortOrders(array $sortArgs): array
    {
        $sortOrders = [];
        $sortOptions = $this->getSortOptions();
        if (empty($sortArgs)) {
            $defaultSortOrder = $this->sortOrderBuilder
                ->setField($sortOptions['default']['code'])
                ->setDirection(SortOrder::SORT_DESC)
                ->create();
            $sortOrders[] = $defaultSortOrder;
            return $sortOrders;
        }

        if (isset($sortOptions[$sortArgs['sort_field']])) {
            $sortOption = $sortOptions[$sortArgs['sort_field']];
            $sortDirection = $sortArgs['sort_direction'];
            $sortOrder = $this->sortOrderBuilder
                ->setField($sortOption['code'])
                ->setDirection($sortDirection)
                ->create();
            $sortOrders[] = $sortOrder;
        }
        return $sortOrders;
    }

    /**
     * Format the negotiable quote response
     *
     * @param int $countLists
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     */
    private function getQuoteFormatData(int $countLists, int $pageSize, int $currentPage): array
    {
        return [
            'total_count' => $countLists,
            'items' => [],
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => (int)ceil($countLists/$pageSize)
            ],
        ];
    }

    /**
     * Create filter for filtering the requested categories id's based on url_key, ids, name in the result.
     *
     * @param array $filterArgs
     * @return FilterGroup[]
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    private function createFilterGroups(array $filterArgs): array
    {
        $filterGroups = [];
        if (!empty($filterArgs)) {
            $filters = [];
            foreach ($filterArgs as $condition) {
                foreach ($condition as $condType => $value) {
                    if ($condType === 'match') {
                        if (is_array($value)) {
                            throw new GraphQlInputException(__('Invalid match filter.'));
                        }
                        $searchValue = str_replace('%', '', $value);
                        $filters[] = $this->filterBuilder
                            ->setField(NegotiableQuoteInterface::QUOTE_NAME)
                            ->setConditionType('like')
                            ->setValue("%{$searchValue}%")
                            ->create();
                    } else {
                        $quoteIds = array_values($this->quoteIdMaskResource->getUnmaskedQuoteIds((array) $value, true));
                        $filters[] = $this->filterBuilder
                            ->setField('main_table.' . CartInterface::KEY_ENTITY_ID)
                            ->setConditionType($condType)
                            ->setValue($quoteIds)
                            ->create();
                    }
                }
            }

            $this->filterGroupBuilder->setFilters($filters);
            $filterGroups[] = $this->filterGroupBuilder->create();
        }

        return $filterGroups;
    }

    /**
     * Data provider for sort options
     *
     * @return array
     */
    private function getSortOptions(): array
    {
        return [
            "default" => [
                "value" => "CREATED_AT",
                "code" => "created_at"
            ],
            "QUOTE_NAME" => [
                "label" => "Quote Name",
                "code" => "quote_name",
                "value" => "QUOTE_NAME"
            ],
            "STATUS" => [
                "label" => "Status",
                "code" => "status",
                "value" => "STATUS"
            ],
            "CREATED_AT" => [
                "label" => "Created",
                "code" => "created_at",
                "value" => "CREATED_AT"
            ],
            "UPDATED_AT" => [
                "label" => "Last Updated",
                "code" => "updated_at",
                "value" => "UPDATED_AT"
            ]
        ];
    }
}
