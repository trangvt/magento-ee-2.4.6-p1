<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList;

use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Exception\LocalizedException;
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
use Magento\RequisitionListGraphQl\Model\RequisitionList\CopyItems as CopyItemsModel;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Get;

/**
 * Copy Requisition list items from one list to another
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 */
class CopyItems implements ResolverInterface
{

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
     * @var CopyItemsModel
     */
    private $copyItemsModel;

    /**
     * @var \Magento\RequisitionList\Model\Config
     */
    private $moduleConfig;

    /**
     * @param Get $getRequisitionListForUser
     * @param CopyItemsModel $copyItemsModel
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param IdEncoder $idEncoder
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Get $getRequisitionListForUser,
        CopyItemsModel $copyItemsModel,
        ExtensibleDataObjectConverter $dataObjectConverter,
        IdEncoder $idEncoder,
        ModuleConfig $moduleConfig
    ) {
        $this->dataObjectConverter = $dataObjectConverter;
        $this->idEncoder = $idEncoder;
        $this->getRequisitionListForUser = $getRequisitionListForUser;
        $this->copyItemsModel = $copyItemsModel;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Copy requisition list items from one list to another list
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

        if (empty($args['sourceRequisitionListUid'])) {
            throw new GraphQlInputException(__('Specify the "sourceRequisitionListUid" value.'));
        }

        if (empty($args['requisitionListItem'])) {
            throw new GraphQlInputException(__('Specify the "requisitionListItem" value.'));
        }
        $sourceId = (int)$this->idEncoder->decode($args['sourceRequisitionListUid']);
        // This will check if the source requisition id is valid
        $this->getRequisitionListForUser->execute($customerId, $sourceId);

        if (isset($args['destinationRequisitionListUid'])) {
            $destinationId = (int)$this->idEncoder->decode($args['destinationRequisitionListUid']);
            $targetRequisitionList = $this->getRequisitionListForUser->execute($customerId, $destinationId);
        } else {
            $targetRequisitionList = $this->copyItemsModel->createNewRequisitionList($customerId);
        }
        $itemIds = array_map(
            function ($id) {
                return $this->idEncoder->decode($id);
            },
            $args['requisitionListItem']['requisitionListItemUids']
        );
        $this->copyItemsModel->execute($targetRequisitionList, $itemIds, $sourceId);

        $data = $this->dataObjectConverter->toFlatArray($targetRequisitionList, [], RequisitionListInterface::class);
        $data['uid'] = $this->idEncoder->encode((string)$targetRequisitionList->getId());
        $data['items_count'] = count($targetRequisitionList->getItems());

        return [
            'requisition_list' => $data
        ];
    }
}
