<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Delete company user resolver
 */
class DeleteCompanyUser implements ResolverInterface
{
    /**
     * Authorization level of a company session.
     */
    public const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\CompanyGraphQl\Model\Company\Users\DeleteCompanyUser
     */
    private $deleteCompanyUser;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources = [self::COMPANY_RESOURCE];

    /**
     * @param \Magento\CompanyGraphQl\Model\Company\Users\DeleteCompanyUser $deleteCompanyUser
     * @param Uid $idEncoder
     * @param ResolverAccess $resolverAccess
     */
    public function __construct(
        \Magento\CompanyGraphQl\Model\Company\Users\DeleteCompanyUser $deleteCompanyUser,
        Uid $idEncoder,
        ResolverAccess $resolverAccess
    ) {
        $this->deleteCompanyUser = $deleteCompanyUser;
        $this->idEncoder = $idEncoder;
        $this->resolverAccess = $resolverAccess;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($this->allowedResources);

        if (!($args['id'])) {
            throw new GraphQlInputException(__('"id" value should be specified'));
        }

        return ['success' => $this->deleteCompanyUser->execute((int)$this->idEncoder->decode($args['id']))];
    }
}
