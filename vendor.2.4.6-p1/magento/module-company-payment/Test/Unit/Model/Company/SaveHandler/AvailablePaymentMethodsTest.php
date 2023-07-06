<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\CompanyPayment\Test\Unit\Model\Company\SaveHandler;

use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyPayment\Model\Company\SaveHandler\AvailablePaymentMethods;
use Magento\CompanyPayment\Model\CompanyPaymentMethodFactory;
use Magento\CompanyPayment\Model\ResourceModel\CompanyPaymentMethod;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AvailablePaymentMethodsTest extends TestCase
{
    /**
     * @var CompanyPaymentMethod|MockObject
     */
    private $companyPaymentMethodResource;

    /**
     * @var CompanyPaymentMethodFactory|MockObject
     */
    private $companyPaymentMethodFactory;

    /**
     * @var AvailablePaymentMethods
     */
    private $availablePaymentMethods;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyPaymentMethodResource = $this->createMock(
            CompanyPaymentMethod::class
        );
        $this->companyPaymentMethodFactory = $this->createPartialMock(
            CompanyPaymentMethodFactory::class,
            ['create']
        );

        $objectManager = new ObjectManager($this);
        $this->availablePaymentMethods = $objectManager->getObject(
            AvailablePaymentMethods::class,
            [
                'companyPaymentMethodResource' => $this->companyPaymentMethodResource,
                'companyPaymentMethodFactory' => $this->companyPaymentMethodFactory,
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $companyId = 1;
        $applicablePayment = ['payment'];
        $availablePayments = [$applicablePayment[0], 'payment2'];
        $useConfigSettings = true;
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $company->expects($this->exactly(2))->method('getId')->willReturn($companyId);
        $initialCompany = $this->getMockForAbstractClass(CompanyInterface::class);
        $extensionAttributes = $this->getMockForAbstractClass(
            CompanyExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getApplicablePaymentMethod',
                'getAvailablePaymentMethods',
                'getUseConfigSettings',
                'setApplicablePaymentMethod',
                'setAvailablePaymentMethods',
                'setUseConfigSettings'
            ]
        );
        $company->expects($this->exactly(2))->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $initialExtensionAttributes = $this->getMockForAbstractClass(
            CompanyExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getApplicablePaymentMethod', 'getAvailablePaymentMethods', 'getUseConfigSettings']
        );
        $initialCompany->expects($this->once())
            ->method('getExtensionAttributes')->willReturn($initialExtensionAttributes);
        $extensionAttributes->expects($this->exactly(2))
            ->method('getApplicablePaymentMethod')->willReturn($applicablePayment);
        $extensionAttributes->method('getAvailablePaymentMethods')->willReturn($availablePayments);
        $extensionAttributes->expects($this->once())->method('getUseConfigSettings')->willReturn($useConfigSettings);
        $initialExtensionAttributes->expects($this->once())
            ->method('getApplicablePaymentMethod')->willReturn($availablePayments[1]);
        $paymentSettings = $this->createPartialMock(
            \Magento\CompanyPayment\Model\CompanyPaymentMethod::class,
            [
                'load',
                'getId',
                'setCompanyId',
                'setApplicablePaymentMethod',
                'setAvailablePaymentMethods',
                'setUseConfigSettings',
            ]
        );
        $this->companyPaymentMethodFactory->expects($this->once())->method('create')->willReturn($paymentSettings);
        $paymentSettings->expects($this->once())->method('load')->with($companyId)->willReturn($paymentSettings);
        $paymentSettings->expects($this->once())->method('getId')->willReturn(null);
        $paymentSettings->expects($this->once())->method('setCompanyId')->with($companyId)->willReturnSelf();
        $paymentSettings->expects($this->once())
            ->method('setApplicablePaymentMethod')->with($applicablePayment)->willReturnSelf();
        $paymentSettings->expects($this->once())
            ->method('setAvailablePaymentMethods')->with(implode(',', $availablePayments))->willReturnSelf();
        $paymentSettings->expects($this->once())
            ->method('setUseConfigSettings')->with($useConfigSettings)->willReturnSelf();
        $this->companyPaymentMethodResource->expects($this->once())
            ->method('save')->with($paymentSettings)->willReturn($paymentSettings);
        $extensionAttributes->expects($this->once())
            ->method('setApplicablePaymentMethod')->with(null)->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('setAvailablePaymentMethods')->with(null)->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('setUseConfigSettings')->with(null)->willReturnSelf();
        $company->expects($this->once())->method('setExtensionAttributes')
            ->with($extensionAttributes)->willReturnSelf();
        $this->availablePaymentMethods->execute($company, $initialCompany);
    }
}
