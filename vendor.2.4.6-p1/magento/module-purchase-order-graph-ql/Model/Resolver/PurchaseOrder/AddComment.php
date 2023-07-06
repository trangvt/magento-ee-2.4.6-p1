<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver\PurchaseOrder;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrderGraphQl\Model\GetCommentData;

/**
 * Purchase Order Resolver for addPurchaseOrderComment mutation
 */
class AddComment implements ResolverInterface
{
    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @var CommentManagement
     */
    private CommentManagement $commentManagement;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private PurchaseOrderRepositoryInterface $purchaseOrderRepository;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var GetCommentData
     */
    private GetCommentData $getCommentData;

    /**
     * @param CommentManagement $commentManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param Uid $uid
     * @param ResolverAccess $resolverAccess
     * @param GetCommentData $getCommentData
     * @param array $allowedResources
     */
    public function __construct(
        CommentManagement $commentManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        Uid $uid,
        ResolverAccess $resolverAccess,
        GetCommentData $getCommentData,
        array $allowedResources = []
    ) {
        $this->commentManagement = $commentManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->uid = $uid;
        $this->allowedResources = $allowedResources;
        $this->resolverAccess = $resolverAccess;
        $this->getCommentData = $getCommentData;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);

        if (empty($args['input']['purchase_order_uid'])) {
            throw new GraphQlInputException(
                __('Required parameter "%param" is missing', ['param' => 'purchase_order_uid'])
            );
        }

        if (empty(trim($args['input']['comment']))) {
            throw new GraphQlInputException(
                __('Required parameter "%param" is missing', ['param' => 'comment'])
            );
        }

        $id = $args['input']['purchase_order_uid'];
        $comment = $args['input']['comment'];

        try {
            $this->purchaseOrderRepository->getById(
                $this->uid->decode($args['input']['purchase_order_uid'])
            );
            $createdComment = $this->commentManagement->addComment(
                $this->uid->decode($id),
                $context->getUserId(),
                $comment
            );
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(
                __('Purchase order with requested ID=%number not found.', ['number' => $id])
            );
        }

        return [
            'comment' => $this->getCommentData->execute($createdComment)
        ];
    }
}
