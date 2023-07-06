<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\SaveHandler\SharedCatalog;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog;
use Magento\SharedCatalog\Model\SaveHandler\SharedCatalog\Save;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SharedCatalog/Model/SaveHandler/SharedCatalog/Save.php.
 */
class SaveTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Save
     */
    private $save;

    /**
     * @var SharedCatalog|MockObject
     */
    private $sharedCatalogResourceMock;

    /**
     * @var CustomerGroupManagement|MockObject
     */
    private $customerGroupManagementMock;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->sharedCatalogResourceMock = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerGroupManagementMock = $this->getMockBuilder(CustomerGroupManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContextMock = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->save = $this->objectManagerHelper->getObject(
            Save::class,
            [
                'sharedCatalogResource' => $this->sharedCatalogResourceMock,
                'customerGroupManagement' => $this->customerGroupManagementMock,
                'userContext' => $this->userContextMock
            ]
        );
    }

    /**
     * Test for execute() method.
     *
     * @return void
     */
    public function testExecute()
    {
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogResourceMock->expects($this->once())->method('save')->with($sharedCatalog);

        $this->save->execute($sharedCatalog);
    }

    /**
     * Test for prepare() method if user type is Admin.
     *
     * @return void
     */
    public function testPrepareIfUserTypeAdmin()
    {
        $userId = 1;

        $sharedCatalog = $this->prepareSharedCatalogMockForPrepareTest();
        $this->userContextMock->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $this->userContextMock->expects($this->once())->method('getUserId')->willReturn($userId);
        $sharedCatalog->expects($this->once())->method('setCreatedBy')->with($userId)->willReturnSelf();

        $this->save->prepare($sharedCatalog);
    }

    /**
     * Test for prepare() method if user type is not Admin.
     *
     * @return void
     */
    public function testPrepareIfUserTypeNotAdmin()
    {
        $userId = null;

        $sharedCatalog = $this->prepareSharedCatalogMockForPrepareTest();
        $this->userContextMock->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->userContextMock->expects($this->never())->method('getUserId');
        $sharedCatalog->expects($this->once())->method('setCreatedBy')->with($userId)->willReturnSelf();

        $this->save->prepare($sharedCatalog);
    }

    /**
     * Prepare shared catalog mock for prepare() tests.
     *
     * @return MockObject
     */
    private function prepareSharedCatalogMockForPrepareTest()
    {
        $customerGroupId = 1;

        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn(null);
        $customerGroup = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerGroup->expects($this->once())->method('getId')->willReturn($customerGroupId);
        $this->customerGroupManagementMock->expects($this->once())->method('createCustomerGroupForSharedCatalog')
            ->with($sharedCatalog)->willReturn($customerGroup);
        $sharedCatalog->expects($this->once())->method('setCustomerGroupId')->with($customerGroupId)->willReturnSelf();
        $sharedCatalog->expects($this->atLeastOnce())->method('getType')
            ->willReturn(null);
        $sharedCatalog->expects($this->atLeastOnce())->method('setType')
            ->with(SharedCatalogInterface::TYPE_CUSTOM)
            ->willReturnSelf();

        return $sharedCatalog;
    }
}
