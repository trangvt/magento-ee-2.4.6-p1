<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\User\Model\UserFactory;
use Magento\User\Model\ResourceModel\User;

/**
 * Company contacts data resolver, used for GraphQL request processing.
 */
class Contacts implements ResolverInterface
{
    /**
     * @var ExtractCustomerData
     */
    private $customerData;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var User
     */
    private $userResource;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @param ExtractCustomerData $customerData
     * @param UserFactory $userFactory
     * @param User $userResource
     * @param GetCustomer $getCustomer
     * @param ResolverAccess $resolverAccess
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $allowedResources
     */
    public function __construct(
        ExtractCustomerData $customerData,
        UserFactory $userFactory,
        User $userResource,
        GetCustomer $getCustomer,
        ResolverAccess $resolverAccess,
        CustomerRepositoryInterface $customerRepository,
        array $allowedResources = []
    ) {
        $this->customerData = $customerData;
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
        $this->getCustomer = $getCustomer;
        $this->resolverAccess = $resolverAccess;
        $this->customerRepository = $customerRepository;
        $this->allowedResources = $allowedResources;
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
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $company = $value['model'];

        if (!isset($value['isNewCompany']) || $value['isNewCompany'] !== true) {
            $this->resolverAccess->isAllowed($this->allowedResources);
            $customer = $this->getCustomer->execute($context);
        } else {
            $customer = $customer = $this->customerRepository->getById($company->getSuperUserId());
        }

        $salesRepresentative = $this->userFactory->create();
        $this->userResource->load($salesRepresentative, $company->getSalesRepresentativeId());
        $contactData['company_admin'] = $this->customerData->execute($customer);
        $contactData['sales_representative'] = [
            'email' => $salesRepresentative->getEmail(),
            'firstname' => $salesRepresentative->getFirstname(),
            'lastname' => $salesRepresentative->getLastname()
        ];

        return $contactData[$info->fieldName];
    }
}
