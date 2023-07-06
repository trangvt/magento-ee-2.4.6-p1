<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Fixture;

use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creating a new company role
 */
class Role implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'id' => null,
        'role_name' => 'Role %uniqid%',
        'company_id' => null,
        'permissions' => []
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
        return $this->serviceFactory->create(RoleRepositoryInterface::class, 'save')->execute(
            [
                'role' => $this->processor->process($this, $this->dataMerger->merge(self::DEFAULT_DATA, $data))
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $this->serviceFactory->create(RoleRepositoryInterface::class, 'delete')->execute(
            [
                'roleId' => $data['role_id']
            ]
        );
    }
}
