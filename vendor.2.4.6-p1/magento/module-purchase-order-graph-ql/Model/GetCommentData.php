<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\PurchaseOrder\Api\Data\CommentInterface;

/**
 * Retrieve formatted purchase order comment data for GraphQL response
 */
class GetCommentData
{
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var ExtractCustomerData
     */
    private ExtractCustomerData $extractCustomerData;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ExtractCustomerData $extractCustomerData
     * @param Uid $uid
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ExtractCustomerData $extractCustomerData,
        Uid $uid
    ) {
        $this->customerRepository = $customerRepository;
        $this->extractCustomerData = $extractCustomerData;
        $this->uid = $uid;
    }

    /**
     * Retrieve formatted purchase order comment data for GraphQL response
     *
     * @param CommentInterface $comment
     * @return array
     * @throws LocalizedException
     */
    public function execute(CommentInterface $comment): array
    {
        try {
            $customer = $this->customerRepository->getById($comment->getCreatorId());
            $author = $this->extractCustomerData->execute($customer);
            $author['model'] = $customer;
        } catch (NoSuchEntityException $exception) {
            $author = null;
        }

        return [
            'uid' => $this->uid->encode((string)$comment->getEntityId()),
            'created_at' => $comment->getCreatedAt(),
            'author' => $author,
            'text' => $comment->getComment(),
        ];
    }
}
