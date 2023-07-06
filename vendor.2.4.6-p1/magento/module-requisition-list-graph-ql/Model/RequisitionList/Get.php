<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\RequisitionList\Model\RequisitionListRepository;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

/**
 * Get RequisitionList
 */
class Get
{
    /**
     * @var RequisitionListRepository
     */
    private $repository;

    /**
     * GetList constructor
     *
     * @param RequisitionListRepository $repository
     */
    public function __construct(
        RequisitionListRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get Requisition List for user
     *
     * @param int $customerId
     * @param int $requisitionListId
     * @return RequisitionListInterface
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(int $customerId, int $requisitionListId): RequisitionListInterface
    {
        try {
            $requisitionList = $this->repository->get($requisitionListId);
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()), $exception);
        }

        if ($requisitionList && $requisitionList->getId() && (int)$requisitionList->getCustomerId() !== $customerId) {
            throw new GraphQlAuthorizationException(
                __('The current user cannot perform operations on requisition list')
            );
        }

        return $requisitionList;
    }
}
