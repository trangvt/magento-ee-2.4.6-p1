<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\SharedCatalog\Model\SaveHandler;
use Magento\SharedCatalog\Model\SaveHandler\DuplicatedPublicSharedCatalog;
use Magento\SharedCatalog\Model\SaveHandler\SharedCatalog;
use Magento\SharedCatalog\Model\SharedCatalogValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * SaveHandler unit test.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveHandlerTest extends TestCase
{
    /**
     * @var CustomerGroupManagement|MockObject
     */
    private $customerGroupManagement;

    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $sharedCatalogManagement;

    /**
     * @var SharedCatalogValidator|MockObject
     */
    private $validator;

    /**
     * @var DuplicatedPublicSharedCatalog|MockObject
     */
    private $duplicatedCatalogSaveHandler;

    /**
     * @var SharedCatalog|MockObject
     */
    private $catalogSaveHandler;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var SaveHandler
     */
    private $saveHandler;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerGroupManagement = $this
            ->getMockBuilder(CustomerGroupManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogManagement = $this
            ->getMockBuilder(SharedCatalogManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validator = $this
            ->getMockBuilder(SharedCatalogValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->duplicatedCatalogSaveHandler = $this->getMockBuilder(DuplicatedPublicSharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogSaveHandler = $this
            ->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->saveHandler = $objectManager->getObject(
            SaveHandler::class,
            [
                'customerGroupManagement' => $this->customerGroupManagement,
                'sharedCatalogManagement' => $this->sharedCatalogManagement,
                'validator' => $this->validator,
                'duplicatedPublicCatalogSaveHandler' => $this->duplicatedCatalogSaveHandler,
                'catalogSaveHandler' => $this->catalogSaveHandler,
                'logger' => $this->logger
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
        $this->validator->expects($this->once())->method('validate')->with($sharedCatalog);
        $this->validator->expects($this->once())->method('isCatalogPublicTypeDuplicated')->with($sharedCatalog)
            ->willReturn(false);
        $this->catalogSaveHandler->expects($this->once())->method('execute')
            ->with($sharedCatalog, $sharedCatalog)->willReturn($sharedCatalog);
        $this->duplicatedCatalogSaveHandler->expects($this->never())->method('execute');

        $this->assertInstanceOf(
            SharedCatalogInterface::class,
            $this->saveHandler->execute($sharedCatalog)
        );
    }

    /**
     * Test for execute() method if public catalog is duplicated.
     *
     * @return void
     */
    public function testExecuteIfPublicCatalogDuplicated()
    {
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $publicCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator->expects($this->once())->method('validate')->with($sharedCatalog);
        $this->validator->expects($this->once())->method('isCatalogPublicTypeDuplicated')->with($sharedCatalog)
            ->willReturn(true);
        $this->sharedCatalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $this->duplicatedCatalogSaveHandler->expects($this->once())->method('execute')
            ->with($sharedCatalog, $publicCatalog)->willReturn($sharedCatalog);
        $this->catalogSaveHandler->expects($this->never())->method('execute');

        $this->assertInstanceOf(
            SharedCatalogInterface::class,
            $this->saveHandler->execute($sharedCatalog)
        );
    }

    /**
     * Test execute with \Exception exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not save shared catalog.');
        $exception = new \Exception('exception message');
        $sharedCatalog = $this->prepareExecuteWithExceptions($exception);
        $this->logger->expects($this->once())->method('critical')->with('exception message');
        $sharedCatalog->expects($this->once())->method('setCustomerGroupId')->with(null);

        $this->saveHandler->execute($sharedCatalog);
    }

    /**
     * Test execute with CouldNotSaveException exception.
     *
     * @return void
     */
    public function testExecuteWithCouldNotSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('exception message');
        $exception = new CouldNotSaveException(__('exception message'));
        $sharedCatalog = $this->prepareExecuteWithExceptions($exception);
        $sharedCatalog->expects($this->once())->method('setCustomerGroupId')->with(null);

        $this->saveHandler->execute($sharedCatalog);
    }

    /**
     * Prepare mocks for execute() test with Exceptions.
     *
     * @param \Exception $exception
     * @return MockObject
     */
    private function prepareExecuteWithExceptions(\Exception $exception)
    {
        $sharedCatalog = $this->getMockBuilder(\Magento\SharedCatalog\Model\SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator->expects($this->once())->method('validate')->with($sharedCatalog);
        $this->validator->expects($this->once())->method('isCatalogPublicTypeDuplicated')->with($sharedCatalog)
            ->willReturn(false);
        $this->catalogSaveHandler->expects($this->once())->method('execute')
            ->with($sharedCatalog, $sharedCatalog)->willThrowException($exception);
        $sharedCatalog->expects($this->exactly(2))->method('getCustomerGroupId')
            ->willReturnOnConsecutiveCalls(null, 1);
        $this->customerGroupManagement->expects($this->once())->method('deleteCustomerGroupById')->with($sharedCatalog);

        return $sharedCatalog;
    }

    /**
     * Test execute with CouldNotSaveException exception on roollback action.
     *
     * @return void
     */
    public function testExecuteWithExceptionOnRollback()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not save shared catalog.');
        $exception = new CouldNotSaveException(__('exception message'));
        $sharedCatalog = $this->prepareExecuteWithExceptions($exception);
        $this->customerGroupManagement->expects($this->once())->method('deleteCustomerGroupById')->with($sharedCatalog)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with('exception message');
        $sharedCatalog->expects($this->never())->method('setCustomerGroupId');

        $this->saveHandler->execute($sharedCatalog);
    }
}
