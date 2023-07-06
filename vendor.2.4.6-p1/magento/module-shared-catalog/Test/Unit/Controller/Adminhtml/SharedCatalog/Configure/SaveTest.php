<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog\Configure;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\View\Result\RedirectFactory as BackendRedirectFactory;
use Magento\Customer\Api\Data\GroupExtension;
use Magento\Customer\Api\Data\GroupExtensionInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\PriceManagementInterface;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure\Save;
use Magento\SharedCatalog\Model\Configure\Category;
use Magento\SharedCatalog\Model\Form\Storage\DiffProcessor;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for save configuration controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SaveTest extends TestCase
{
    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Category|MockObject
     */
    private $configureCategory;

    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var Wizard|MockObject
     */
    private $wizardStorage;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ScheduleBulk|MockObject
     */
    private $scheduleBulk;

    /**
     * @var PriceManagementInterface|MockObject
     */
    private $priceSharedCatalogManagement;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Save
     */
    private $save;

    /**
     * @var DiffProcessor|MockObject
     */
    private $diffProcessor;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var GroupExtensionInterfaceFactory
     */
    private $groupExtensionInterfaceFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GroupInterface|MockObject
     */
    private $group;

    /**
     * @var GroupExtension|MockObject
     */
    private $groupExtension;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory = $this->getMockBuilder(BackendRedirectFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();
        $this->wizardStorageFactory = $this->getMockBuilder(WizardFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->wizardStorage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scheduleBulk = $this->getMockBuilder(ScheduleBulk::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureCategory = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory = $this->getMockBuilder(WizardFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scheduleBulk = $this->getMockBuilder(
            ScheduleBulk::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceSharedCatalogManagement = $this->getMockBuilder(
            PriceManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory = $this->getMockBuilder(BackendRedirectFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->diffProcessor = $this->getMockBuilder(DiffProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDiff'])
            ->getMockForAbstractClass();

        $this->groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getById', 'save'])
            ->getMockForAbstractClass();

        $this->groupExtensionInterfaceFactory = $this->getMockBuilder(GroupExtensionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsites', 'getStore', 'getGroup'])
            ->getMockForAbstractClass();

        $this->group = $this->getMockBuilder(GroupInterface::class)
            ->getMockForAbstractClass();

        $this->groupExtension = $this->getMockBuilder(GroupExtension::class)
            ->addMethods(['setExcludeWebsiteIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->save = $objectManager->getObject(
            Save::class,
            [
                'configureCategory' => $this->configureCategory,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'logger' => $this->logger,
                'scheduleBulk' => $this->scheduleBulk,
                'priceSharedCatalogManagement' => $this->priceSharedCatalogManagement,
                'userContextInterface' => $userContext,
                'diffProcessor' => $this->diffProcessor,
                '_request' => $this->request,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'messageManager' => $this->messageManager,
                'groupRepository' => $this->groupRepository,
                'groupExtensionInterfaceFactory' => $this->groupExtensionInterfaceFactory,
                'storeManager' => $this->storeManager
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $changes = [
            'pricesChanged' => false,
            'categoriesChanged' => false,
            'productsChanged' => true
        ];

        $this->prepareExecuteBody();
        $this->diffProcessor->expects(self::once())
            ->method('getDiff')
            ->willReturn($changes);
        $message = __(
            'The selected changes have been applied to the shared catalog.'
        );

        $this->messageManager
            ->expects(self::once())
            ->method('addSuccessMessage')
            ->with($message)->willReturnSelf();
        $result = $this->prepareExecuteResultMock();

        self::assertEquals($result, $this->save->execute());
    }

    /**
     * Test for method execute with success message about changed categories.
     *
     * @return void
     */
    public function testExecuteWithMessageAboutChangedCategories(): void
    {
        $changes = [
            'pricesChanged' => false,
            'categoriesChanged' => true
        ];
        $storeId = 0;

        $this->prepareExecuteBody($storeId);
        $this->diffProcessor->expects(self::once())
            ->method('getDiff')
            ->willReturn($changes);
        $message = __(
            'The selected items are being processed. You can continue to work in the meantime.'
        );

        $this->messageManager->expects(self::once())
            ->method('addSuccessMessage')
            ->with($message)
            ->willReturnSelf();

        $result = $this->prepareExecuteResultMock();

        self::assertEquals($result, $this->save->execute());
    }

    /**
     * Prepare Result mock for execute() method test.
     *
     * @return MockObject
     */
    private function prepareExecuteResultMock(): MockObject
    {
        $result = $this->getMockBuilder(ResultRedirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects(self::once())->method('create')->willReturn($result);
        $result->expects(self::once())
            ->method('setPath')->with('shared_catalog/sharedCatalog/index')->willReturnSelf();

        return $result;
    }

    /**
     * Prepare body for execute() method test.
     *
     * @param int $storeId
     *
     * @return void
     */
    private function prepareExecuteBody($storeId = 0): void
    {
        $configurationKey = 'configuration_key';
        $sharedCatalogId = 1;
        $productSkus = ['sku1', 'sku2'];
        $tierPrices = [3 => 10, 4 => 15, 5 => 20];

        $this->request
            ->method('getParam')
            ->withConsecutive(
                ['catalog_id'],
                ['configure_key'],
                ['store_id']
            )
            ->willReturnOnConsecutiveCalls(
                $sharedCatalogId,
                $configurationKey,
                $storeId
            );

        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->wizardStorageFactory
            ->expects(self::once())
            ->method('create')
            ->with(['key' => $configurationKey])
            ->willReturn($storage);

        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configureCategory
            ->expects(self::once())
            ->method('saveConfiguredCategories')
            ->with($storage, $sharedCatalogId, $storeId)
            ->willReturn($sharedCatalog);

        $sharedCatalog->expects(self::once())
            ->method('getCustomerGroupId')
            ->willReturn(1);

        $storage
            ->expects(self::once())
            ->method('getUnassignedProductSkus')
            ->willReturn($productSkus);

        $this->priceSharedCatalogManagement
            ->expects(self::once())
            ->method('deleteProductTierPrices')
            ->with($sharedCatalog, $productSkus)
            ->willReturnSelf();

        $storage->expects(self::once())
            ->method('getTierPrices')
            ->willReturn($tierPrices);

        $this->scheduleBulk->expects(self::once())
            ->method('execute')
            ->with($sharedCatalog, $tierPrices);
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $configurationKey = 'configuration_key';
        $sharedCatalogId = 1;
        $storeId = 2;
        $exception = new \Exception('Exception Message');

        $this->request
            ->method('getParam')
            ->withConsecutive(
                ['catalog_id'],
                ['configure_key'],
                ['store_id']
            )
            ->willReturnOnConsecutiveCalls(
                $sharedCatalogId,
                $configurationKey,
                $storeId
            );

        $this->wizardStorageFactory
            ->expects(self::once())
            ->method('create')
            ->with(['key' => $configurationKey])
            ->willReturn($this->wizardStorage);

        $this->configureCategory
            ->expects(self::once())
            ->method('saveConfiguredCategories')
            ->with($this->wizardStorage, $sharedCatalogId, $storeId)
            ->willThrowException($exception);

        $this->logger->expects(self::once())->method('critical')->with($exception);

        $this->messageManager
            ->expects(self::once())
            ->method('addErrorMessage')
            ->with($exception->getMessage())
            ->willReturnSelf();

        $result = $this->getMockBuilder(ResultRedirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectFactory->expects(self::once())->method('create')->willReturn($result);

        $result->expects(self::once())
            ->method('setPath')
            ->with('shared_catalog/sharedCatalog/index')
            ->willReturnSelf();

        $this->assertEquals($result, $this->save->execute());
    }

    /**
     * Test for method execute with InvalidArgumentException.
     *
     * @return void
     */
    public function testExecuteWithInvalidArgumentException(): void
    {
        $configurationKey = 'configuration_key';
        $sharedCatalogId = 1;
        $storeId = 2;
        $exception = new \InvalidArgumentException('Exception Message');

        $this->request->method('getParam')
            ->withConsecutive(['catalog_id'], ['configure_key'], ['store_id'])
            ->willReturnOnConsecutiveCalls($sharedCatalogId, $configurationKey, $storeId);

        $this->wizardStorageFactory
            ->expects(self::once())
            ->method('create')
            ->with(['key' => $configurationKey])
            ->willReturn($this->wizardStorage);

        $this->configureCategory
            ->expects(self::once())
            ->method('saveConfiguredCategories')
            ->with($this->wizardStorage, $sharedCatalogId, $storeId)
            ->willThrowException($exception);

        $this->logger->expects($this->once())->method('critical')->with($exception);
        $result = $this->getMockBuilder(ResultRedirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects(self::once())->method('create')->willReturn($result);
        $result->expects(self::once())
            ->method('setPath')
            ->with('shared_catalog/sharedCatalog/index')
            ->willReturnSelf();

        $this->assertEquals($result, $this->save->execute());
    }

    /**
     * Test for method execute with exclude websites.
     *
     * @return void
     */
    public function testExecuteWithExcludedWebsites(): void
    {
        $changes = [
            'pricesChanged' => false,
            'categoriesChanged' => true
        ];
        $storeId = 2;

        $this->prepareExecuteBody($storeId);
        $this->diffProcessor->expects(self::once())
            ->method('getDiff')
            ->willReturn($changes);
        $message = __(
            'The selected items are being processed. You can continue to work in the meantime.'
        );

        $this->messageManager->expects(self::once())
            ->method('addSuccessMessage')
            ->with($message)
            ->willReturnSelf();

        $result = $this->prepareExecuteResultMock();

        $websiteMock = $this->getMockBuilder(Website::class)
            ->onlyMethods(['getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $websiteMock->expects(self::once())->method('getId')->willReturn(1);
        $secondWebsiteMock = $this->getMockBuilder(Website::class)
            ->onlyMethods(['getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $secondWebsiteMock->expects(self::once())->method('getId')->willReturn(2);

        $group = $this->getMockBuilder(GroupInterface::class)
            ->addMethods(['getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $group->expects(self::once())
            ->method('getWebsiteId')
            ->willReturn(2);

        $this->storeManager->expects(self::once())
            ->method('getWebsites')
            ->willReturn([$websiteMock, $secondWebsiteMock]);

        $this->storeManager->expects(self::once())
            ->method('getGroup')
            ->with($storeId)
            ->willReturn($group);

        $this->groupRepository->expects(self::once())
            ->method('getById')
            ->with(1)
            ->willReturn($this->group);

        $this->groupExtensionInterfaceFactory->expects(self::once())
            ->method('create')
            ->willReturn($this->groupExtension);

        $this->groupExtension->expects(self::once())
            ->method('setExcludeWebsiteIds')
            ->with([1])
            ->willReturnSelf();
        $this->group->expects(self::once())
            ->method('setExtensionAttributes')
            ->with($this->groupExtension)
            ->willReturnSelf();

        $this->groupRepository->expects(self::once())
            ->method('save')
            ->willReturn($this->group);

        self::assertEquals($result, $this->save->execute());
    }
}
