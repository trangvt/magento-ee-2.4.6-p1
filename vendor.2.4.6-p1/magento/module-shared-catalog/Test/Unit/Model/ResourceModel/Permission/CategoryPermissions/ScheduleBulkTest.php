<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\Permission\CategoryPermissions;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for category permissions scheduler.
 */
class ScheduleBulkTest extends TestCase
{
    /**
     * @var BulkManagementInterface|MockObject
     */
    private $bulkManagement;

    /**
     * @var OperationInterfaceFactory|MockObject
     */
    private $operationFactory;

    /**
     * @var IdentityGeneratorInterface|MockObject
     */
    private $identityService;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private $groupRepository;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var ScheduleBulk
     */
    private $scheduleBulk;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->bulkManagement = $this->getMockForAbstractClass(BulkManagementInterface::class);
        $this->operationFactory = $this->getMockBuilder(OperationInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->identityService = $this->getMockForAbstractClass(IdentityGeneratorInterface::class);
        $this->serializer = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->groupRepository = $this->getMockForAbstractClass(GroupRepositoryInterface::class);
        $this->userContext = $this->getMockForAbstractClass(UserContextInterface::class);

        $objectManager = new ObjectManagerHelper($this);
        $this->scheduleBulk = $objectManager->getObject(
            ScheduleBulk::class,
            [
                'bulkManagement' => $this->bulkManagement,
                'operationFactory' => $this->operationFactory,
                'identityService' => $this->identityService,
                'serializer' => $this->serializer,
                'groupRepository' => $this->groupRepository,
                'userContext' => $this->userContext,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $bulkId = 'bulk-001';
        $categoryIds = [1];
        $groupIds = [2];
        $userId = 3;
        $serializedData = 'operation serialized data';
        $this->identityService->expects($this->once())->method('generateId')->willReturn($bulkId);
        $operation = $this->getMockBuilder(OperationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializer->expects($this->once())->method('serialize')
            ->with(['category_id' => $categoryIds[0], 'group_ids' => $groupIds[0]])->willReturn($serializedData);
        $this->operationFactory->expects($this->once())->method('create')->with(
            [
                'data' => [
                    'bulk_uuid' => 'bulk-001',
                    'topic_name' => 'shared.catalog.category.permissions.updated',
                    'serialized_data' => $serializedData,
                    'status' => OperationInterface::STATUS_TYPE_OPEN,
                ],
            ]
        )->willReturn($operation);
        $this->bulkManagement->expects($this->once())->method('scheduleBulk')
            ->with($bulkId, [$operation], __('Assign Categories to Shared Catalog'), $userId)
            ->willReturn(true);
        $this->scheduleBulk->execute($categoryIds, $groupIds, $userId);
    }

    /**
     * Test for execute method with exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Something went wrong while scheduling operations.');
        $bulkId = 'bulk-001';
        $categoryIds = [1];
        $groupIds = [2];
        $userId = 3;
        $serializedData = 'operation serialized data';
        $this->identityService->expects($this->once())->method('generateId')->willReturn($bulkId);
        $operation = $this->getMockBuilder(OperationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializer->expects($this->once())->method('serialize')
            ->with(['category_id' => $categoryIds[0], 'group_ids' => $groupIds[0]])->willReturn($serializedData);
        $this->operationFactory->expects($this->once())->method('create')->with(
            [
                'data' => [
                    'bulk_uuid' => 'bulk-001',
                    'topic_name' => 'shared.catalog.category.permissions.updated',
                    'serialized_data' => $serializedData,
                    'status' => OperationInterface::STATUS_TYPE_OPEN,
                ],
            ]
        )->willReturn($operation);
        $this->bulkManagement->expects($this->once())->method('scheduleBulk')
            ->with($bulkId, [$operation], __('Assign Categories to Shared Catalog'), $userId)
            ->willReturn(false);
        $this->scheduleBulk->execute($categoryIds, $groupIds, $userId);
    }
}
