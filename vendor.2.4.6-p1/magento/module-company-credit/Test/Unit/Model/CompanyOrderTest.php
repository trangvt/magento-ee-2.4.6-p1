<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Model\CompanyOrder;
use Magento\CompanyCredit\Model\CompanyStatus;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Company Order.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyOrderTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var CompanyStatus|MockObject
     */
    private $companyStatus;

    /**
     * @var CompanyOrder
     */
    private $companyOrder;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );
        $this->companyStatus = $this->createMock(
            CompanyStatus::class
        );

        $objectManager = new ObjectManager($this);
        $this->companyOrder = $objectManager->getObject(
            CompanyOrder::class,
            [
                'customerRepository' => $this->customerRepository,
                'companyRepository' => $this->companyRepository,
                'companyStatus' => $this->companyStatus,
            ]
        );
    }

    /**
     * Test for method getCompanyIdByOrder.
     *
     * @return void
     */
    public function testGetCompanyIdByOrder()
    {
        $companyId = 1;
        $customerId = 2;
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $order->expects($this->once())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $order->expects($this->exactly(2))->method('getCustomerId')->willReturn($customerId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $customerExtensionAttributes = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCompanyAttributes']
        );
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($customerExtensionAttributes);
        $customerExtensionAttributes->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $this->assertEquals($companyId, $this->companyOrder->getCompanyIdByOrder($order));
    }

    /**
     * Test for method getCompanyIdByOrder with deleted customer.
     *
     * @return void
     */
    public function testGetCompanyIdByOrderWithDeletedCustomer()
    {
        $companyId = 1;
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $order->expects($this->exactly(2))->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $order->expects($this->once())->method('getCustomerId')->willReturn(null);
        $orderPayment->expects($this->once())
            ->method('getAdditionalInformation')->with('company_id')->willReturn($companyId);
        $this->assertEquals($companyId, $this->companyOrder->getCompanyIdByOrder($order));
    }

    /**
     * Test for method getCompanyIdForRefund.
     *
     * @return void
     */
    public function testGetCompanyIdForRefund()
    {
        $companyId = 1;
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $order->expects($this->exactly(2))->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $order->expects($this->once())->method('getCustomerId')->willReturn(null);
        $orderPayment->expects($this->once())
            ->method('getAdditionalInformation')->with('company_id')->willReturn($companyId);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())->method('getId')->willReturn($companyId);
        $this->companyStatus->expects($this->once())->method('isRefundAvailable')->with($companyId)->willReturn(true);
        $this->assertEquals($companyId, $this->companyOrder->getCompanyIdForRefund($order));
    }

    /**
     * Test for method getCompanyIdForRefund with deleted company.
     *
     * @return void
     */
    public function testGetCompanyIdForRefundWithDeletedCompany()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $companyId = null;
        $customerId = 2;
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $order->expects($this->once())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $order->expects($this->exactly(2))->method('getCustomerId')->willReturn($customerId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $customerExtensionAttributes = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCompanyAttributes']
        );
        $companyAttributes = $this->getMockForAbstractClass(CompanyCustomerInterface::class);
        $customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($customerExtensionAttributes);
        $customerExtensionAttributes->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)
            ->willThrowException(new NoSuchEntityException());
        $this->assertEquals($companyId, $this->companyOrder->getCompanyIdForRefund($order));
    }

    /**
     * Test for method getCompanyIdForRefund with rejected company.
     *
     * @return void
     */
    public function testGetCompanyIdForRefundWithRejectedCompany()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $companyId = 1;
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $order->expects($this->exactly(2))->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $order->expects($this->once())->method('getCustomerId')->willReturn(null);
        $orderPayment->expects($this->once())
            ->method('getAdditionalInformation')->with('company_id')->willReturn($companyId);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())->method('getId')->willReturn($companyId);
        $this->companyStatus->expects($this->once())->method('isRefundAvailable')->with($companyId)->willReturn(false);
        $this->assertEquals($companyId, $this->companyOrder->getCompanyIdForRefund($order));
    }
}
