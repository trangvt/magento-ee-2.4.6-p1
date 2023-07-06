<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\Permission\CategoryPermissions;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationListInterface;
use Magento\CatalogPermissions\Model\Indexer\CustomerGroupFilter;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\Permissions\Synchronizer;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\Consumer;
use Magento\SharedCatalog\Model\SharedCatalogInvalidation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for category permissions consumer.
 */
class ConsumerTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var Synchronizer|MockObject
     */
    private $permissionsSynchronizer;

    /**
     * @var SharedCatalogInvalidation|MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * @var CustomerGroupFilter
     */
    private $customerGroupFilter;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->serializer = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->permissionsSynchronizer = $this->createMock(Synchronizer::class);
        $this->sharedCatalogInvalidation = $this->createMock(SharedCatalogInvalidation::class);
        $this->customerGroupFilter = $this->createMock(CustomerGroupFilter::class);

        $objectManager = new ObjectManagerHelper($this);
        $this->consumer = $objectManager->getObject(
            Consumer::class,
            [
                'logger' => $this->logger,
                'entityManager' => $this->entityManager,
                'serializer' => $this->serializer,
                'permissionsSynchronizer' => $this->permissionsSynchronizer,
                'sharedCatalogInvalidation' => $this->sharedCatalogInvalidation,
                'customerGroupFilter' => $this->customerGroupFilter,
            ]
        );
    }

    /**
     * Test for processOperations method.
     *
     * @return void
     */
    public function testProcessOperations()
    {
        $data = [
            'category_id' => 1,
            'group_ids' => '2,3',
        ];

        $operation = $this->getMockBuilder(OperationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $operationList = $this->getMockBuilder(OperationListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $operationList->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([$operation]);
        $operation->expects($this->once())
            ->method('getSerializedData')
            ->willReturn(json_encode($data));
        $this->serializer->expects($this->once())
            ->method('unserialize')
            ->with(json_encode($data))
            ->willReturn($data);
        $this->permissionsSynchronizer->expects($this->once())
            ->method('updateCategoryPermissions')
            ->with($data['category_id'], explode(',', $data['group_ids']));
        $this->sharedCatalogInvalidation->expects($this->once())
            ->method('reindexCatalogPermissions')
            ->with([$data['category_id']]);
        $operation->expects($this->once())->method('setStatus')
            ->with(OperationInterface::STATUS_TYPE_COMPLETE)
            ->willReturnSelf();
        $operation->expects($this->once())
            ->method('setResultMessage')
            ->with(null)
            ->willReturnSelf();
        $this->entityManager->expects($this->once())
            ->method('save')
            ->with($operationList)
            ->willReturn($operationList);
        $this->customerGroupFilter->expects($this->once())
            ->method('setGroupIds')
            ->with(explode(',', $data['group_ids']));
        $this->consumer->processOperations($operationList);
    }
}
