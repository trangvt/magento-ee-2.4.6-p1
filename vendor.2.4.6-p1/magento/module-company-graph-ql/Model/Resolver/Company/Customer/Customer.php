<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company\Customer;

use Magento\CompanyGraphQl\Model\Company\Users;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Retrieve company data for customer.
 */
class Customer implements ResolverInterface
{
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
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        $customer = $value['model'];

        if (!$customer || !$customer->getId()) {
            throw new GraphQlInputException(__('Customer is not a company user.'));
        }
        $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        $values = [
            'job_title' => $companyAttributes->getJobTitle(),
            'status' => $companyAttributes->getStatus() ? Users::STATUS_ACTIVE : Users::STATUS_INACTIVE,
            'telephone' => $companyAttributes->getTelephone()
        ];

        return $values[$field->getName()] ?? null;
    }
}
