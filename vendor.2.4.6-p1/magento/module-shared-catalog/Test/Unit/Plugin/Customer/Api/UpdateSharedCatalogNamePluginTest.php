<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Customer\Api;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\SharedCatalogLocator;
use Magento\SharedCatalog\Plugin\Customer\Api\UpdateSharedCatalogNamePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for UpdateSharedCatalogNamePlugin.
 */
class UpdateSharedCatalogNamePluginTest extends TestCase
{
    /**
     * @var SharedCatalogLocator|MockObject
     */
    private $sharedCatalogLocator;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var UpdateSharedCatalogNamePlugin
     */
    private $updateSharedCatalogNamePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->sharedCatalogLocator = $this->getMockBuilder(SharedCatalogLocator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->updateSharedCatalogNamePlugin = $objectManagerHelper->getObject(
            UpdateSharedCatalogNamePlugin::class,
            [
                'sharedCatalogLocator' => $this->sharedCatalogLocator,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
            ]
        );
    }

    /**
     * Test for afterSave().
     *
     * @return void
     */
    public function testAfterSave()
    {
        $customerGroupId = 1;
        $customerGroupCode = 'Customer Group';
        $customerGroup = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerGroup->expects($this->atLeastOnce())->method('getId')->willReturn($customerGroupId);
        $customerGroup->expects($this->atLeastOnce())->method('getCode')->willReturn($customerGroupCode);
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())->method('getName')->willReturn('Shared Catalog');
        $sharedCatalog->expects($this->atLeastOnce())->method('setName')->with($customerGroupCode)->willReturnSelf();
        $this->sharedCatalogLocator->expects($this->atLeastOnce())->method('getSharedCatalogByCustomerGroup')
            ->with($customerGroupId)->willReturn($sharedCatalog);
        $this->sharedCatalogRepository->expects($this->atLeastOnce())->method('save')->with($sharedCatalog)
            ->willReturn($sharedCatalog);
        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertInstanceOf(
            GroupInterface::class,
            $this->updateSharedCatalogNamePlugin->afterSave($groupRepository, $customerGroup)
        );
    }

    /**
     * Test for afterSave() with NoSuchEntityException.
     *
     * @return void
     */
    public function testAfterSaveWithNoSuchEntityException()
    {
        $customerGroupId = 1;
        $customerGroup = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerGroup->expects($this->atLeastOnce())->method('getId')->willReturn($customerGroupId);
        $phrase = new Phrase('exception');
        $exception = new NoSuchEntityException($phrase);
        $this->sharedCatalogLocator->expects($this->atLeastOnce())->method('getSharedCatalogByCustomerGroup')
            ->with($customerGroupId)->willThrowException($exception);
        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->updateSharedCatalogNamePlugin->afterSave($groupRepository, $customerGroup);
    }
}
