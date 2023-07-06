<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Customer\Model;

use Magento\Customer\Api\Data\CustomerInterface as CustomerData;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SharedCatalog\Plugin\Customer\Model\SessionPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for plugin Magento\SharedCatalog\Plugin\Customer\Model\SessionPlugin
 */
class SessionPluginTest extends TestCase
{
    /**
     * @var CustomerSession|MockObject
     */
    private $customerSessionMock;

    /**
     * @var SessionPlugin
     */
    protected $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)->disableOriginalConstructor()
            ->onlyMethods(['getCustomerData', 'setCustomerGroupId'])->getMock();

        $this->plugin = new SessionPlugin();
    }

    /**
     * @return void
     */
    public function testAfterGetCustomerGroupId(): void
    {
        $groupId = 1;
        $customerGroupId = 3;

        $customerDataMock = $this->getMockBuilder(CustomerData::class)->disableOriginalConstructor()
            ->onlyMethods(['getGroupId'])->getMockForAbstractClass();

        $this->customerSessionMock->expects($this->exactly(3))->method('getCustomerData')
            ->willReturn($customerDataMock);
        $customerDataMock->expects($this->exactly(2))->method('getGroupId')
            ->willReturn($customerGroupId);
        $this->customerSessionMock->expects($this->once())->method('setCustomerGroupId')
            ->willReturn($customerGroupId);

        $this->assertEquals(
            $customerGroupId,
            $this->plugin->afterGetCustomerGroupId($this->customerSessionMock, $groupId)
        );
    }

    /**
     * @return void
     */
    public function testAfterGetCustomerGroupIdForNoCustomerData(): void
    {
        $groupId = 1;
        $this->customerSessionMock->expects($this->atLeastOnce())->method('getCustomerData')->willReturn([]);

        $this->assertEquals($groupId, $this->plugin->afterGetCustomerGroupId($this->customerSessionMock, $groupId));
    }

    /**
     * @return void
     */
    public function testAfterGetCustomerGroupIdForNoSuchEntityException(): void
    {
        $groupId = 1;
        $this->customerSessionMock->expects($this->once())->method('getCustomerData')
            ->willThrowException(new NoSuchEntityException());

        $this->assertEquals($groupId, $this->plugin->afterGetCustomerGroupId($this->customerSessionMock, $groupId));
    }
}
