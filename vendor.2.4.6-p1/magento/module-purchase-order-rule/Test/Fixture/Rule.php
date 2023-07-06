<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRule\Model\Rule\Condition\Address;
use Magento\PurchaseOrderRule\Model\Rule\Condition\Combine;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creating a new purchase order rule
 */
class Rule implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'id' => null,
        'name' => 'Rule %uniqid%',
        'description' => 'Rule Description %uniqid%',
        'active' => 1,
        'company_id' => null,
        'conditions_serialized' => null,
        'approver_role_ids' => [],
        'admin_approval_required' => true,
        'manager_approval_required' => true,
        'applies_to_all' => 1,
        'applies_to_role_ids' => [],
        'created_by' => null
    ];

    private const DEFAULT_CONDITIONS = [
        'type' => Combine::class,
        'attribute' => null,
        'operator' => null,
        'value' => '1',
        'is_value_processed' => null,
        'aggregator' => 'all',
        'conditions' => [
            [
                'type' => Address::class,
                'attribute' => 'grand_total',
                'operator' => '>',
                'value' => '5',
                'currency_code' => 'USD',
                'is_value_processed' => false
            ]
        ]
    ];

    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var DataMerger
     */
    private DataMerger $dataMerger;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $processor;

    /**
     * @param ServiceFactory $serviceFactory
     * @param DataMerger $dataMerger
     * @param ProcessorInterface $processor
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        DataMerger $dataMerger,
        ProcessorInterface $processor
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataMerger = $dataMerger;
        $this->processor = $processor;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $data['conditions_serialized'] = $data['conditions_serialized']
            ?? json_encode($data['conditions'] ?? self::DEFAULT_CONDITIONS);

        return $this->serviceFactory->create(RuleRepositoryInterface::class, 'save')->execute(
            [
                'rule' => $this->processor->process($this, $this->dataMerger->merge(self::DEFAULT_DATA, $data))
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $this->serviceFactory->create(RuleRepositoryInterface::class, 'deleteById')->execute(
            [
                'rule' => $data['rule_id']
            ]
        );
    }
}
