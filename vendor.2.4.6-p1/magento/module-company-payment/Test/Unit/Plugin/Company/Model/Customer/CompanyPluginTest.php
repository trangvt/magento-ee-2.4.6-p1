<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\CompanyPayment\Test\Unit\Plugin\Company\Model\Customer;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Customer\Company;
use Magento\CompanyPayment\Model\CompanyPaymentMethodFactory;
use Magento\CompanyPayment\Model\Config;
use Magento\CompanyPayment\Model\ResourceModel\CompanyPaymentMethod;
use Magento\CompanyPayment\Plugin\Company\Model\Customer\CompanyPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompanyPluginTest extends TestCase
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
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var CompanyPlugin
     */
    private $companyPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyPaymentMethodResource =
            $this->createMock(CompanyPaymentMethod::class);
        $this->companyPaymentMethodFactory =
            $this->createPartialMock(CompanyPaymentMethodFactory::class, ['create']);
        $this->config = $this->createMock(Config::class);

        $objectManager = new ObjectManager($this);
        $this->companyPlugin = $objectManager->getObject(
            CompanyPlugin::class,
            [
                'companyPaymentMethodResource' => $this->companyPaymentMethodResource,
                'companyPaymentMethodFactory' => $this->companyPaymentMethodFactory,
                'config' => $this->config,
            ]
        );
    }

    /**
     * Test for method afterCreateCompany.
     *
     * @return void
     */
    public function testAfterCreateCompany()
    {
        $companyId = 1;
        $availablePaymentMethods = ['payment1'];
        $subject = $this->createMock(Company::class);
        $result = $this->getMockForAbstractClass(CompanyInterface::class);
        $paymentSettings = $this->createMock(\Magento\CompanyPayment\Model\CompanyPaymentMethod::class);
        $this->companyPaymentMethodFactory->expects($this->once())->method('create')->willReturn($paymentSettings);
        $result->expects($this->once())->method('getId')->willReturn($companyId);
        $paymentSettings->expects($this->once())->method('setCompanyId')->with($companyId)->willReturnSelf();
        $paymentSettings->expects($this->once())->method('setApplicablePaymentMethod')->with('0')->willReturnSelf();
        $paymentSettings->expects($this->once())->method('setUseConfigSettings')->with('1')->willReturnSelf();
        $this->config->expects($this->once())->method('isSpecificApplicableMethodApplied')->willReturn(true);
        $this->config->expects($this->exactly(2))
            ->method('getAvailablePaymentMethods')->willReturn($availablePaymentMethods);
        $paymentSettings->expects($this->once())
            ->method('setAvailablePaymentMethods')->with($availablePaymentMethods)->willReturnSelf();
        $this->companyPaymentMethodResource->expects($this->once())
            ->method('save')->with($paymentSettings)->willReturn($paymentSettings);
        $this->assertEquals($result, $this->companyPlugin->afterCreateCompany($subject, $result));
    }
}
