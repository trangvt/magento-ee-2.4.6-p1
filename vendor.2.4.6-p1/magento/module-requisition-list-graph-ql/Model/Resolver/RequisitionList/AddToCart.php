<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Get;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\AddItemsToCart;

/**
 * Resolver to add Requisition List Items to cart
 */
class AddToCart implements ResolverInterface
{
    /**
     * @var Get
     */
    private $getRequisitionListForUser;

    /**
     * @var AddItemsToCart
     */
    private $addItemsToCart;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @var \Magento\RequisitionList\Model\Config
     */
    private $moduleConfig;

    /**
     * @param Get $getRequisitionListForUser
     * @param AddItemsToCart $addItemsToCart
     * @param IdEncoder $idEncoder
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Get $getRequisitionListForUser,
        AddItemsToCart $addItemsToCart,
        IdEncoder $idEncoder,
        ModuleConfig $moduleConfig
    ) {
        $this->getRequisitionListForUser = $getRequisitionListForUser;
        $this->addItemsToCart = $addItemsToCart;
        $this->idEncoder = $idEncoder;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @inheritDoc
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
        $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsite()->getId();

        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current user cannot perform operations on requisition list')
            );
        }

        if (empty($args['requisitionListUid'])) {
            throw new GraphQlInputException(__('"requisitionListUid" value should be specified'));
        }

        if (isset($args['requisitionListItemUids']) && empty($args['requisitionListItemUids'])) {
            throw new GraphQlInputException(__('"requisitionListItemUids" value should be specified'));
        }

        /**
         * To check whether the requisition list is available for the user
         */
        $requisitionListId = (int)$this->idEncoder->decode($args['requisitionListUid']);
        $this->getRequisitionListForUser->execute($customerId, $requisitionListId);

        /**
         * Adds the requisition list items to cart
         */
        $itemIds = [];
        if (isset($args['requisitionListItemUids'])) {
            $itemIds = array_map(
                function ($id) {
                    return $this->idEncoder->decode($id);
                },
                $args['requisitionListItemUids']
            );
        }
        $cart = $this->addItemsToCart->execute($customerId, $itemIds, $requisitionListId, $websiteId);
        $errors = $this->addItemsToCart->getErrors();
        $status = count($errors) ? false : true;
        return [
            'cart' => [
                'model' => $cart
            ],
            'add_requisition_list_items_to_cart_user_errors' => $errors,
            'status' => $status
        ];
    }
}
