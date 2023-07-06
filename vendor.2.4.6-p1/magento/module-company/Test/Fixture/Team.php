<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Fixture;

use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Creating a new company team
 */
class Team implements RevertibleDataFixtureInterface
{
    private const COMPANY_ID = 'company_id';
    private const TEAM = 'team';
    private const DEFAULT_DATA = [
        self::COMPANY_ID => null,
        self::TEAM => [
            'team_id' => null,
            'name' => 'Team %uniqid%',
            'description' => 'Team %uniqid% description',
            'target_id' => 0
        ]
    ];

    /**
     * @var DataMerger
     */
    private DataMerger $dataMerger;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $processor;

    /**
     * @var TeamInterfaceFactory
     */
    private TeamInterfaceFactory $factory;

    /**
     * @var TeamRepositoryInterface
     */
    private TeamRepositoryInterface $repository;

    /**
     * @param DataMerger $dataMerger
     * @param ProcessorInterface $processor
     * @param TeamInterfaceFactory $factory
     * @param TeamRepositoryInterface $repository
     */
    public function __construct(
        DataMerger $dataMerger,
        ProcessorInterface $processor,
        TeamInterfaceFactory $factory,
        TeamRepositoryInterface $repository
    ) {
        $this->dataMerger = $dataMerger;
        $this->processor = $processor;
        $this->factory = $factory;
        $this->repository = $repository;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        if (empty($data[self::COMPANY_ID])) {
            throw new InvalidArgumentException(__('"%field" is required', ['field' => self::COMPANY_ID]));
        }

        $team = $this->factory->create(
            [
                'data' => $this->processor->process(
                    $this,
                    $this->dataMerger->merge(self::DEFAULT_DATA[self::TEAM], $data[self::TEAM] ?? [])
                )
            ]
        );
        $team->setHasDataChanges(true);
        $this->repository->create($team, $data[self::COMPANY_ID]);

        return $this->repository->get($team->getId());
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $this->repository->deleteById($data['id']);
    }
}
