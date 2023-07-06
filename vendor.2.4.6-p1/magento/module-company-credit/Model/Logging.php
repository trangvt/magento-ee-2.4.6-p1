<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CompanyCredit\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\Processor;

/**
 * Class responsible for providing additional info on company credit actions
 */
class Logging
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param RequestInterface $request
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        RequestInterface $request,
        ResourceConnection $resourceConnection
    ) {
        $this->request = $request;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Custom handler for reimburse balance action
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processor
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchReimburseBalance(array $config, Event $eventModel, Processor $processor)
    {
        try {
            $connection = $this->resourceConnection->getConnection();

            $companyQuery = $connection->select()
                ->from(['main_table' => $this->resourceConnection->getTableName('company')]);

            $post = $this->request->getParams();
            if (empty($post['history_id']) && empty($post['id'])) {
                return $eventModel->setInfo(__('Missing parameters to complete action request'));
            }

            if (!empty($post['history_id'])) {
                $companyQuery->join(
                    ['cc' => $this->resourceConnection->getTableName('company_credit')],
                    'main_table.entity_id = cc.company_id',
                    []
                )->join(
                    ['cch' => $this->resourceConnection->getTableName('company_credit_history')],
                    'cch.company_credit_id = cc.entity_id AND cch.entity_id = ' . $post['history_id'],
                    []
                );
            } else {
                $companyQuery->where('entity_id = ?', $post['id']);
            }
            $companyRow = $connection->fetchRow($companyQuery);

            $reimburseBalanceData = [__('Company id: %1 (%2)', $companyRow['entity_id'], $companyRow['company_name'])];
            foreach ($this->request->getParam('reimburse_balance') as $fieldName => $fieldValue) {
                $reimburseBalanceData[] = $fieldName . ': ' . $fieldValue;
            }
            return $eventModel->setInfo(implode('; ', $reimburseBalanceData));
        } catch (\Exception $e) {
            return $eventModel->setInfo(__('Error during reimburse: %1', $e->getMessage()));
        }
    }

    /**
     * Custom handler for mass convert action
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processor
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchMassConvert(array $config, Event $eventModel, Processor $processor)
    {
        try {
            $convertedIds = $processor->getCollectedIds();

            $connection = $this->resourceConnection->getConnection();

            $companyQuery = $connection->select()
                ->from(['main_table' => $this->resourceConnection->getTableName('company')])
                ->joinInner(
                    ['cc' => $this->resourceConnection->getTableName('company_credit')],
                    'main_table.entity_id = cc.company_id',
                    []
                )
                ->where('cc.entity_id IN (?)', $convertedIds);

            $params = $this->request->getParams();
            if (empty($params['currency_to']) && empty($params['currency_rates'])) {
                return $eventModel->setInfo('Missing parameters to complete action request');
            }

            $infoMessage = sprintf(
                'Currency to: %s; Rates: %s; ',
                $params['currency_to'],
                implode(', ', $params['currency_rates'])
            );

            $infoMessageCompanies = [];
            foreach ($connection->fetchAll($companyQuery) as $row) {
                $infoMessageCompanies []= sprintf('%s (%s)', $row['entity_id'], $row['company_name']);
            }
            $infoMessageCompanies = implode(', ', $infoMessageCompanies);
            $infoMessage .= sprintf('Affected companies: %s', $infoMessageCompanies);

            return $eventModel->setInfo($infoMessage);
        } catch (\Exception $e) {
            return $eventModel->setInfo(sprintf('Error during mass convert: %s', $e->getMessage()));
        }
    }
}
