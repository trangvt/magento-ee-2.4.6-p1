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
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Magento\RequisitionListGraphQl\Model\RequisitionList\DeleteItems as DeleteItemsModel;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Get;

/**
 * Delete Requisition List Items resolver
 */
class DeleteItems implements ResolverInterface
{
    /**
     * @var DeleteItemsModel
     */
    private $deleteRequisitionListItemsForUser;

    /**
     * @var Get
     */
    private $getRequisitionListForUser;

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
     * @param DeleteItemsModel $deleteRequisitionListItemsForUser
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param IdEncoder $idEncoder
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Get $getRequisitionListForUser,
        DeleteItemsModel $deleteRequisitionListItemsForUser,
        ExtensibleDataObjectConverter $dataObjectConverter,
        IdEncoder $idEncoder,
        ModuleConfig $moduleConfig
    ) {
        $this->getRequisitionListForUser = $getRequisitionListForUser;
        $this->deleteRequisitionListItemsForUser = $deleteRequisitionListItemsForUser;
        $this->dataObjectConverter = $dataObjectConverter;
        $this->idEncoder = $idEncoder;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Remove requisition list items
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|Value|mixed
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->moduleConfig->isActive()) {
            throw new GraphQlInputException(__('Requisition List feature is not available.'));
        }

        $customerId = (int)$context->getUserId();

        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current user cannot perform operations on requisition list')
            );
        }

        if (empty($args['requisitionListUid'])) {
            throw new GraphQlInputException(__('Specify the "requisitionListUid" value.'));
        }

        if (empty($args['requisitionListItemUids'])) {
            throw new GraphQlInputException(__('Specify the "requisitionListItemUids" value.'));
        }

        $requisitionListId = (int)$this->idEncoder->decode($args['requisitionListUid']);
        $requisitionList = $this->getRequisitionListForUser->execute($customerId, $requisitionListId);
        $requisitionListItemsId = array_map(
            function ($id) {
                return $this->idEncoder->decode($id);
            },
            $args['requisitionListItemUids']
        );
        $this->deleteRequisitionListItemsForUser->execute($requisitionListItemsId, $requisitionListId);

        $data = $this->dataObjectConverter->toFlatArray($requisitionList, [], RequisitionListInterface::class);
        $data['uid'] = $this->idEncoder->encode((string)$requisitionList->getId());
        $data['items_count'] = count($requisitionList->getItems());

        return [
            'requisition_list' => $data
        ];
    }
}
