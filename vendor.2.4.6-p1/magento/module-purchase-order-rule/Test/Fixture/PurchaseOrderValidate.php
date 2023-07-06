<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrderRule\Model\Validator;
use Magento\TestFramework\Fixture\DataFixtureInterface;

/**
 * Validate purchase order
 */
class PurchaseOrderValidate implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'purchase_order_id' => null
    ];

    /**
     * @var Validator
     */
    private Validator $purchaseOrderValidator;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private PurchaseOrderRepositoryInterface $purchaseOrderRepository;

    /**
     * @param Validator $purchaseOrderValidator
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     */
    public function __construct(
        Validator $purchaseOrderValidator,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {
        $this->purchaseOrderValidator = $purchaseOrderValidator;
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
        $this->purchaseOrderValidator->validate($this->purchaseOrderRepository->getById($data['purchase_order_id']));
        return null;
    }
}
