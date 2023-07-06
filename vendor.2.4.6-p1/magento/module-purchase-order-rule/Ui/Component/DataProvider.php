<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Ui\Component;

use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyManagement;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover;

/**
 * DataProvider for purchase orders selection on grid
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * Tab name on Purchase Orders listing page
     */
    public const TAB_NAME = 'require_my_approval';

    /**
     * @var array|CompanyManagement
     */
    private $companyManagement;

    /**
     * @var array|CompanyContext
     */
    private $companyContext;

    /**
     * @var AppliedRuleApprover
     */
    private $appliedRuleApprover;

    /**
     * @var AppliedRuleRepositoryInterface
     */
    private $appliedRuleRepository;

    /**
     * @var AppliedRuleApproverRepositoryInterface
     */
    private $appliedRuleApproverRepository;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param CompanyManagement $companyManagement
     * @param CompanyContext $companyContext
     * @param AppliedRuleApprover $appliedRuleApprover
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param array $meta
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CompanyManagement $companyManagement,
        CompanyContext $companyContext,
        AppliedRuleApprover $appliedRuleApprover,
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->companyManagement = $companyManagement;
        $this->companyContext = $companyContext;
        $this->appliedRuleApprover = $appliedRuleApprover;
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
    }

    /**
     * @inheritdoc
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $customerId = $this->companyContext->getCustomerId();

        $items = parent::searchResultToOutput($searchResult);
        foreach ($items['items'] as $key => $item) {
            $appliedRules = $this->appliedRuleRepository->getListByPurchaseOrderId((int)$item['entity_id'])->getItems();

            $items['items'][$key]['approvedByMe'] = false;
            foreach ($appliedRules as $appliedRule) {
                $approvers = $this->getAppliedRuleApprovers($appliedRule);
                if (count($approvers) > 0) {
                    foreach ($approvers as $approver) {
                        if ((int)$approver->getCustomerId() === (int)$customerId) {
                            $items['items'][$key]['approvedByMe'] = (bool)$approver->getStatus();
                        }
                    }
                }
            }
        }
        $items['totalFilteredRecords'] =
            $this->appliedRuleApprover->getPurchaseOrdersRequireApprovalByCurrentCustomer()->getTotalCount();
        $items['tabName'] = self::TAB_NAME;

        return $items;
    }

    /**
     * Retrieve the approvers for the applied rule
     *
     * @param AppliedRuleInterface $appliedRule
     *
     * @return array|AppliedRuleApproverInterface[]
     * @since 100.2.0
     */
    private function getAppliedRuleApprovers(AppliedRuleInterface $appliedRule)
    {
        try {
            $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int) $appliedRule->getId());
            return $approvers->getItems();
        } catch (\Exception $e) {
            return [];
        }
    }
}
