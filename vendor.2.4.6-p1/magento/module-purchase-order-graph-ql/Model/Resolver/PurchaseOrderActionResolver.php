<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Validator\Exception\PurchaseOrderValidationException;
use Magento\PurchaseOrder\Model\PurchaseOrderManagement;
use Magento\PurchaseOrderGraphQl\Model\GetPurchaseOrderData;
use Magento\PurchaseOrderGraphQl\Model\GetErrorType;
use Magento\PurchaseOrderGraphQl\Model\IsAllowedAction;
use Magento\PurchaseOrderGraphQl\Model\ValidateRequest;

/**
 * generic Purchase Order Resolver for PO actions mutations
 */
class PurchaseOrderActionResolver implements ResolverInterface
{
    private const ERROR_MESSAGES = [
        'OPERATION_NOT_APPLICABLE' => "Action '%action' is not allowed for purchase order %number.",
        'NOT_FOUND' => "Action '%action' - purchase order with requested ID=%number not found",
        'COULD_NOT_SAVE' => "Action '%action'  - purchase order with requested ID=%number could not be saved",
        'NOT_VALID_DATA' => "Action '%action' - purchase order with requested ID=%number contains invalid data",
        'UNDEFINED' => "An undefined error occurred when trying to '%action' purchase order with requested ID=%number"
    ];

    /**
     * @var PurchaseOrderManagement
     */
    private PurchaseOrderManagement $purchaseOrderManagement;

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
     * @var IsAllowedAction
     */
    private IsAllowedAction $isAllowedAction;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @param PurchaseOrderManagement $purchaseOrderManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param GetPurchaseOrderData $getPurchaseOrderData
     * @param GetErrorType $getErrorType
     * @param ValidateRequest $validateRequest
     * @param IsAllowedAction $isAllowedAction
     * @param Uid $uid
     */
    public function __construct(
        PurchaseOrderManagement $purchaseOrderManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        GetPurchaseOrderData $getPurchaseOrderData,
        GetErrorType $getErrorType,
        ValidateRequest $validateRequest,
        IsAllowedAction $isAllowedAction,
        Uid $uid
    ) {
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->getPurchaseOrderData = $getPurchaseOrderData;
        $this->getErrorType = $getErrorType;
        $this->validateRequest = $validateRequest;
        $this->isAllowedAction = $isAllowedAction;
        $this->uid = $uid;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $this->validateRequest->execute(
            $context,
            $args,
            'purchase_order_uids'
        );
        $ids = $args['input']['purchase_order_uids'];
        $action = $args['action'];
        $customerId = $context->getUserId();
        $errors = [];
        $purchaseOrders = [];
        foreach ($ids as $id) {
            try {
                $purchaseOrder = $this->purchaseOrderRepository->getById($this->uid->decode($id));
                if (!$this->isAllowedAction->execute($action, $purchaseOrder)) {
                    $message = __(
                        $this->getErrorMessageByType('OPERATION_NOT_APPLICABLE'),
                        [
                            'action' => $action,
                            'number' => $purchaseOrder->getIncrementId()
                        ]
                    );
                    $errors[] = [
                        'message' => $message,
                        'type' => $this->getErrorType->execute(new PurchaseOrderValidationException($message))
                    ];
                    continue;
                }
                $this->purchaseOrderManagement->{$action . "PurchaseOrder"}($purchaseOrder, $customerId);
                $purchaseOrders[] = $this->getPurchaseOrderData->execute($purchaseOrder);
            } catch (Exception $exception) {
                $errors[] = [
                    'message' => __(
                        $this->getErrorMessageByType($this->getErrorType->execute($exception)),
                        [
                            'action' => $action,
                            'number' => $id
                        ]
                    ),
                    'type' => $this->getErrorType->execute($exception)
                ];
            }
        }

        return [
            'purchase_orders' => $purchaseOrders,
            'errors' => $errors
        ];
    }

    /**
     * This method allows to use constants in string literals
     * (fixes "Constants are not allowed as the first argument of translation function, use string literal instead")
     *
     * @param string $errorType
     * @return string
     */
    private function getErrorMessageByType(string $errorType): string
    {
        return PurchaseOrderActionResolver::ERROR_MESSAGES[$errorType];
    }
}
