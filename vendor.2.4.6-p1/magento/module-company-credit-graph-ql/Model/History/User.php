<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCreditGraphQl\Model\History;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Zend_Db_Select_Exception;

/**
 * Get users by name
 */
class User
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get company user's ids by name
     *
     * @param string $name
     * @param int $companyId
     * @return array
     * @throws Zend_Db_Select_Exception
     */
    public function getHistoryUserIdsByName(string $name, int $companyId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $nameExpr = "%{$name}%";

        $subSelect1 = $connection->select()->from(
            ['main_table' => $this->resourceConnection->getTableName('company_credit_history')],
            ['user_id' => 'main_table.user_id']
        )->joinLeft(
            ['au' => $this->resourceConnection->getTableName('admin_user')],
            "main_table.user_id = au.user_id",
            []
        )->joinLeft(
            ['cc' => $this->resourceConnection->getTableName('company_credit')],
            "main_table.company_credit_id = cc.entity_id",
            []
        )->where(
            'cc.company_id = ?',
            $companyId
        )->where(new \Zend_Db_Expr("CONCAT(au.firstname, ' ', au.lastname) LIKE ?"), $nameExpr);

        $subSelect2 = $connection->select()->from(
            ['main_table' => $this->resourceConnection->getTableName('company_credit_history')],
            ['user_id' => 'main_table.user_id']
        )->joinLeft(
            ['ce' => $this->resourceConnection->getTableName('customer_entity')],
            "main_table.user_id = ce.entity_id",
            []
        )->joinLeft(
            ['cc' => $this->resourceConnection->getTableName('company_credit')],
            "main_table.company_credit_id = cc.entity_id",
            []
        )->where(
            'cc.company_id = ?',
            $companyId
        )->where(new \Zend_Db_Expr("CONCAT(ce.firstname, ' ', ce.lastname) LIKE ?"), $nameExpr);

        $sql = $this->resourceConnection->getConnection()->select()
            ->union(
                [
                    $subSelect1,
                    $subSelect2
                ],
                Select::SQL_UNION
            );

        return $connection->fetchCol($sql);
    }
}
