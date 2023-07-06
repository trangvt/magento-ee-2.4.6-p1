<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrderGraphQl\Model\GetPurchaseOrderData;

/**
 * Purchase Orders Resolver
 */
class PurchaseOrders implements ResolverInterface
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private PurchaseOrderRepositoryInterface $purchaseOrderRepository;

    /**
     * @var GetPurchaseOrderData
     */
    private GetPurchaseOrderData $getPurchaseOrderData;

    /**
     * @var GetPurchaseOrdersSearchCriteria
     */
    private GetPurchaseOrdersSearchCriteria $purchaseOrdersSearchCriteria;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param ResolverAccess $resolverAccess
     * @param GetPurchaseOrderData $getPurchaseOrderData
     * @param GetPurchaseOrdersSearchCriteria $purchaseOrdersSearchCriteria
     * @param array $allowedResources
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        ResolverAccess $resolverAccess,
        GetPurchaseOrderData $getPurchaseOrderData,
        GetPurchaseOrdersSearchCriteria $purchaseOrdersSearchCriteria,
        array $allowedResources = []
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->resolverAccess = $resolverAccess;
        $this->getPurchaseOrderData = $getPurchaseOrderData;
        $this->purchaseOrdersSearchCriteria = $purchaseOrdersSearchCriteria;
        $this->allowedResources = $allowedResources;
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

        $filterArgs = $args['filter'] ?? [];

        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        $pageSize = $args['pageSize'] ?? 20;
        $currentPage = $args['currentPage'] ?? 1;

        $searchResult = $this->purchaseOrderRepository->getList(
            $this->purchaseOrdersSearchCriteria->execute($filterArgs, $currentPage, $pageSize, $context->getUserId())
        );

        return [
            'items' => array_map(
                function (PurchaseOrderInterface $purchaseOrder) {
                    return $this->getPurchaseOrderData->execute($purchaseOrder);
                },
                $searchResult->getItems()
            ),
            'total_count' => $searchResult->getTotalCount(),
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $searchResult->getSearchCriteria()->getCurrentPage(),
                'total_pages' => $pageSize ? ((int)ceil($searchResult->getTotalCount() / $pageSize)) : 0
            ],
        ];
    }
}
