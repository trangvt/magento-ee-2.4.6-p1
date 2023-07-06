<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Magento\RequisitionListGraphQl\Model\Cart\ClearCartItems;

/**
 * Clear Items from Customer Cart.
 */
class ClearCustomerCart implements ResolverInterface
{
    /**
     * @var ClearCartItems
     */
    private $clearCartItems;

    /**
     * @var \Magento\RequisitionList\Model\Config
     */
    private $moduleConfig;

    /**
     * @param ClearCartItems $clearCartItems
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        ClearCartItems $clearCartItems,
        ModuleConfig $moduleConfig
    ) {
        $this->clearCartItems = $clearCartItems;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @inheritDoc
     *
     * @throws GraphQlInputException
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     * @throws GraphQlAuthorizationException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (!$this->moduleConfig->isActive()) {
            throw new GraphQlInputException(__('Requisition List feature is not available.'));
        }

        $customerId = $context->getUserId();

        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current user cannot perform operations on requisition list')
            );
        }

        if (empty($args['cartUid'])) {
            throw new GraphQlInputException(__('Required parameter "cartUid" is missing'));
        }

        $cart = $this->clearCartItems->clearCartItems($args['cartUid']);

        return [
            'cart' => [
                'model' => $cart,
            ],
            'status' => !$cart->hasError()
        ];
    }
}
