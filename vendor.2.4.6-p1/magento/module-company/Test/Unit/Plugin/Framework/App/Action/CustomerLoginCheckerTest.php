<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Framework\App\Action;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Model\Customer\PermissionInterface;
use Magento\Company\Plugin\Framework\App\Action\CustomerLoginChecker;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerLoginCheckerTest extends TestCase
{
    /**
     * @var PermissionInterface|MockObject
     */
    private $permission;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerLoginChecker
     */
    private $customerLoginChecker;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->permission = $this
            ->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoginAllowed'])
            ->getMockForAbstractClass();
        $this->userContext = $this
            ->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserType', 'getUserId'])
            ->getMockForAbstractClass();
        $this->customerRepository = $this
            ->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);
        $this->customerLoginChecker = $objectManagerHelper->getObject(
            CustomerLoginChecker::class,
            [
                'userContext' => $this->userContext,
                'permission' => $this->permission,
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    /**
     * Test isLoginAllowed method.
     *
     * @return void
     */
    public function testIsLoginAllowed()
    {
        $customer = $this
            ->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(1);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $this->permission->expects($this->once())->method('isLoginAllowed')->willReturn(false);
        $this->assertTrue($this->customerLoginChecker->isLoginAllowed());
    }

    /**
     * Test exception in isLoginAllowed method.
     *
     * @return void
     */
    public function testIsLoginAllowedWithException()
    {
        $exception = new NoSuchEntityException();
        $this->userContext->expects($this->once())->method('getUserType')->willReturn(3);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(1);
        $this->customerRepository->expects($this->once())->method('getById')->willThrowException($exception);
        $this->assertFalse($this->customerLoginChecker->isLoginAllowed());
    }
}
