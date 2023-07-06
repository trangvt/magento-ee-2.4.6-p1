<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Role\DeleteRole;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Delete company role resolver
 */
class DeleteCompanyRole implements ResolverInterface
{
    /**
     * Authorization level of a company session.
     */
    public const COMPANY_RESOURCE = 'Magento_Company::roles_edit';

    /**
     * @var DeleteRole
     */
    private $deleteRole;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var array
     */
    private $allowedResources = [self::COMPANY_RESOURCE];

    /**
     * @param DeleteRole $deleteRole
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     */
    public function __construct(
        DeleteRole $deleteRole,
        ResolverAccess $resolverAccess,
        Uid $idEncoder
    ) {
        $this->deleteRole = $deleteRole;
        $this->resolverAccess = $resolverAccess;
        $this->idEncoder = $idEncoder;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($this->allowedResources);

        if (empty($args['id'])) {
            throw new GraphQlInputException(__('"id" value should be specified'));
        }

        return ['success' => $this->deleteRole->execute((int)$this->idEncoder->decode($args['id']))];
    }
}
