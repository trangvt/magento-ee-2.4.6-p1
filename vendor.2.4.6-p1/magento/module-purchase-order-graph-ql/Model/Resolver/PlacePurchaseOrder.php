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
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Helper\Error\AggregateExceptionMessageFormatter;
use Magento\PurchaseOrder\Api\PurchaseOrderPaymentInformationManagementInterface;
use Magento\PurchaseOrder\Model\PurchaseOrderRepository;
use Magento\PurchaseOrderGraphQl\Model\GetPurchaseOrderData;
use Magento\QuoteGraphQl\Model\Cart\GetCartForCheckout;

/**
 * Place Purchase Order Resolver IsEnabled
 */
class PlacePurchaseOrder implements ResolverInterface
{
    private const PARAM_CART_ID = 'cart_id';

    /**
     * @var PurchaseOrderRepository
     */
    private PurchaseOrderRepository $purchaseOrderRepository;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var GetPurchaseOrderData
     */
    private GetPurchaseOrderData $getPurchaseOrderData;

    /**
     * @var PurchaseOrderPaymentInformationManagementInterface
     */
    private PurchaseOrderPaymentInformationManagementInterface $place;

    /**
     * @var GetCartForCheckout
     */
    private GetCartForCheckout $getCartForCheckout;

    /**
     * @var AggregateExceptionMessageFormatter
     */
    private AggregateExceptionMessageFormatter $errorMessageFormatter;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param AggregateExceptionMessageFormatter $errorMessageFormatter
     * @param GetCartForCheckout $getCartForCheckout
     * @param GetPurchaseOrderData $getPurchaseOrderData
     * @param PurchaseOrderRepository $purchaseOrderRepository
     * @param PurchaseOrderPaymentInformationManagementInterface $place
     * @param ResolverAccess $resolverAccess
     * @param LoggerInterface $logger
     * @param array $allowedResources
     */
    public function __construct(
        AggregateExceptionMessageFormatter $errorMessageFormatter,
        GetCartForCheckout $getCartForCheckout,
        GetPurchaseOrderData $getPurchaseOrderData,
        PurchaseOrderRepository $purchaseOrderRepository,
        PurchaseOrderPaymentInformationManagementInterface $place,
        ResolverAccess $resolverAccess,
        LoggerInterface $logger,
        array $allowedResources = []
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->getPurchaseOrderData = $getPurchaseOrderData;
        $this->place = $place;
        $this->getCartForCheckout = $getCartForCheckout;
        $this->errorMessageFormatter = $errorMessageFormatter;
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

        if (empty($args['input'][self::PARAM_CART_ID])) {
            throw new GraphQlInputException(
                __('Required parameter "%param" is missing', ['param' => self::PARAM_CART_ID])
            );
        }
        $maskedCartId = $args['input'][self::PARAM_CART_ID];
        $userId = (int)$context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        try {
            $cart = $this->getCartForCheckout->execute($maskedCartId, $userId, $storeId);
            $purchaseOrderId = $this->place->savePaymentInformationAndPlacePurchaseOrder(
                $cart->getId(),
                $cart->getPayment()
            );
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception);
            throw $this->errorMessageFormatter->getFormatted(
                $exception,
                __('Unable to place purchase order: A server error stopped your purchase order from being placed. ' .
                    'Please try to place your purchase order again'),
                'Unable to place purchase order',
                $field,
                $context,
                $info
            );
        }

        $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);

        return ['purchase_order' => $this->getPurchaseOrderData->execute($purchaseOrder),];
    }
}
