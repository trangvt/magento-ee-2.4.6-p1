<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Helper\Error\AggregateExceptionMessageFormatter;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\PurchaseOrderRepository;
use Magento\SalesGraphQl\Model\Formatter\Order;

/**
 * Place Order Resolver IsEnabled
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrder implements ResolverInterface
{
    private const PARAM_PURCHASE_ORDER_UID = 'purchase_order_uid';

    /**
     * @var PurchaseOrderRepository
     */
    private PurchaseOrderRepository $purchaseOrderRepository;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var PurchaseOrderManagementInterface
     */
    private PurchaseOrderManagementInterface $purchaseOrderManagement;

    /**
     * @var AggregateExceptionMessageFormatter
     */
    private AggregateExceptionMessageFormatter $errorMessageFormatter;

    /**
     * @var Authorization
     */
    private Authorization $authorization;

    /**
     * @var Order
     */
    private Order $orderFormatter;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param AggregateExceptionMessageFormatter $errorMessageFormatter
     * @param Authorization $authorization
     * @param Order $orderFormatter
     * @param PurchaseOrderRepository $purchaseOrderRepository
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param ResolverAccess $resolverAccess
     * @param Uid $uid
     * @param LoggerInterface $logger
     * @param array $allowedResources
     */
    public function __construct(
        AggregateExceptionMessageFormatter $errorMessageFormatter,
        Authorization $authorization,
        Order $orderFormatter,
        PurchaseOrderRepository $purchaseOrderRepository,
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        ResolverAccess $resolverAccess,
        Uid $uid,
        LoggerInterface $logger,
        array $allowedResources = []
    ) {
        $this->authorization = $authorization;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->errorMessageFormatter = $errorMessageFormatter;
        $this->orderFormatter = $orderFormatter;
        $this->uid = $uid;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);

        if (empty($args['input'][self::PARAM_PURCHASE_ORDER_UID])) {
            throw new GraphQlInputException(
                __('Required parameter "%param" is missing', ['param' => self::PARAM_PURCHASE_ORDER_UID])
            );
        }

        $purchaseOrder = $this->purchaseOrderRepository->getById(
            $this->uid->decode($args['input'][self::PARAM_PURCHASE_ORDER_UID])
        );

        if (!$this->authorization->isAllowed('placeorder', $purchaseOrder)) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer is not authorized to place the purchase order %number.',
                    ['number' => $purchaseOrder->getIncrementId()]
                )
            );
        }

        try {
            $order = $this->purchaseOrderManagement->createSalesOrder($purchaseOrder, (int)$context->getUserId());
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception);
            throw $this->errorMessageFormatter->getFormatted(
                $exception,
                __('Unable to place order: A server error stopped your order from being placed. ' .
                    'Please try to place your order again'),
                'Unable to place order',
                $field,
                $context,
                $info
            );
        }

        return ['order' => $this->orderFormatter->format($order)];
    }
}
