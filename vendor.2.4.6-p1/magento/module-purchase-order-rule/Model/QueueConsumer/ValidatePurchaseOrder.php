<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\QueueConsumer;

use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrderRule\Model\Validator;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue consumer handles 'purchaseorder.order.validation' message
 */
class ValidatePurchaseOrder
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var Validator
     */
    private $purchaseOrderValidator;

    /**
     * @param LoggerInterface $logger
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param Validator $purchaseOrderValidator
     */
    public function __construct(
        LoggerInterface $logger,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        Validator $purchaseOrderValidator
    ) {
        $this->logger = $logger;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->purchaseOrderValidator = $purchaseOrderValidator;
    }

    /**
     * Process purchase order
     *
     * @param string $purchaseOrderId
     */
    public function process(string $purchaseOrderId)
    {
        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
            $this->purchaseOrderValidator->validate($purchaseOrder);
        } catch (LocalizedException $exception) {
            $this->logger->error('ValidatePurchaseOrder queue consumer error:' . $exception->getLogMessage());
        }
    }
}
