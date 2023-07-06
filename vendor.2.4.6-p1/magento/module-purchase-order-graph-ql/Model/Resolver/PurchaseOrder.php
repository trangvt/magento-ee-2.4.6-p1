<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\PurchaseOrderRepository;
use Magento\PurchaseOrderGraphQl\Model\GetPurchaseOrderData;

/**
 * Purchase Order Resolver IsEnabled
 */
class PurchaseOrder implements ResolverInterface
{
    /**
     * @var PurchaseOrderRepository
     */
    private PurchaseOrderRepository $purchaseOrderRepository;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @var GetPurchaseOrderData
     */
    private GetPurchaseOrderData $getPurchaseOrderData;

    /**
     * @var Authorization
     */
    private Authorization $authorization;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @param PurchaseOrderRepository $purchaseOrderRepository
     * @param ResolverAccess $resolverAccess
     * @param GetPurchaseOrderData $getPurchaseOrderData
     * @param Authorization $authorization
     * @param Uid $uid
     * @param array $allowedResources
     */
    public function __construct(
        PurchaseOrderRepository $purchaseOrderRepository,
        ResolverAccess          $resolverAccess,
        GetPurchaseOrderData    $getPurchaseOrderData,
        Authorization           $authorization,
        Uid                     $uid,
        array                   $allowedResources = []
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->getPurchaseOrderData = $getPurchaseOrderData;
        $this->authorization = $authorization;
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

        $this->resolverAccess->isAllowed($this->allowedResources);

        if (empty($args['uid'])) {
            throw new GraphQlInputException(__('Required parameter "uid" is missing.'));
        }

        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($this->uid->decode($args['uid']));
        } catch (NoSuchEntityException $e) {
            throw new GraphQlInputException(
                __(
                    'No such entity with %fieldName = %fieldValue',
                    [
                        'fieldName' => 'entity_id',
                        'fieldValue' => $args['uid']
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

        return $this->getPurchaseOrderData->execute($purchaseOrder);
    }
}
