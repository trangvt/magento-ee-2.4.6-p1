<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class SharedCatalog implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'name' => 'Shared Catalog %uniqid%',
        'type' => 0,
        'description' => 'Shared Catalog Description %uniqid%',
        'customer_group_id' => null,
        'tax_class_id' => 3,
        'created_by' => 1,
        'store_id' => 0,
        'created_at' => null
    ];

    /**
     * @var SharedCatalogFactory
     */
    private $sharedCatalogFactory;

    /**
     * @var ProcessorInterface
     */
    private $dataProcessor;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @param SharedCatalogFactory $sharedCatalogFactory
     * @param ProcessorInterface $dataProcessor
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(
        SharedCatalogFactory $sharedCatalogFactory,
        ProcessorInterface   $dataProcessor,
        ServiceFactory       $serviceFactory
    ) {
        $this->sharedCatalogFactory = $sharedCatalogFactory;
        $this->dataProcessor = $dataProcessor;
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $serviceSave = $this->serviceFactory->create(SharedCatalogRepositoryInterface::class, 'save');
        $sharedCatalogId = $serviceSave->execute(
            [
                'sharedCatalog' => $this->prepareData($data)
            ]
        );
        $serviceGet = $this->serviceFactory->create(SharedCatalogRepositoryInterface::class, 'get');
        return $serviceGet->execute(
            [
                'sharedCatalogId' => $sharedCatalogId
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $sharedCatalogData = $data;
        $serviceDeleteById = $this->serviceFactory->create(SharedCatalogRepositoryInterface::class, 'deleteById');
        $serviceDeleteById->execute(
            [
                'sharedCatalogId' => $sharedCatalogData->getId()
            ]
        );
    }

    /**
     * Prepare shared catalog data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_DATA, $data);
        return $this->dataProcessor->process($this, $data);
    }
}
