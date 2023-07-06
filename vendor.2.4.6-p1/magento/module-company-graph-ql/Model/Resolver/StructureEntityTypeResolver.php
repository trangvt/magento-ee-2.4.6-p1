<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Api\Data\HierarchyInterface;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Company Structure Entity type resolver, used for GraphQL request processing.
 */
class StructureEntityTypeResolver implements TypeResolverInterface
{

    /**
     * Query type Team
     */
    public const QUERY_TYPE_TEAM = 'CompanyTeam';

    /**
     * Query type Customer
     */
    public const QUERY_TYPE_CUSTOMER = 'Customer';

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        switch ($data['type']) {
            case HierarchyInterface::TYPE_TEAM:
                $type = self::QUERY_TYPE_TEAM;
                break;
            case HierarchyInterface::TYPE_CUSTOMER:
                $type = self::QUERY_TYPE_CUSTOMER;
                break;
            default:
                $type = '';
        }

        return $type;
    }
}
