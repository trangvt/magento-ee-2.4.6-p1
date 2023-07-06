<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\RequisitionListRepository;

/**
 * Update RequisitionList
 */
class Update
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
     * Rename Requisition list for user
     *
     * @param  RequisitionListInterface $requisitionList
     * @param  array                    $args
     * @return RequisitionListInterface
     * @throws GraphQlInputException
     */
    public function execute(RequisitionListInterface $requisitionList, array $args)
    {
        if (isset($args['name'])) {
            $requisitionList->setName($args['name']);
        }

        if (isset($args['description'])) {
            $requisitionList->setDescription($args['description']);
        }

        try {
            $requisitionList = $this->repository->save($requisitionList, true);
        } catch (CouldNotSaveException $exception) {
            throw new GraphQlInputException(
                __('Unable to update Requisition list'),
                $exception
            );
        }

        return $requisitionList;
    }
}
