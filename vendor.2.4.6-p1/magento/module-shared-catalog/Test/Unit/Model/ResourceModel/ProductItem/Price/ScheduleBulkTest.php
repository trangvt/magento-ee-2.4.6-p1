<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\ProductItem\Price;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for ProductItem\Price\ScheduleBulk resource model.
 */
class ScheduleBulkTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ScheduleBulk
     */
    private $scheduleBulk;

    /**
     * @var BulkManagementInterface|MockObject
     */
    private $bulkManagementMock;

    /**
     * @var OperationInterfaceFactory|MockObject
     */
    private $operationFactoryMock;

    /**
     * @var IdentityGeneratorInterface|MockObject
     */
    private $identityServiceMock;

    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private $groupRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->bulkManagementMock = $this->getMockForAbstractClass(BulkManagementInterface::class);
        $this->operationFactoryMock = $this->getMockBuilder(OperationInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->identityServiceMock = $this->getMockForAbstractClass(IdentityGeneratorInterface::class);
        $this->groupRepository = $this->getMockForAbstractClass(GroupRepositoryInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->scheduleBulk = $this->objectManagerHelper->getObject(
            ScheduleBulk::class,
            [
                'bulkManagement' => $this->bulkManagementMock,
                'operationFactory' => $this->operationFactoryMock,
                'identityService' => $this->identityServiceMock,
                'groupRepository' => $this->groupRepository
            ]
        );
    }

    /**
     * Test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $userId = 1664;
        $price = [['is_changed' => true]];
        $prices = ['sku_1' => $price];
        $bulkUuid = '83900a60-57c9-11e6-8b77-86f30ca893d3';
        $bulkDescription = __('Assign custom prices to selected products');

        $sharedCatalogMock = $this->createSharedCatalogMock(5, SharedCatalogInterface::TYPE_CUSTOM);
        $this->identityServiceMock->expects($this->once())
            ->method('generateId')
            ->willReturn($bulkUuid);
        $operationMocks = $this->getMockForOperation(1);
        $this->bulkManagementMock
            ->expects($this->once())
            ->method('scheduleBulk')
            ->with($bulkUuid, $operationMocks, $bulkDescription, $userId)
            ->willReturn(true);

        $this->scheduleBulk->execute($sharedCatalogMock, $prices, $userId);
    }

    /**
     * @return void
     */
    public function testExecuteWithPublicSharedCatalog()
    {
        $userId = 1664;
        $price = [['is_changed' => true]];
        $prices = ['sku_1' => $price];
        $bulkUuid = '83900a60-57c9-11e6-8b77-86f30ca893d3';
        $bulkDescription = __('Assign custom prices to selected products');

        $sharedCatalogMock = $this->createSharedCatalogMock(1, SharedCatalogInterface::TYPE_PUBLIC);
        $this->identityServiceMock->expects($this->once())
            ->method('generateId')
            ->willReturn($bulkUuid);
        $operationMocks = $this->getMockForOperation(2);
        $this->bulkManagementMock
            ->expects($this->once())
            ->method('scheduleBulk')
            ->with($bulkUuid, $operationMocks, $bulkDescription, $userId)
            ->willReturn(true);

        $this->scheduleBulk->execute($sharedCatalogMock, $prices, $userId);
    }

    /**
     * Test for execute() with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Something went wrong while processing the request.');
        $userId = 435;
        $price = [['is_changed' => true]];
        $prices = ['sku_1' => $price];
        $bulkUuid = '83900a60-57c9-11e6-8b77-86f30ca893d3';
        $bulkDescription = __('Assign custom prices to selected products');

        $sharedCatalogMock = $this->createSharedCatalogMock(5, SharedCatalogInterface::TYPE_CUSTOM);
        $this->identityServiceMock->expects($this->once())
            ->method('generateId')
            ->willReturn($bulkUuid);
        $operationMocks = $this->getMockForOperation(1);
        $this->bulkManagementMock
            ->expects($this->once())
            ->method('scheduleBulk')
            ->with($bulkUuid, $operationMocks, $bulkDescription, $userId)
            ->willReturn(false);

        $this->scheduleBulk->execute($sharedCatalogMock, $prices, $userId);
    }

    /**
     * Get mock for operation.
     *
     * @param int $count
     * @return OperationInterface[]|MockObject[]
     */
    private function getMockForOperation(int $count): array
    {
        $operationMocks = [];
        for ($i = 0; $i < $count; $i++) {
            $operationMocks[] = $this->getMockForAbstractClass(OperationInterface::class);
        }
        $this->operationFactoryMock->expects($this->exactly($count))
            ->method('create')
            ->willReturnOnConsecutiveCalls(...$operationMocks);

        return $operationMocks;
    }

    /**
     * @param int $sharedCatalogId
     * @param int $sharedCatalogType
     * @return SharedCatalogInterface|MockObject
     */
    private function createSharedCatalogMock(int $sharedCatalogId, int $sharedCatalogType): MockObject
    {
        $sharedCatalogMock = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $sharedCatalogMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($sharedCatalogId);
        $sharedCatalogMock->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn($sharedCatalogType);
        $sharedCatalogMock->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturn($sharedCatalogId);

        $customerGroup = $this->getMockForAbstractClass(GroupInterface::class);
        $customerGroup->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn('code' . $sharedCatalogId);
        $customerGroups = [$customerGroup];
        if (SharedCatalogInterface::TYPE_PUBLIC === $sharedCatalogType) {
            $customerGroup = $this->getMockForAbstractClass(GroupInterface::class);
            $customerGroup->expects($this->atLeastOnce())
                ->method('getCode')
                ->willReturn('NOT LOGGED IN');
            $customerGroups[] = $customerGroup;
        }

        $this->groupRepository->expects($this->exactly(count($customerGroups)))
            ->method('getById')
            ->willReturnOnConsecutiveCalls(...$customerGroups);

        return $sharedCatalogMock;
    }
}
