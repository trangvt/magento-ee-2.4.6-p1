<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Customer\Api;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\SharedCatalogLocator;
use Magento\SharedCatalog\Plugin\Customer\Api\ValidateCustomerGroupDeletePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ValidateCustomerGroupDeletePlugin.
 */
class ValidateCustomerGroupDeletePluginTest extends TestCase
{
    /**
     * @var SharedCatalogLocator|MockObject
     */
    private $sharedCatalogLocator;

    /**
     * @var ValidateCustomerGroupDeletePlugin
     */
    private $validateCustomerGroupDeletePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->sharedCatalogLocator = $this
            ->getMockBuilder(SharedCatalogLocator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->validateCustomerGroupDeletePlugin = $objectManagerHelper->getObject(
            ValidateCustomerGroupDeletePlugin::class,
            [
                'sharedCatalogLocator' => $this->sharedCatalogLocator,
            ]
        );
    }

    /**
     * Test for beforeDeleteById().
     *
     * @return void
     */
    public function testBeforeDeleteById()
    {
        $customerGroupId = 1;
        $this->sharedCatalogLocator->expects($this->once())
            ->method('getSharedCatalogByCustomerGroup')
            ->with($customerGroupId)
            ->willThrowException(new NoSuchEntityException(__('Exception Message')));
        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertEquals(
            [$customerGroupId],
            $this->validateCustomerGroupDeletePlugin->beforeDeleteById($groupRepository, $customerGroupId)
        );
    }

    /**
     * Test for beforeDeleteById() with CouldNotDeleteException.
     *
     * @return void
     */
    public function testBeforeDeleteByIdWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->expectExceptionMessage('A shared catalog is linked to this customer group.');
        $customerGroupId = 1;
        $sharedCatalog = $this
            ->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogLocator->expects($this->once())
            ->method('getSharedCatalogByCustomerGroup')->with($customerGroupId)->willReturn($sharedCatalog);
        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->validateCustomerGroupDeletePlugin->beforeDeleteById($groupRepository, $customerGroupId);
    }
}
