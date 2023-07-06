<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Quote\Api;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Model\Customer\PermissionInterface;
use Magento\Company\Plugin\Quote\Api\CartManagementInterfacePlugin;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartManagementInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartManagementInterfacePluginTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var PermissionInterface|MockObject
     */
    private $permission;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CartManagementInterfacePlugin
     */
    private $plugin;

    /**
     * Set up.
     *
     * @return void
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
        $this->permission = $this
            ->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCheckoutAllowed'])
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            CartManagementInterfacePlugin::class,
            [
                'customerRepository' => $this->customerRepository,
                'userContext' => $this->userContext,
                'permission' => $this->permission
            ]
        );
    }

    /**
     * Test beforePlaceOrder method.
     *
     * @return void
     */
    public function testBeforePlaceOrder()
    {
        $userId = 1;
        $cartId = 5;
        $customer = $this
            ->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $subject = $this
            ->getMockBuilder(CartManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $this->permission->expects($this->once())->method('isCheckoutAllowed')->with($customer)->willReturn(true);
        $this->assertEquals([$cartId, null], $this->plugin->beforePlaceOrder($subject, $cartId));
    }

    /**
     * Test beforePlaceOrder method throws LocalizedException.
     *
     * @return void
     */
    public function testBeforePlaceOrderWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('This customer company account is blocked and customer cannot place orders.');
        $userId = 1;
        $cartId = 5;
        $customer = $this
            ->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $subject = $this
            ->getMockBuilder(CartManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $this->permission->expects($this->once())->method('isCheckoutAllowed')->with($customer)->willReturn(false);
        $this->assertEquals([$cartId, null], $this->plugin->beforePlaceOrder($subject, $cartId));
    }
}
