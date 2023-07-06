<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver\PurchaseOrder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;
use Magento\PurchaseOrder\Model\PurchaseOrder\LogManagementInterface;
use Magento\PurchaseOrderGraphQl\Model\GetLogMessage;

/**
 * Purchase Order log resolver
 */
class Log implements ResolverInterface
{
    /**
     * @var LogManagementInterface
     */
    private LogManagementInterface $logManagement;

    /**
     * @var GetLogMessage
     */
    private GetLogMessage $getLogMessage;

    /**
     * @param LogManagementInterface $logManagement
     * @param GetLogMessage $getLogMessage
     */
    public function __construct(
        LogManagementInterface $logManagement,
        GetLogMessage $getLogMessage
    ) {
        $this->logManagement = $logManagement;
        $this->getLogMessage = $getLogMessage;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var PurchaseOrderInterface $purchaseOrder */
        $purchaseOrder = $value['model'];

        return array_values(
            array_map(
                function (PurchaseOrderLogInterface $event) {
                    return [
                        'uid' => $event->getId(),
                        'created_at' => $event->getCreatedAt(),
                        'activity' => $event->getActivityType(),
                        'message' => $this->getLogMessage->execute($event)
                    ];
                },
                $this->logManagement->getPurchaseOrderLogs($purchaseOrder->getEntityId())
            )
        );
    }
}
