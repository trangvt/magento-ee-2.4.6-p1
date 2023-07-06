<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver;

use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Get;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Update;

/**
 * Update RequisitionList Resolver
 */
class UpdateRequisitionList implements ResolverInterface
{
    /**
     * @var Update
     */
    private $update;

    /**
     * @var Get
     */
    private $get;

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
     * @param Get $get
     * @param Update $update
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param IdEncoder $idEncoder
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Get $get,
        Update $update,
        ExtensibleDataObjectConverter $dataObjectConverter,
        IdEncoder $idEncoder,
        ModuleConfig $moduleConfig
    ) {
        $this->get = $get;
        $this->update = $update;
        $this->dataObjectConverter = $dataObjectConverter;
        $this->idEncoder = $idEncoder;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|Value
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
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

        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $requisitionList = $this->get->execute(
            $customerId,
            (int)$this->idEncoder->decode($args['requisitionListUid'])
        );

        $requisitionList = $this->update->execute($requisitionList, $args['input']);

        $data = $this->dataObjectConverter->toFlatArray(
            $requisitionList,
            [],
            RequisitionListInterface::class
        );
        $data['uid'] = $this->idEncoder->encode((string)$requisitionList->getId());
        $data['items_count'] = count($requisitionList->getItems());

        return [
            'requisition_list' => $data
        ];
    }
}
