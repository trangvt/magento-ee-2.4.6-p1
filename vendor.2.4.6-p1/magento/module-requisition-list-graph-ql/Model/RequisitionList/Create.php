<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\RequisitionList\Model\RequisitionListFactory;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;

/**
 * Create Requisition list
 */
class Create
{
    /**
     * @var RequisitionListFactory
     */
    private $requisitionListFactory;

    /**
     * @var RequisitionListRepositoryInterface
     */
    private $repository;

    /**
     * Create constructor
     *
     * @param RequisitionListFactory $requisitionListFactory
     * @param RequisitionListRepositoryInterface $repository
     */
    public function __construct(
        RequisitionListFactory $requisitionListFactory,
        RequisitionListRepositoryInterface $repository
    ) {
        $this->requisitionListFactory = $requisitionListFactory;
        $this->repository = $repository;
    }

    /**
     * Create Requisition list for user
     *
     * @param int $customerId
     * @param array $args
     * @return RequisitionListInterface
     * @throws GraphQlInputException
     */
    public function execute(int $customerId, array $args): RequisitionListInterface
    {
        $requisitionList = $this->requisitionListFactory->create();
        $requisitionList->setCustomerId($customerId);
        $requisitionList->setName($args['name']);

        if (isset($args['description'])) {
            $requisitionList->setDescription($args['description']);
        }
        try {
            $requisitionList = $this->repository->save($requisitionList, true);
        } catch (CouldNotSaveException $exception) {
            throw new GraphQlInputException(__('Unable to create requisition list'), $exception);
        }

        return $requisitionList;
    }
}
