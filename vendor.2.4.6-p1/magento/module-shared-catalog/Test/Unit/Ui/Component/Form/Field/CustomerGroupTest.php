<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Form\Field;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\Config;
use Magento\SharedCatalog\Ui\Component\Form\Field\CustomerGroup;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CustomerGroup.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerGroupTest extends TestCase
{
    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $catalogManagement;

    /**
     * @var GroupManagementInterface|MockObject
     */
    private $groupManagement;

    /**
     * @var Config|MockObject
     */
    private $moduleConfig;

    /**
     * @var UiComponentInterface|MockObject
     */
    private $wrappedComponent;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CustomerGroup
     */
    private $groupField;

    /**
     * @var string
     */
    private $formElement = 'testElement';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->catalogManagement = $this->createMock(
            SharedCatalogManagementInterface::class
        );
        $this->groupManagement = $this->createMock(
            GroupManagementInterface::class
        );
        $this->moduleConfig = $this->createMock(Config::class);
        $processor = $this->createPartialMock(
            Processor::class,
            ['register', 'notify']
        );
        $context = $this->getMockForAbstractClass(
            ContextInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getProcessor']
        );
        $context->expects($this->atLeastOnce())->method('getProcessor')->willReturn($processor);
        $this->wrappedComponent = $this->getMockForAbstractClass(
            UiComponentInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setData', 'getContext']
        );
        $this->wrappedComponent->expects($this->once())->method('getContext')->willReturn($context);
        $uiComponentFactory =
            $this->createPartialMock(UiComponentFactory::class, ['create']);
        $uiComponentFactory->expects($this->once())->method('create')->willReturn($this->wrappedComponent);
        $data = ['config' => ['formElement' => $this->formElement]];
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->groupField = $objectManager->getObject(
            CustomerGroup::class,
            [
                'catalogManagement' => $this->catalogManagement,
                'groupManagement' => $this->groupManagement,
                'moduleConfig' => $this->moduleConfig,
                'uiComponentFactory' => $uiComponentFactory,
                'storeManager' => $this->storeManager,
                'context' => $context,
                'components' => [],
                'data' => $data,
            ]
        );
    }

    /**
     * Test prepare method.
     *
     * @return void
     */
    public function testPrepare()
    {
        $publicGroupId = 1;
        $publicCatalog = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->catalogManagement->expects($this->once())->method('getPublicCatalog')->willReturn($publicCatalog);
        $publicCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($publicGroupId);
        $this->groupManagement->expects($this->never())->method('getDefaultGroup');
        $this->wrappedComponent->expects($this->once())->method('setData')
            ->with(
                'config',
                [
                    'dataScope' => null,
                    'formElement' => $this->formElement,
                    'value' => $publicGroupId
                ]
            )->willReturnSelf();
        $this->groupField->prepare();
    }

    /**
     * Test prepare method with exception.
     *
     * @return void
     */
    public function testPrepareWithException()
    {
        $defaultGroupId = 1;
        $defaultGroup = $this->getMockForAbstractClass(GroupInterface::class);
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(true);
        $this->catalogManagement->expects($this->once())->method('getPublicCatalog')->willThrowException(
            new NoSuchEntityException()
        );
        $this->groupManagement->expects($this->once())->method('getDefaultGroup')->willReturn($defaultGroup);
        $defaultGroup->expects($this->once())->method('getId')->willReturn($defaultGroupId);
        $this->wrappedComponent->expects($this->once())->method('setData')
            ->with(
                'config',
                [
                    'dataScope' => null,
                    'formElement' => $this->formElement,
                    'value' => $defaultGroupId
                ]
            )->willReturnSelf();
        $this->groupField->prepare();
    }

    /**
     * Test prepare method with disabled module.
     *
     * @return void
     */
    public function testPrepareWithDisabledModule()
    {
        $defaultGroupId = 1;
        $defaultGroup = $this->getMockForAbstractClass(GroupInterface::class);
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->moduleConfig->expects($this->atLeastOnce())->method('isActive')->willReturn(false);
        $this->catalogManagement->expects($this->never())->method('getPublicCatalog');
        $this->groupManagement->expects($this->once())->method('getDefaultGroup')->willReturn($defaultGroup);
        $defaultGroup->expects($this->once())->method('getId')->willReturn($defaultGroupId);
        $this->wrappedComponent->expects($this->once())->method('setData')
            ->with(
                'config',
                [
                    'dataScope' => null,
                    'formElement' => $this->formElement,
                    'value' => $defaultGroupId,
                    'notice' => null,
                ]
            )->willReturnSelf();
        $this->groupField->prepare();
    }
}
