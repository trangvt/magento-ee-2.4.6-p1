<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

class GetCustomerHeaders
{
    /**
     * Get customer authentication header to perform Purchase Order validations
     *
     * @param string $customerFixtureName
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function execute(string $customerFixtureName = 'customer'): array
    {
        /** @var CustomerInterface $customer */
        $customer = DataFixtureStorageManager::getStorage()->get($customerFixtureName);
        return Bootstrap::getObjectManager()->get(GetCustomerAuthenticationHeader::class)
            ->execute($customer->getEmail());
    }
}
