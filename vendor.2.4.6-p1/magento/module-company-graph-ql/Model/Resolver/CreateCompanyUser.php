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
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Create company user resolver.
 */
class CreateCompanyUser implements ResolverInterface
{
    /**
     * Authorization level of a company session.
     */
    public const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\CompanyGraphQl\Model\Company\Users\CreateCompanyUser
     */
    private $createCompanyUser;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Data
     */
    private $helper;

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
     * @param \Magento\CompanyGraphQl\Model\Company\Users\CreateCompanyUser $createCompanyUser
     * @param Formatter $formatter
     * @param Uid $idEncoder
     * @param ResolverAccess $resolverAccess
     * @param Data $helper
     * @param Validator $validator
     */
    public function __construct(
        \Magento\CompanyGraphQl\Model\Company\Users\CreateCompanyUser $createCompanyUser,
        Formatter $formatter,
        Uid $idEncoder,
        ResolverAccess $resolverAccess,
        Data $helper,
        Validator $validator
    ) {
        $this->createCompanyUser = $createCompanyUser;
        $this->formatter = $formatter;
        $this->idEncoder = $idEncoder;
        $this->resolverAccess = $resolverAccess;
        $this->helper = $helper;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($this->allowedResources);

        if (isset($args['input']['role_id'])) {
            $args['input']['role_id'] = $this->idEncoder->decode($args['input']['role_id']);
        }

        if (isset($args['input']['target_id'])) {
            $args['input']['target_id'] = $this->idEncoder->decode($args['input']['target_id']);
        }

        $this->validator->validateUserCreating($args['input']);
        $customerData = $this->helper->setCompanyAttributes($args['input'], $this->companyAttributes);

        /** @var ContextInterface $context */
        $user = $this->createCompanyUser->execute($customerData);
        if ($user === null) {
            throw new GraphQlNoSuchEntityException(__('Something went wrong'));
        }

        return ['user' => $this->formatter->formatUser($user)];
    }
}
