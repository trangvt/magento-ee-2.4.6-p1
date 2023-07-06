<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Helper\Data;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Users\Formatter;
use Magento\CompanyGraphQl\Model\Company\Users\Validator;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Update company user resolver
 */
class UpdateCompanyUser implements ResolverInterface
{
    /**
     * Authorization level of a company session.
     */
    public const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\CompanyGraphQl\Model\Company\Users\UpdateCompanyUser
     */
    private $updateCompanyUser;

    /**
     * @var Formatter
     */
    private $userFormatter;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var array
     */
    private $allowedResources = [self::COMPANY_RESOURCE];

    /**
     * @var array
     */
    private $companyAttributes = [
        'job_title',
        'telephone',
        'status'
    ];

    /**
     * @param \Magento\CompanyGraphQl\Model\Company\Users\UpdateCompanyUser $updateCompanyUser
     * @param Formatter $userFormatter
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     * @param Validator $validator
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\CompanyGraphQl\Model\Company\Users\UpdateCompanyUser $updateCompanyUser,
        Formatter $userFormatter,
        ResolverAccess $resolverAccess,
        Uid $idEncoder,
        Validator $validator,
        Data $helper,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->updateCompanyUser = $updateCompanyUser;
        $this->userFormatter = $userFormatter;
        $this->resolverAccess = $resolverAccess;
        $this->idEncoder = $idEncoder;
        $this->validator = $validator;
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($this->allowedResources);

        $args['input']['id'] = $this->idEncoder->decode($args['input']['id']);

        if (isset($args['input']['role_id'])) {
            $args['input']['role_id'] = $this->idEncoder->decode($args['input']['role_id']);
        }

        $this->validator->validateUserUpdating($args['input']);

        $customer = $this->customerRepository->getById($args['input']['id']);
        $customerData = $this->helper->setCompanyAttributes($args['input'], $this->companyAttributes, $customer);

        $customer = $this->updateCompanyUser->execute($customer, $customerData);
        if ($customer === null) {
            throw new GraphQlNoSuchEntityException(__('Something went wrong'));
        }

        return ['user' => $this->userFormatter->formatUser($customer)];
    }
}
