<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\SaveHandler;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\SharedCatalog\Model\SaveHandler\SharedCatalog as SaveHandlerSharedCatalog;
use Magento\SharedCatalog\Model\SaveHandler\SharedCatalog\Save as SharedCatalogSave;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\SharedCatalog\Model\SharedCatalogValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SharedCatalog save handler.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SaveHandlerSharedCatalog
     */
    private $sharedCatalogSaveHandler;

    /**
     * @var CustomerGroupManagement|MockObject
     */
    private $customerGroupManagementMock;

    /**
     * @var CatalogPermissionManagement|MockObject
     */
    private $catalogPermissionManagementMock;

    /**
     * @var SharedCatalogValidator|MockObject
     */
    private $validatorMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SharedCatalogSave|MockObject
     */
    private $saveMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerGroupManagementMock = $this->createMock(CustomerGroupManagement::class);
        $this->catalogPermissionManagementMock = $this->createMock(CatalogPermissionManagement::class);
        $this->validatorMock = $this->createMock(SharedCatalogValidator::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->saveMock = $this->createMock(SharedCatalogSave::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->sharedCatalogSaveHandler = $this->objectManagerHelper->getObject(
            SaveHandlerSharedCatalog::class,
            [
                'customerGroupManagement' => $this->customerGroupManagementMock,
                'catalogPermissionManagement' => $this->catalogPermissionManagementMock,
                'validator' => $this->validatorMock,
                'logger' => $this->loggerMock,
                'save' => $this->saveMock
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
        $groupId = 5;

        $sharedCatalog = $this->createMock(SharedCatalog::class);
        $originalSharedCatalog = $this->createMock(SharedCatalog::class);
        $sharedCatalog->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($groupId);
        $this->customerGroupManagementMock->expects($this->once())->method('updateCustomerGroup');
        $originalSharedCatalog->expects($this->once())->method('getId')->willReturn(null);
        $this->catalogPermissionManagementMock->expects($this->once())
            ->method('setDenyPermissionsForCustomerGroup')->with($groupId);
        $this->saveMock->expects($this->once())->method('prepare')->with($sharedCatalog);
        $this->saveMock->expects($this->once())->method('execute')->with($sharedCatalog);
        $this->validatorMock->expects($this->once())->method('isDirectChangeToCustom')->with($sharedCatalog);

        $this->assertInstanceOf(
            SharedCatalogInterface::class,
            $this->sharedCatalogSaveHandler->execute($sharedCatalog, $originalSharedCatalog)
        );
    }

    /**
     * Test for execute() method with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not save shared catalog.');
        $exception = new LocalizedException(__('exception message'));
        $sharedCatalog = $this->prepareExecuteWithExceptions($exception);
        $this->loggerMock->expects($this->once())->method('critical')->with('exception message');

        $this->sharedCatalogSaveHandler->execute($sharedCatalog, $sharedCatalog);
    }

    /**
     * Test for execute() method with CouldNotSaveException.
     *
     * @return void
     */
    public function testExecuteWithCouldNotSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('exception message');
        $exception = new CouldNotSaveException(__('exception message'));
        $sharedCatalog = $this->prepareExecuteWithExceptions($exception);

        $this->sharedCatalogSaveHandler->execute($sharedCatalog, $sharedCatalog);
    }

    /**
     * Prepare mocks for execute() test with Exceptions.
     *
     * @param \Exception $exception
     * @return MockObject
     */
    private function prepareExecuteWithExceptions(\Exception $exception)
    {
        $sharedCatalog = $this->createMock(SharedCatalog::class);
        $this->customerGroupManagementMock->expects($this->never())->method('createCustomerGroupForSharedCatalog');
        $this->saveMock->expects($this->once())->method('prepare')->with($sharedCatalog)
            ->willThrowException($exception);
        $this->validatorMock->expects($this->once())->method('isDirectChangeToCustom')->with($sharedCatalog);

        return $sharedCatalog;
    }
}
