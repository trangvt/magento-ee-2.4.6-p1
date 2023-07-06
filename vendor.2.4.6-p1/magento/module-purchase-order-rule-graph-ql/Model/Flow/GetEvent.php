<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Flow;

use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;

class GetEvent
{
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * @var RoleRepositoryInterface
     */
    private RoleRepositoryInterface $roleRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var RoleManagementInterface
     */
    private RoleManagementInterface $roleManagement;

    /**
     * @param RoleRepositoryInterface $roleRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param RoleManagementInterface $roleManagement
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        RoleRepositoryInterface $roleRepository,
        CustomerRepositoryInterface $customerRepository,
        RoleManagementInterface $roleManagement,
        TimezoneInterface $timezone
    ) {
        $this->roleRepository = $roleRepository;
        $this->customerRepository = $customerRepository;
        $this->roleManagement = $roleManagement;
        $this->timezone = $timezone;
    }

    /**
     * Get event data
     *
     * @param AppliedRuleApproverInterface $approver
     * @return array
     */
    public function execute(AppliedRuleApproverInterface $approver): array
    {
        return [
            'name' => $this->getCustomerName($approver->getCustomerId()),
            'role' => $this->getRoleName($approver),
            'status' => $this->getStatus($approver),
            'message' => $this->getMessage($approver),
            'updated_at' => $approver->getUpdatedAt()
        ];
    }

    /**
     * Get status as string
     *
     * @param AppliedRuleApproverInterface $approver
     * @return string|null
     */
    private function getStatus(AppliedRuleApproverInterface $approver): ?string
    {
        switch ($approver->getStatus()) {
            case AppliedRuleApproverInterface::STATUS_PENDING:
                return 'PENDING';
            case AppliedRuleApproverInterface::STATUS_REJECTED:
                return 'REJECTED';
            case AppliedRuleApproverInterface::STATUS_APPROVED:
                return 'APPROVED';
            default:
                return null;
        }
    }

    /**
     * Get event message
     *
     * @param AppliedRuleApproverInterface $approver
     * @return Phrase|null
     */
    private function getMessage(AppliedRuleApproverInterface $approver): ?Phrase
    {
        switch ($approver->getStatus()) {
            case AppliedRuleApproverInterface::STATUS_PENDING:
                return $this->getPendingMessage($approver);
            case AppliedRuleApproverInterface::STATUS_REJECTED:
                return $this->getRejectedMessage($approver);
            case AppliedRuleApproverInterface::STATUS_APPROVED:
                return $this->getApprovedMessage($approver);
            default:
                return null;
        }
    }

    /**
     * Retrieve the message for a pending approver
     *
     * @param AppliedRuleApproverInterface $approver
     * @return Phrase
     */
    private function getPendingMessage(AppliedRuleApproverInterface $approver): Phrase
    {
        return __('Pending Approval from %1', $this->getRoleName($approver));
    }

    /**
     * Retrieve the message for a rejected approver
     *
     * @param AppliedRuleApproverInterface $approver
     * @return Phrase
     */
    private function getRejectedMessage(AppliedRuleApproverInterface $approver): Phrase
    {
        return __(
            '%1 rejected this purchase order on %2',
            $this->getCustomerName($approver->getCustomerId()),
            $this->getFormattedActionDate($approver->getUpdatedAt())
        );
    }

    /**
     * Retrieve the message for an approved approver
     *
     * @param AppliedRuleApproverInterface $approver
     * @return Phrase
     */
    private function getApprovedMessage(AppliedRuleApproverInterface $approver): Phrase
    {
        return __(
            '%1 approved this purchase order on %2',
            $this->getCustomerName($approver->getCustomerId()),
            $this->getFormattedActionDate($approver->getUpdatedAt())
        );
    }

    /**
     * Retrieve the associated role name
     *
     * @param AppliedRuleApproverInterface $approver
     * @return string
     */
    private function getRoleName(AppliedRuleApproverInterface $approver): string
    {
        $unknownRole = (string) __('Unknown Role');
        try {
            $approverType = $approver->getApproverType();
            if ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ROLE) {
                return $this->roleRepository->get($approver->getRoleId())->getRoleName() ?? $unknownRole;
            }
            if ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN) {
                return (string )$this->roleManagement->getAdminRole()->getRoleName();
            }
            if ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER) {
                return (string) $this->roleManagement->getManagerRole()->getRoleName();
            }
            return $unknownRole;
        } catch (NoSuchEntityException $e) {
            return $unknownRole;
        }
    }

    /**
     * Retrieve the customers name
     *
     * @param int $customerId
     * @return string
     */
    private function getCustomerName(int $customerId): string
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getFirstname() . ' ' . $customer->getLastname();
        } catch (LocalizedException $e) {
            return (string) __('Unknown Customer');
        }
    }

    /**
     * Retrieve the formatted action date
     *
     * @param string $date
     * @return Phrase
     * @throws \Exception
     */
    private function getFormattedActionDate(string $date): Phrase
    {
        $dateTime = new \DateTime($date);
        return __(
            '%1 at %2',
            $this->timezone->formatDateTime($dateTime, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE),
            $this->timezone->formatDateTime($dateTime, \IntlDateFormatter::NONE)
        );
    }
}
