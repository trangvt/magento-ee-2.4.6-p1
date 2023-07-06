<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Delete;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Get;

class DeleteRequisitionList implements ResolverInterface
{
    /**
     * @var Delete
     */
    private $delete;

    /**
     * @var Get
     */
    private $get;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

    /**
     * @var \Magento\RequisitionList\Model\Config
     */
    private $moduleConfig;

    /**
     * @param Get $get
     * @param Delete $delete
     * @param IdEncoder $idEncoder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Get $get,
        Delete $delete,
        IdEncoder $idEncoder,
        ExtensibleDataObjectConverter $dataObjectConverter,
        ModuleConfig $moduleConfig
    ) {
        $this->get = $get;
        $this->delete = $delete;
        $this->idEncoder = $idEncoder;
        $this->dataObjectConverter = $dataObjectConverter;
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
     * @throws Exception
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

        $requisitionList = $this->get->execute(
            $customerId,
            (int)$this->idEncoder->decode($args['requisitionListUid'])
        );

        return [
            'status' => $this->delete->execute($requisitionList)
        ];
    }
}
