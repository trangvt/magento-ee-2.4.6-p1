<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Block\PurchaseOrder;

use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\RoleManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Block class for the approval flow section of the purchase order details page.
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.2.0
 */
class ApprovalFlow extends AbstractPurchaseOrder
{
    /**
     * @var array
     */
    private $appliedRules = null;

    /**
     * @var AppliedRuleRepositoryInterface
     */
    private $appliedRuleRepository;

    /**
     * @var AppliedRuleApproverRepositoryInterface
     */
    private $appliedRuleApproverRepository;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var RoleManagement
     */
    private $roleManagement;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param RoleRepositoryInterface $roleRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param RoleManagement $roleManagement
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        RoleRepositoryInterface $roleRepository,
        CustomerRepositoryInterface $customerRepository,
        RoleManagement $roleManagement,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->roleRepository = $roleRepository;
        $this->customerRepository = $customerRepository;
        $this->roleManagement = $roleManagement;
    }

    /**
     * Retrieve all applied rules with information regarding the individual roles who are required to approve
     *
     * @return array
     * @since 100.2.0
     */
    public function getAppliedRules()
    {
        try {
            if (!$this->appliedRules) {
                $this->appliedRules = [];
                $appliedRules = $this->appliedRuleRepository->getListByPurchaseOrderId(
                    (int) $this->getPurchaseOrder()->getEntityId()
                );

                if ($appliedRules->getTotalCount() > 0) {
                    $this->appliedRules = $appliedRules->getItems();
                }
            }

            return $this->appliedRules;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retrieve the approvers for the applied rule
     *
     * @param AppliedRuleInterface $appliedRule
     * @return array|AppliedRuleApproverInterface[]
     * @since 100.2.0
     */
    public function getAppliedRuleApprovers(AppliedRuleInterface $appliedRule)
    {
        try {
            $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int) $appliedRule->getId());
            return $approvers->getItems();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retrieve the message for a pending approver
     *
     * @param AppliedRuleApproverInterface $approver
     * @return Phrase
     * @since 100.2.0
     */
    public function getPendingMessage(AppliedRuleApproverInterface $approver)
    {
        return __('Pending Approval from %1', $this->getRoleName($approver));
    }

    /**
     * Retrieve the message for a rejected approver
     *
     * @param AppliedRuleApproverInterface $approver
     * @return Phrase
     * @since 100.2.0
     */
    public function getRejectedMessage(AppliedRuleApproverInterface $approver)
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
     * @since 100.2.0
     */
    public function getApprovedMessage(AppliedRuleApproverInterface $approver)
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
     * @return string|null
     */
    private function getRoleName(AppliedRuleApproverInterface $approver)
    {
        try {
            $approverType = $approver->getApproverType();
            if ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ROLE) {
                $role = $this->roleRepository->get($approver->getRoleId());
                return $role->getRoleName();
            } elseif ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN) {
                return $this->roleManagement->getAdminRole()->getRoleName();
            } elseif ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER) {
                return $this->roleManagement->getManagerRole()->getRoleName();
            } else {
                return __('Unknown Role');
            }
        } catch (NoSuchEntityException $e) {
            return __('Unknown Role');
        }
    }

    /**
     * Retrieve the customers name
     *
     * @param int $customerId
     * @return Phrase|string
     */
    private function getCustomerName(int $customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getFirstname() . ' ' . $customer->getLastname();
        } catch (NoSuchEntityException $e) {
            return __('Unknown Customer');
        } catch (LocalizedException $e) {
            return __('Unknown Customer');
        }
    }

    /**
     * Retrieve the formatted action date
     *
     * @param string $date
     * @return Phrase
     */
    private function getFormattedActionDate(string $date)
    {
        return __(
            '%1 at %2',
            $this->formatDate($date, \IntlDateFormatter::LONG),
            $this->formatTime($date)
        );
    }

    /**
     * Only render template if at least one rule was applied
     *
     * @return string|null
     * @since 100.2.0
     */
    public function getTemplate()
    {
        if (count($this->getAppliedRules()) > 0) {
            return parent::getTemplate();
        }

        return null;
    }
}
