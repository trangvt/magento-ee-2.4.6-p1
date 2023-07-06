<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Retrieve the list of roles that
 */
class Roles
{
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var RoleManagementInterface
     */
    private RoleManagementInterface $roleManagement;

    /**
     * @var RoleRepositoryInterface
     */
    private RoleRepositoryInterface $roleRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RoleManagementInterface $roleManagement
     * @param RoleRepositoryInterface $roleRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RoleManagementInterface $roleManagement,
        RoleRepositoryInterface $roleRepository,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->roleManagement = $roleManagement;
        $this->roleRepository = $roleRepository;
        $this->logger = $logger;
    }

    /**
     * Retrieve all company roles
     *
     * @param int $companyId
     * @return array
     */
    public function getRoles(int $companyId): array
    {
        try {
            return $this->roleRepository->getList(
                $this->searchCriteriaBuilder
                    ->addFilter(RoleInterface::COMPANY_ID, $companyId)
                    ->create()
            )->getItems();
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception);
            return [];
        }
    }

    /**
     * Get roles that can be selected as rule approvers
     *
     * @param int $companyId
     * @return array
     */
    public function getApproverRoles(int $companyId): array
    {
        return [
            $this->roleManagement->getAdminRole(),
            $this->roleManagement->getManagerRole(),
            ...$this->getRoles($companyId)
        ];
    }
}
