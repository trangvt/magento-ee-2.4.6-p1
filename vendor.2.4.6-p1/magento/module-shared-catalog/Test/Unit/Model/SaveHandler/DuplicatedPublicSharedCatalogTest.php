<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\SaveHandler;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\CompanyManagementInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\SharedCatalog\Model\SaveHandler\DuplicatedPublicSharedCatalog;
use Magento\SharedCatalog\Model\SaveHandler\SharedCatalog\Save;
use Magento\SharedCatalog\Model\SharedCatalog;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DuplicatedPublicSharedCatalog save handler.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DuplicatedPublicSharedCatalogTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DuplicatedPublicSharedCatalog
     */
    private $duplicatedPublicSharedCatalog;

    /**
     * @var ProductItemManagementInterface|MockObject
     */
    private $sharedCatalogProductItemManagementMock;

    /**
     * @var CustomerGroupManagement|MockObject
     */
    private $customerGroupManagementMock;

    /**
     * @var CatalogPermissionManagement|MockObject
     */
    private $catalogPermissionManagementMock;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $sharedCatalogCompanyManagementMock;

    /**
     * @var CategoryManagementInterface|MockObject
     */
    private $categoryManagementMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Save|MockObject
     */
    private $saveMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->sharedCatalogProductItemManagementMock = $this->getMockBuilder(ProductItemManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerGroupManagementMock = $this->getMockBuilder(CustomerGroupManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogPermissionManagementMock = $this->getMockBuilder(CatalogPermissionManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogCompanyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->categoryManagementMock = $this->getMockBuilder(CategoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->saveMock = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->duplicatedPublicSharedCatalog = $this->objectManagerHelper->getObject(
            DuplicatedPublicSharedCatalog::class,
            [
                'sharedCatalogProductItemManagement' => $this->sharedCatalogProductItemManagementMock,
                'customerGroupManagement' => $this->customerGroupManagementMock,
                'catalogPermissionManagement' => $this->catalogPermissionManagementMock,
                'sharedCatalogCompanyManagement' => $this->sharedCatalogCompanyManagementMock,
                'categoryManagement' => $this->categoryManagementMock,
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
        $publicCatalogId = 1;
        $sharedCatalogId = 1;
        $publicCatalogCategoryIds = [1];
        $sharedCatalogCategoryIds = [1];

        $sharedCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $publicCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $publicCatalog->expects($this->atLeastOnce())->method('setType')
            ->with(SharedCatalogInterface::TYPE_CUSTOM)->willReturnSelf();
        $this->sharedCatalogProductItemManagementMock->expects($this->once())->method('deletePricesForPublicCatalog');
        $this->customerGroupManagementMock->expects($this->once())->method('updateCustomerGroup')->with($sharedCatalog);
        $this->customerGroupManagementMock->expects($this->once())->method('setDefaultCustomerGroup')
            ->with($sharedCatalog);
        $this->sharedCatalogProductItemManagementMock->expects($this->once())->method('addPricesForPublicCatalog');
        $this->sharedCatalogCompanyManagementMock->expects($this->once())->method('unassignAllCompanies')
            ->with($publicCatalogId);
        $publicCatalog->expects($this->atLeastOnce())->method('getId')->willReturn($publicCatalogId);
        $this->catalogPermissionManagementMock->expects($this->once())->method('setDenyPermissions')
            ->with(
                $publicCatalogCategoryIds,
                [GroupInterface::NOT_LOGGED_IN_ID]
            );
        $sharedCatalog->expects($this->atLeastOnce())->method('getId')->willReturn($sharedCatalogId);
        $this->categoryManagementMock->expects($this->exactly(2))->method('getCategories')
            ->withConsecutive([$publicCatalogId], [$sharedCatalogId])
            ->willReturnOnConsecutiveCalls($publicCatalogCategoryIds, $sharedCatalogCategoryIds);
        $this->catalogPermissionManagementMock->expects($this->once())->method('setAllowPermissions')
            ->with(
                $sharedCatalogCategoryIds,
                [GroupInterface::NOT_LOGGED_IN_ID]
            );

        $this->assertInstanceOf(
            SharedCatalogInterface::class,
            $this->duplicatedPublicSharedCatalog->execute($sharedCatalog, $publicCatalog)
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
        $sharedCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exception = new LocalizedException(__('exception message'));
        $publicCatalog = $this->prepareExecuteWithExceptions($exception);
        $this->loggerMock->expects($this->once())->method('critical')->with('exception message');

        $this->duplicatedPublicSharedCatalog->execute($sharedCatalog, $publicCatalog);
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
        $sharedCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exception = new CouldNotSaveException(__('exception message'));
        $publicCatalog = $this->prepareExecuteWithExceptions($exception);

        $this->duplicatedPublicSharedCatalog->execute($sharedCatalog, $publicCatalog);
    }

    /**
     * Prepare mocks for execute() test with Exceptions.
     *
     * @param \Exception $exception
     * @return MockObject
     */
    private function prepareExecuteWithExceptions(\Exception $exception)
    {
        $publicCatalog = $this->getMockBuilder(SharedCatalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $publicCatalog->expects($this->atLeastOnce())->method('setType')
            ->with(SharedCatalogInterface::TYPE_CUSTOM)->willReturnSelf();
        $this->sharedCatalogProductItemManagementMock->expects($this->once())->method('deletePricesForPublicCatalog');
        $this->saveMock->expects($this->once())->method('execute')->with($publicCatalog)
            ->willThrowException($exception);

        return $publicCatalog;
    }
}
