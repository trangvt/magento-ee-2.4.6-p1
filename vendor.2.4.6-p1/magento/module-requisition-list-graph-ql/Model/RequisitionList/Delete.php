<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList;

use Magento\Framework\Exception\StateException;
use Magento\RequisitionList\Model\RequisitionListRepository;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;

/**
 * Delete RequisitionList
 */
class Delete
{
    /**
     * @var RequisitionListRepository
     */
    private $repository;

    /**
     * Delete constructor
     *
     * @param RequisitionListRepository $repository
     */
    public function __construct(
        RequisitionListRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Delete Requisition list for user
     *
     * @param RequisitionListInterface $requisitionList
     * @return bool
     */
    public function execute(RequisitionListInterface $requisitionList)
    {
        try {
            $this->repository->delete($requisitionList);
            $isDeleted = true;
        } catch (StateException $exception) {
            $isDeleted = false;
        }

        return $isDeleted;
    }
}
