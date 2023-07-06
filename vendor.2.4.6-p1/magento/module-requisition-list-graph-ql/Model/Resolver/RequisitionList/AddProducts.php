<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList;

use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Cart\Data\CartItemFactory;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Get;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\AddItemsToRequisitionList;

/**
 * Resolver for add products to requisition list
 */
class AddProducts implements ResolverInterface
{
    /**
     * @var Get
     */
    private $getRequisitionListForUser;

    /**
     * @var AddItemsToRequisitionList
     */
    private $addItemsToRequisitionList;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

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
     * @param AddItemsToRequisitionList $addItemsToRequisitionList
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param IdEncoder $idEncoder
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Get $getRequisitionListForUser,
        AddItemsToRequisitionList $addItemsToRequisitionList,
        ExtensibleDataObjectConverter $dataObjectConverter,
        IdEncoder $idEncoder,
        ModuleConfig $moduleConfig
    ) {
        $this->getRequisitionListForUser = $getRequisitionListForUser;
        $this->addItemsToRequisitionList = $addItemsToRequisitionList;
        $this->dataObjectConverter = $dataObjectConverter;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current user cannot perform operations on requisition list')
            );
        }

        if (empty($args['requisitionListItems']) || !is_array($args['requisitionListItems'])
        ) {
            throw new GraphQlInputException(__('Required parameter "requisitionListItems" is missing'));
        }

        if (empty($args['requisitionListUid'])) {
            throw new GraphQlInputException(__('Required parameter "requisitionListUid" is missing'));
        }

        $requisitionListId = (int)$this->idEncoder->decode($args['requisitionListUid']);
        $itemsData = $args['requisitionListItems'];
        $requisitionListItems = [];
        foreach ($itemsData as $itemData) {
            $requisitionListItems[] = (new CartItemFactory())->create($itemData);
        }

        $requisitionList =  $this->getRequisitionListForUser->execute($customerId, $requisitionListId);
        $this->addItemsToRequisitionList->execute($requisitionList, $requisitionListItems);

        $data = $this->dataObjectConverter->toFlatArray($requisitionList, [], RequisitionListInterface::class);
        $data['uid'] = $this->idEncoder->encode((string)$requisitionList->getId());
        $data['items_count'] = count($requisitionList->getItems());

        return [
            'requisition_list' => $data
        ];
    }
}
