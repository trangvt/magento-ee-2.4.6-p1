<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Model\AddItemsToCart;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\PurchaseOrderRepository;
use Magento\Quote\Model\Cart\Data\Error;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * Add purchase order items to cart
 */
class AddToCart implements ResolverInterface
{
    private const PARAM_PURCHASE_ORDER_UID = 'purchase_order_uid';
    private const PARAM_CART_ID = 'cart_id';
    private const PARAM_REPLACE_CART = 'replace_existing_cart_items';

    /**
     * @var PurchaseOrderRepository
     */
    private PurchaseOrderRepository $purchaseOrderRepository;

    /**
     * @var Authorization
     */
    private Authorization $authorization;

    /**
     * @var GetCartForUser
     */
    private GetCartForUser $getCartForUser;

    /**
     * @var AddItemsToCart
     */
    private AddItemsToCart $addItemsToCart;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @param AddItemsToCart $addItemsToCart
     * @param Authorization $authorization
     * @param GetCartForUser $getCartForUser
     * @param PurchaseOrderRepository $purchaseOrderRepository
     * @param Uid $uid
     */
    public function __construct(
        AddItemsToCart $addItemsToCart,
        Authorization $authorization,
        GetCartForUser $getCartForUser,
        PurchaseOrderRepository $purchaseOrderRepository,
        Uid $uid
    ) {
        $this->addItemsToCart = $addItemsToCart;
        $this->authorization = $authorization;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->getCartForUser = $getCartForUser;
        $this->uid = $uid;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if (empty($args['input'][self::PARAM_PURCHASE_ORDER_UID])) {
            throw new GraphQlInputException(
                __('Required parameter "%param" is missing', ['param' => self::PARAM_PURCHASE_ORDER_UID])
            );
        }

        if (empty($args['input'][self::PARAM_CART_ID])) {
            throw new GraphQlInputException(
                __('Required parameter "%param" is missing', ['param' => self::PARAM_CART_ID])
            );
        }

        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById(
                $this->uid->decode($args['input'][self::PARAM_PURCHASE_ORDER_UID])
            );
        } catch (NoSuchEntityException $e) {
            throw new GraphQlInputException(
                __(
                    'No such entity with %fieldName = %fieldValue',
                    [
                        'fieldName' => 'entity_id',
                        'fieldValue' => $args['input'][self::PARAM_PURCHASE_ORDER_UID]
                    ]
                )
            );
        }

        if (!$this->authorization->isAllowed('view', $purchaseOrder)) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer is not authorized to retrieve the purchase order %number.',
                    [
                        'number' => $purchaseOrder->getIncrementId()
                    ]
                )
            );
        }

        $maskedCartId = $args['input'][self::PARAM_CART_ID];
        $replaceCart = $args['input'][self::PARAM_REPLACE_CART] ?? false;

        $quote = $this->getCartForUser->execute(
            $maskedCartId,
            $context->getUserId(),
            (int)$context->getExtensionAttributes()->getStore()->getId()
        );

        $errors = $this->addItemsToCart->execute($quote, $purchaseOrder, $replaceCart);

        return [
            'cart' => [
                'model' => $quote,
            ],
            'user_errors' => array_map(
                function (Error $error) {
                    return [
                        'code' => $error->getCode(),
                        'message' => $error->getMessage(),
                        'path' => [$error->getCartItemPosition()]
                    ];
                },
                $errors
            )
        ];
    }
}
