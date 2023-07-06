<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCreditGraphQl\Model\Credit;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\User\Model\ResourceModel\User;
use Magento\User\Model\UserFactory;

/**
 * Operation user data provider
 */
class OperationUser
{
    /**
     * @var array
     */
    private $creditHistoryUserType;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var User
     */
    private $userResource;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param UserFactory $userFactory
     * @param User $userResource
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $creditHistoryUserType
     */
    public function __construct(
        UserFactory $userFactory,
        User $userResource,
        CustomerRepositoryInterface $customerRepository,
        array $creditHistoryUserType = []
    ) {
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
        $this->customerRepository = $customerRepository;
        $this->creditHistoryUserType = $creditHistoryUserType;
    }

    /**
     * Get user type by id
     *
     * @param int $userId
     * @return string
     */
    public function getUserType(int $userId): string
    {
        return array_search($userId, array_column($this->creditHistoryUserType, 'value', 'label'), false);
    }

    /**
     * Get operation user name
     *
     * @param int $userType
     * @param int $userId
     * @return string
     */
    public function getUserName(int $userType, int $userId): string
    {
        switch ($userType) {
            case UserContextInterface::USER_TYPE_ADMIN:
                $salesRepresentative = $this->userFactory->create();
                $this->userResource->load($salesRepresentative, $userId);
                $name = sprintf('%s %s', $salesRepresentative->getFirstName(), $salesRepresentative->getLastName());
                break;
            case UserContextInterface::USER_TYPE_CUSTOMER:
                $customer = $this->customerRepository->getById($userId);
                $name = sprintf('%s %s', $customer->getFirstName(), $customer->getLastName());
                break;
            default:
                $name = '';
        }

        return $name;
    }
}
