<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;

/**
 * Place order for a purchase order
 */
class PurchaseOrderPlaceOrder implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'purchase_order_id' => null,
        'customer_id' => null
    ];

    /**
     * @var PurchaseOrderManagementInterface
     */
    private PurchaseOrderManagementInterface $purchaseOrderManagement;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private PurchaseOrderRepositoryInterface $purchaseOrderRepository;

    /**
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     */
    public function __construct(
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        if (empty($data['purchase_order_id'])) {
            throw new InvalidArgumentException(__('"purchase_order_id" is required'));
        }
        return $this->purchaseOrderManagement->createSalesOrder(
            $this->purchaseOrderRepository->getById($data['purchase_order_id']),
            $data['customer_id'] ?? self::DEFAULT_DATA['customer_id']
        );
    }
}
