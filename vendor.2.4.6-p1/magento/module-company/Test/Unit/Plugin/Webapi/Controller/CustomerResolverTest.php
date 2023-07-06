<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Webapi\Controller;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Plugin\Webapi\Controller\CustomerResolver;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerResolverTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerResolver
     */
    private $customerResolver;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
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
        $this->customerResolver = $objectManagerHelper->getObject(
            CustomerResolver::class,
            [
                'userContext' => $this->userContext,
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    /**
     * Test getCustomer method.
     *
     * @return void
     */
    public function testGetCustomer()
    {
        $userId = 1;
        $customer = $this
            ->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserType')->willReturn(3);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $this->assertEquals($customer, $this->customerResolver->getCustomer());
    }

    /**
     * Test getCustomer method throws NoSuchEntityException exception.
     *
     * @return void
     */
    public function testGetCustomerWithException()
    {
        $userId = 1;
        $exception = new NoSuchEntityException();
        $this->userContext->expects($this->once())->method('getUserType')->willReturn(3);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->customerRepository->expects($this->once())->method('getById')->willThrowException($exception);
        $this->assertNull($this->customerResolver->getCustomer());
    }

    /**
     * Test getCustomer method with guest customer.
     *
     * @return void
     */
    public function testWithGuestCustomer()
    {
        $this->userContext->expects($this->once())->method('getUserType')->willReturn(4);
        $this->assertNull($this->customerResolver->getCustomer());
    }
}
