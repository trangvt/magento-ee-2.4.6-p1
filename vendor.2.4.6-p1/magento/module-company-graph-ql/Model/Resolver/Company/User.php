<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Users\Customer;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Provides customer company user data
 */
class User implements ResolverInterface
{
    /**
     * @var ExtractCustomerData
     */
    private $customerData;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var Customer
     */
    private $customerUser;

    /**
     * @param ExtractCustomerData $customerData
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     * @param Customer $customerUser
     * @param array $allowedResources
     */
    public function __construct(
        ExtractCustomerData $customerData,
        ResolverAccess $resolverAccess,
        Uid $idEncoder,
        Customer $customerUser,
        array $allowedResources = []
    ) {
        $this->customerData = $customerData;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->idEncoder = $idEncoder;
        $this->customerUser = $customerUser;
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
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('Required parameter "id" is missing'));
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);
        $company = $value['model'];
        $customer = $this->customerUser->getCustomerById((int)$this->idEncoder->decode($args['id']));
        $customerCompanyAttributes = $this->customerUser->getCustomerCompanyAttributes($customer);

        return $customerCompanyAttributes !== null
            && (int)$customerCompanyAttributes->getCompanyId() === (int)$company->getId()
            ? $this->customerData->execute($customer) : null;
    }
}
