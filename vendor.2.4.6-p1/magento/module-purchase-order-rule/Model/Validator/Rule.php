<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Validator;

use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Validator\ValidatorInterface;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;

/**
 * Determine whether we should create a queue entry to validate the purchase order after checkout
 */
class Rule implements ValidatorInterface
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var PurchaseOrderManagementInterface
     */
    private $purchaseOrderManagement;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param AuthorizationInterface $authorization
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param CompanyContext $companyContext
     * @param RuleRepositoryInterface $ruleRepository
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param PublisherInterface $publisher
     */
    public function __construct(
        AuthorizationInterface $authorization,
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        CompanyContext $companyContext,
        RuleRepositoryInterface $ruleRepository,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        PublisherInterface $publisher
    ) {
        $this->authorization = $authorization;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->companyContext = $companyContext;
        $this->ruleRepository = $ruleRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->publisher = $publisher;
    }

    /**
     * @inheritDoc
     */
    public function validate(PurchaseOrderInterface $purchaseOrder): void
    {
        // Only validate the order against rules if it wasn't approved by another validator in checkout
        if ($purchaseOrder->getStatus() !== PurchaseOrderInterface::STATUS_APPROVED) {
            $rules = $this->ruleRepository->getByCompanyId((int) $purchaseOrder->getCompanyId());
            // Only trigger the rules engine if there is at least one rule in the company
            if ($rules->getTotalCount() > 0) {
                // Update the purchase order to pending while it's processing
                $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_PENDING);
                $this->purchaseOrderRepository->save($purchaseOrder);
                // Add a queue item to validate the order
                $this->publisher->publish('purchaseorder.validation', $purchaseOrder->getEntityId());
            } else {
                $this->purchaseOrderManagement->approvePurchaseOrder(
                    $purchaseOrder,
                    $this->companyContext->getCustomerId()
                );
            }
        }
    }
}
