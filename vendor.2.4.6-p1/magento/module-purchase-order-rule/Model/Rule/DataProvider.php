<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule;

use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\Role;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as FrameworkDataProvider;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRule\Model\RuleConditionPool;
use Psr\Log\LoggerInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Framework\UrlInterface;

/**
 * Provide data to the listing grid for the approval rules
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends FrameworkDataProvider
{
    /**
     * Required resource for action authorization.
     */
    private const COMPANY_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var RoleManagementInterface
     */
    private $roleManagement;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var null|array
     */
    private $companyRoles = null;

    /**
     * @var RuleConditionPool
     */
    private $ruleConditionPool;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param RuleRepositoryInterface $ruleRepository
     * @param RoleRepositoryInterface $roleRepository
     * @param RoleManagementInterface $roleManagement
     * @param CompanyUser $companyUser
     * @param LoggerInterface $logger
     * @param Json $serializer
     * @param RuleConditionPool $ruleConditionPool
     * @param CustomerRepository $customerRepository
     * @param AuthorizationInterface $authorization
     * @param UrlInterface $url
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
        RuleRepositoryInterface $ruleRepository,
        RoleRepositoryInterface $roleRepository,
        RoleManagementInterface $roleManagement,
        CompanyUser $companyUser,
        LoggerInterface $logger,
        Json $serializer,
        RuleConditionPool $ruleConditionPool,
        CustomerRepository $customerRepository,
        AuthorizationInterface $authorization,
        UrlInterface $url,
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

        $this->ruleRepository = $ruleRepository;
        $this->roleRepository = $roleRepository;
        $this->roleManagement = $roleManagement;
        $this->companyUser = $companyUser;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->ruleConditionPool = $ruleConditionPool;
        $this->customerRepository = $customerRepository;
        $this->authorization = $authorization;
        $this->url = $url;
    }

    /**
     * @inheritDoc
     */
    public function getSearchCriteria()
    {
        // Restrict data provider to only show the current companies rules
        $this->searchCriteriaBuilder->addFilter(
            $this->filterBuilder->setField('company_id')
                ->setValue($this->companyUser->getCurrentCompanyId())
                ->create()
        );

        return parent::getSearchCriteria();
    }

    /**
     * Retrieve data for the grid
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getData()
    {
        $sortOrders = $this->getSearchCriteria()->getSortOrders();
        $sortOrder = current($sortOrders);

        // make sure sort order field is a valid db column for the parent method to sort by
        if ($sortOrder->getField() === 'type') {
            $sortOrder->setField(RuleInterface::KEY_CONDITIONS_SERIALIZED);
        }

        $data = parent::getData();
        foreach ($data['items'] as &$item) {
            try {
                $rule = $this->ruleRepository->get($item['rule_id']);
                $roleNames = $this->getRoleNames($rule->getApproverRoleIds());
                if ($rule->isAdminApprovalRequired()) {
                    array_unshift($roleNames, $this->roleManagement->getAdminRole()->getRoleName());
                }
                if ($rule->isManagerApprovalRequired()) {
                    array_unshift($roleNames, $this->roleManagement->getManagerRole()->getRoleName());
                }
                $item['approver'] = implode(', ', $roleNames);
                if ($rule->isAppliesToAll()) {
                    $item['applies_to'] = __('All');
                } else {
                    $item['applies_to'] = implode(', ', $this->getRoleNames($rule->getAppliesToRoleIds()));
                }
            } catch (\Exception $e) {
                $item['approver'] = '';
                $item['applies_to'] = '';
            }

            $item['type'] = __($this->getRuleType($item['conditions_serialized']))->render();
        }

        $data['isCreateRuleAllowed'] = $this->isCreateRuleAllowed();
        $data['createRuleUrl'] = $this->getCreateRuleUrl();
        return $data;
    }

    /**
     * Retrieve the order total amount from the serialized conditions
     *
     * @param string $conditionsSerialized
     * @return string|null
     */
    private function getRuleType(string $conditionsSerialized) : ?string
    {
        $result = null;
        /* @var ConditionInterface $condition */
        $condition = $this->serializer->unserialize($conditionsSerialized);
        if (isset($condition['conditions'][0]['attribute'])) {
            $attribute = $condition['conditions'][0]['attribute'];
            foreach ($this->ruleConditionPool->getConditions() as $rule) {
                if (in_array($attribute, $rule)) {
                    $result = $rule['label'];
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve company role names by ID
     *
     * @param array $ids
     * @return array
     */
    private function getRoleNames(array $ids)
    {
        if (!$this->companyRoles) {
            try {
                $roles = $this->roleRepository->getList(
                    $this->searchCriteriaBuilder
                        ->addSortOrder('role_id', 'desc')
                        ->addFilter(
                            $this->filterBuilder
                                ->setField('company_id')
                                ->setValue($this->companyUser->getCurrentCompanyId())
                                ->create()
                        )->create()
                );

                /* @var Role $role */
                foreach ($roles->getItems() as $role) {
                    $this->companyRoles[$role->getId()] = $role->getRoleName();
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->companyRoles = [];
            }
        }

        $roleNames = [];
        foreach ($ids as $id) {
            if (isset($this->companyRoles[$id])) {
                $roleNames[] = $this->companyRoles[$id];
            }
        }

        return $roleNames;
    }

    /**
     * Checks if is allowed to edit approval rules.
     *
     * @return bool
     */
    private function isCreateRuleAllowed()
    {
        return $this->authorization->isAllowed(self::COMPANY_RESOURCE);
    }

    /**
     * Get href to create rule.
     *
     * @return string
     */
    private function getCreateRuleUrl()
    {
        return $this->url->getUrl('purchaseorderrule/create');
    }
}
