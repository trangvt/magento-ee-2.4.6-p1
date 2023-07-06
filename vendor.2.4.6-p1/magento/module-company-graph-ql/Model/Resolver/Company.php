<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Company data resolver, used for GraphQL request processing.
 */
class Company implements ResolverInterface
{
    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @param ResolverAccess $resolverAccess
     * @param CompanyManagementInterface $companyManagement
     * @param array $allowedResources
     */
    public function __construct(
        ResolverAccess $resolverAccess,
        CompanyManagementInterface $companyManagement,
        array $allowedResources = []
    ) {
        $this->companyManagement = $companyManagement;
        $this->resolverAccess = $resolverAccess;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $this->resolverAccess->isAllowed($this->allowedResources);
        $company = $this->companyManagement->getByCustomerId($context->getUserId());

        return [
            'model' => $company
        ];
    }
}
