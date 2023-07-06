<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Checkout\Controller\Index;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Controller\Index\Index;
use Magento\Company\Model\CompanyUserPermission;
use Magento\Company\Model\Customer\PermissionInterface;
use Magento\Company\Plugin\Checkout\Controller\Index\IndexPlugin;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexPluginTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var PermissionInterface|MockObject
     */
    private $permission;

    /**
     * @var CompanyUserPermission|MockObject
     */
    private $companyUserPermission;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var Index|MockObject
     */
    private $controller;

    /**
     * @var IndexPlugin
     */
    private $plugin;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->userContext = $this->createMock(
            UserContextInterface::class
        );
        $this->customerRepository = $this
            ->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->storeManager = $this
            ->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->permission = $this
            ->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCheckoutAllowed'])
            ->getMockForAbstractClass();
        $this->resultRedirectFactory = $this->createPartialMock(
            RedirectFactory::class,
            ['create']
        );

        $this->companyUserPermission = $this->getMockBuilder(CompanyUserPermission::class)
            ->setMethods(['isCurrentUserCompanyUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->controller = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            IndexPlugin::class,
            [
                'customerRepository' => $this->customerRepository,
                'userContext' => $this->userContext,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'storeManager' => $this->storeManager,
                'permission' => $this->permission,
                'companyUserPermission' => $this->companyUserPermission
            ]
        );
    }

    /**
     * Test aroundExecute() method.
     *
     * @return void
     */
    public function testAroundExecute()
    {
        $closure = function () {
            return;
        };

        $this->userContext->expects($this->exactly(1))->method('getUserId')->willReturn(1);

        $this->customerRepository->expects($this->exactly(1))->method('getById')->with(1)->willReturn($this->customer);

        $isCheckoutAllowed = true;
        $this->permission->expects($this->once())->method('isCheckoutAllowed')->with($this->customer)
            ->willReturn($isCheckoutAllowed);

        $this->assertEquals($closure(), $this->plugin->aroundExecute($this->controller, $closure));
    }

    /**
     * Test aroundExecute() method when Redirect expected.
     *
     * @param bool $isCurrentUserCompanyUser
     * @param string|null $redirectPath
     * @dataProvider aroundExecuteWhenRedirectExpectedDataProvider
     * @return void
     */
    public function testAroundExecuteWhenRedirectExpected($isCurrentUserCompanyUser, $redirectPath)
    {
        $closure = function () {
            return;
        };

        $this->userContext->expects($this->exactly(1))->method('getUserId')->willReturn(1);

        $this->customerRepository->expects($this->exactly(1))->method('getById')->with(1)->willReturn($this->customer);

        $isCheckoutAllowed = false;
        $this->permission->expects($this->once())->method('isCheckoutAllowed')->with($this->customer)
            ->willReturn($isCheckoutAllowed);

        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resultRedirect->expects($this->exactly(1))->method('setPath')
            ->with($redirectPath)->willReturnSelf();

        $this->resultRedirectFactory->expects($this->exactly(1))->method('create')
            ->willReturn($resultRedirect);

        $this->companyUserPermission->expects($this->exactly(1))
            ->method('isCurrentUserCompanyUser')->willReturn($isCurrentUserCompanyUser);

        $this->assertEquals($resultRedirect, $this->plugin->aroundExecute($this->controller, $closure));
    }

    /**
     * Data provider for aroundExecute() method when Redirect expected.
     *
     * @return array
     */
    public function aroundExecuteWhenRedirectExpectedDataProvider()
    {
        return [
            [true, 'company/accessdenied'],
            [false,'noroute']
        ];
    }
}
