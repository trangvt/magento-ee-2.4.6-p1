<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\AsyncOrder\Model;

use Magento\AsyncOrder\Model\AsyncPaymentInformationCustomerPublisher;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Plugin\AsyncOrder\Model\PermissionCheckPlugin;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\PaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Session\Storage;

/**
 * Unit test for \Magento\Company\Plugin\AsyncOrder\Model\PermissionCheckPlugin.
 */
class PermissionCheckPluginTest extends TestCase
{
    /**
     * Cart id
     */
    private const CART_ID = '1';

    /**
     * @var Session|MockObject
     */
    private $userContext;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var PaymentInterface|MockObject
     */
    private $paymentMethod;

    /**
     * @var AsyncPaymentInformationCustomerPublisher|MockObject
     */
    private $subject;

    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var CompanyExtensionInterface|MockObject
     */
    private $companyExtensionAttributes;

    /**
     * @var DataObject
     */
    private $customer;

    /**
     * @var PermissionCheckPlugin
     */
    private $plugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $constructArguments = $objectManagerHelper->getConstructArguments(
            Session::class,
            ['storage' => new Storage()]
        );
        $this->userContext = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getCustomer'])
            ->setConstructorArgs($constructArguments)
            ->getMock();
        $this->companyManagement = $this
            ->getMockBuilder(CompanyManagementInterface::class)
            ->onlyMethods(['getByCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->paymentMethod = $this->getMockForAbstractClass(PaymentInterface::class);
        $this->subject = $this
            ->getMockBuilder(AsyncPaymentInformationCustomerPublisher::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyExtensionAttributes = $this->getMockBuilder(CompanyExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsPurchaseOrderEnabled'])
            ->getMockForAbstractClass();
        $this->customer = new DataObject(['id' => 1]);
        $this->plugin = $objectManagerHelper->getObject(
            PermissionCheckPlugin::class,
            [
                'userContext' => $this->userContext,
                'companyManagement' => $this->companyManagement
            ]
        );
    }

    /**
     * Test beforeSavePaymentInformationAndPlaceOrder method.
     *
     * @return void
     */
    public function testBeforeSavePaymentInformationAndPlaceOrder(): void
    {
        $this->userContext
            ->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customer);
        $this->companyManagement
            ->expects($this->once())
            ->method('getByCustomerId')
            ->willReturn(null);
        $this->assertEquals(null, $this->plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->subject,
            self::CART_ID,
            $this->paymentMethod
        ));
    }

    /**
     * Test beforeSavePaymentInformationAndPlaceOrder method throws AuthorizationException.
     *
     * @return void
     */
    public function testBeforeSavePaymentInformationAndPlaceOrderWithException(): void
    {
        $this->expectException('Magento\Framework\Exception\AuthorizationException');
        $this->expectExceptionMessage('You are not authorized to access this resource.');
        $this->userContext
            ->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customer);
        $this->companyManagement
            ->expects($this->once())
            ->method('getByCustomerId')
            ->with($this->customer->getId())
            ->willReturn($this->company);
        $this->companyExtensionAttributes
            ->expects($this->once())
            ->method('getIsPurchaseOrderEnabled')
            ->willReturnSelf();
        $this->company
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionAttributes);
        $this->companyExtensionAttributes
            ->expects($this->any())
            ->method('getIsPurchaseOrderEnabled')
            ->willReturn(true);
        $this->plugin
            ->beforeSavePaymentInformationAndPlaceOrder(
                $this->subject,
                self::CART_ID,
                $this->paymentMethod
            );
    }
}
