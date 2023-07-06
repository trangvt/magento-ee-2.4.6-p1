<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Model;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Shared catalog resolver.
 *
 * For general purposes use Magento\SharedCatalog\Model\CustomGroupManagement
 * This is a lightweight service for identifying if customer group is a shared catalog.
 */
class SharedCatalogResolver
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * SharedCatalogResolver constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Check if primary catalog should be displayed for customer group.
     *
     * @param int $customerGroupId
     * @return bool
     */
    public function isPrimaryCatalogAvailable(int $customerGroupId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(
            ['customer_group' => $this->resourceConnection->getTableName('customer_group')],
            ['customer_group_id']
        );
        $select->joinLeft(
            ['shared_catalog' => $this->resourceConnection->getTableName('shared_catalog')],
            'customer_group.customer_group_id = shared_catalog.customer_group_id',
            []
        );
        $select->where(
            '(shared_catalog.entity_id IS NULL AND customer_group.customer_group_id != ?)',
            GroupInterface::NOT_LOGGED_IN_ID
        );

        $values = [];
        foreach ($connection->fetchCol($select) as $value) {
            $values[] = (int) $value;
        }

        return isset(array_flip($values)[$customerGroupId]);
    }
}
