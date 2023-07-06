<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Validator\Exception\PurchaseOrderValidationException;
use Magento\PurchaseOrderGraphQl\Model\GetErrorType;
use Magento\PurchaseOrderGraphQl\Model\GetPurchaseOrderData;
use Magento\PurchaseOrderGraphQl\Model\ValidateRequest;
use Magento\PurchaseOrderGraphQl\Model\IsAllowedAction;
use Magento\PurchaseOrderRule\Model\Validator;

/**
 * Validation mutation of purchase order
 */
class Validate implements ResolverInterface
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private PurchaseOrderRepositoryInterface $purchaseOrderRepository;

    /**
     * @var GetPurchaseOrderData
     */
    private GetPurchaseOrderData $getPurchaseOrderData;

    /**
     * @var GetErrorType
     */
    private GetErrorType $getErrorType;

    /**
     * @var ValidateRequest
     */
    private ValidateRequest $validateRequest;

    /**
     * @var Validator
     */
    private Validator $validator;

    /**
     * @var IsAllowedAction
     */
    private IsAllowedAction $isAllowedAction;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param GetPurchaseOrderData $getPurchaseOrderData
     * @param GetErrorType $getErrorType
     * @param ValidateRequest $validateRequest
     * @param Validator $validator
     * @param IsAllowedAction $isAllowedAction
     * @param Uid $uid
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        GetPurchaseOrderData $getPurchaseOrderData,
        GetErrorType $getErrorType,
        ValidateRequest $validateRequest,
        Validator $validator,
        IsAllowedAction $isAllowedAction,
        Uid $uid
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->getPurchaseOrderData = $getPurchaseOrderData;
        $this->getErrorType = $getErrorType;
        $this->validateRequest = $validateRequest;
        $this->validator = $validator;
        $this->isAllowedAction = $isAllowedAction;
        $this->uid = $uid;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        $this->validateRequest->execute($context, $args, 'purchase_order_uids');
        $ids = $args['input']['purchase_order_uids'];

        $errors = [];
        $purchaseOrders = [];
        foreach ($ids as $id) {
            try {
                $purchaseOrder = $this->purchaseOrderRepository->getById($this->uid->decode($id));
                if (!$this->isAllowedAction->execute('validate', $purchaseOrder)) {
                    $message = __(
                        'Purchase order %number cannot be validated.',
                        ['number' => (int)$purchaseOrder->getIncrementId()]
                    );
                    $errors[] = [
                        'message' => $message,
                        'type' => $this->getErrorType->execute(new PurchaseOrderValidationException($message))
                    ];
                    continue;
                }
                $this->validator->validate($purchaseOrder);
                $purchaseOrders[] = $this->getPurchaseOrderData->execute($purchaseOrder);
            } catch (Exception $exception) {
                $errors[] = [
                    'message' => $exception->getMessage(),
                    'type' => $this->getErrorType->execute($exception)
                ];
            }
        }

        return [
            'purchase_orders' => $purchaseOrders,
            'errors' => $errors
        ];
    }
}
