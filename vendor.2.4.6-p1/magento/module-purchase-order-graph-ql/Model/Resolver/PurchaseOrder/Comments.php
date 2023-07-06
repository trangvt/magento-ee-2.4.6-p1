<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver\PurchaseOrder;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Api\Data\CommentInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\CommentRepositoryInterface;
use Magento\PurchaseOrderGraphQl\Model\GetCommentData;

/**
 * Purchase Order comments Resolver
 */
class Comments implements ResolverInterface
{
    /**
     * @var CommentRepositoryInterface
     */
    private CommentRepositoryInterface $commentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var GetCommentData
     */
    private GetCommentData $getCommentData;

    /**
     * @param CommentRepositoryInterface $commentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetCommentData $getCommentData
     */
    public function __construct(
        CommentRepositoryInterface $commentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetCommentData $getCommentData
    ) {
        $this->commentRepository = $commentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getCommentData = $getCommentData;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var PurchaseOrderInterface $purchaseOrder */
        $purchaseOrder = $value['model'];

        return array_values(
            array_map(
                function (CommentInterface $comment) {
                    return $this->getCommentData->execute($comment);
                },
                $this->commentRepository->getList(
                    $this->searchCriteriaBuilder->addFilter(
                        CommentInterface::PURCHASE_ORDER_ID,
                        $purchaseOrder->getEntityId()
                    )->create()
                )->getItems()
            )
        );
    }
}
