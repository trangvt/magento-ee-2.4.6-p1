<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Observer;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Company\Observer\CustomerAccountEdited;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerAccountEditedTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var Customer|MockObject
     */
    private $customerResourceMock;

    /**
     * @var CustomerAccountEdited
     */
    private $customerAccountEdited;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->customerRepositoryMock = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->customerResourceMock = $this->createPartialMock(
            Customer::class,
            ['saveAdvancedCustomAttributes']
        );
        $objectManager = new ObjectManager($this);
        $this->customerAccountEdited = $objectManager->getObject(
            CustomerAccountEdited::class,
            [
                'customerRepository' => $this->customerRepositoryMock,
                'request' => $this->requestMock,
                'customerResource' => $this->customerResourceMock,
            ]
        );
    }

    /**
     * Test method for execute
     */
    public function testExecute()
    {
        $email = 'email@sample.com';
        $customerData = ['extension_attributes' => ['company_attributes' => ['job_title' => 'Manager']]];
        /**
         * @var Observer|MockObject $observer
         */
        $observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getEmail'])
            ->disableOriginalConstructor()
            ->getMock();
        $observer->expects($this->once())->method('getEmail')->willReturn($email);
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->customerRepositoryMock->expects($this->once())->method('get')->willReturn($customer);
        $this->requestMock->expects($this->once())->method('getParam')->willReturn($customerData);

        $customerExtensionAttributes = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $customerAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionAttributes);
        $customerAttributes->expects($this->atLeastOnce())->method('setCustomerId')->willReturnSelf();
        $customerExtensionAttributes->expects($this->atLeastOnce())->method('getCompanyAttributes')
            ->willReturn($customerAttributes);
        $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        $companyAttributes->setCustomerId($customer->getId())
            ->setJobTitle($customerData['extension_attributes']['company_attributes']['job_title']);
        $this->customerResourceMock->expects($this->once())
            ->method('saveAdvancedCustomAttributes')
            ->with($customerAttributes)
            ->willReturnSelf();
        $this->customerAccountEdited->execute($observer);
    }
}
