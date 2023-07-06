<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyPayment\Test\Unit\Plugin\Company;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyExtensionFactory;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyPayment\Model\CompanyPaymentMethodFactory;
use Magento\CompanyPayment\Model\ResourceModel\CompanyPaymentMethod;
use Magento\CompanyPayment\Plugin\Company\CompanyRepositoryPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\CompanyPayment\Model\CompanyPaymentMethod as CompanyPaymentMethodModel;

class CompanyRepositoryPluginTest extends TestCase
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
     * @var CompanyExtensionFactory|MockObject
     */
    private $companyExtensionFactory;

    /**
     * @var CompanyRepositoryPlugin
     */
    private $companyRepositoryPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyPaymentMethodResource =
            $this->createMock(CompanyPaymentMethod::class);
        $this->companyPaymentMethodFactory =
            $this->createPartialMock(CompanyPaymentMethodFactory::class, ['create']);
        $this->companyExtensionFactory =
            $this->createPartialMock(CompanyExtensionFactory::class, ['create']);

        $objectManager = new ObjectManager($this);
        $this->companyRepositoryPlugin = $objectManager->getObject(
            CompanyRepositoryPlugin::class,
            [
                'companyPaymentMethodResource' => $this->companyPaymentMethodResource,
                'companyPaymentMethodFactory' => $this->companyPaymentMethodFactory,
                'companyExtensionFactory' => $this->companyExtensionFactory,
            ]
        );
    }

    /**
     * Test for method afterGet.
     *
     * @param int $companyId
     * @param bool $useConfigSettings
     * @param string $applicablePayment
     * @param string|null $availablePayment
     * @return void
     * @dataProvider getPaymentMethodsDataProvider
     */
    public function testAfterGet(
        int $companyId,
        bool $useConfigSettings,
        string  $applicablePayment,
        string $availablePayment = null
    ) {
        list($companyRepository, $company) = $this->getPaymentMethodsForCompanyMock(
            $companyId,
            $useConfigSettings,
            $applicablePayment,
            $availablePayment
        );
        $this->assertEquals(
            $company,
            $this->companyRepositoryPlugin->afterGet($companyRepository, $company)
        );
    }

    /**
     * Test for method testAfterSave.
     *
     * @param int $companyId
     * @param bool $useConfigSettings
     * @param string $applicablePayment
     * @param string|null $availablePayment
     * @return void
     * @dataProvider getPaymentMethodsDataProvider
     */
    public function testAfterSave(
        int $companyId,
        bool $useConfigSettings,
        string  $applicablePayment,
        string $availablePayment = null
    ) {
        list($companyRepository, $company) = $this->getPaymentMethodsForCompanyMock(
            $companyId,
            $useConfigSettings,
            $applicablePayment,
            $availablePayment
        );
        $this->assertEquals(
            $company,
            $this->companyRepositoryPlugin->afterSave($companyRepository, $company)
        );
    }

    /**
     * Get payment methods data
     *
     * @return array
     */
    public function getPaymentMethodsDataProvider(): array
    {
        return [
            'when config enable and both available and applicable payment exist' => [
                1, true, 'payment1', 'payment2'
            ],
            'when config disable and both available and applicable payment exist' => [
                1, false, 'payment1', 'payment2'
            ],
            'when config enable and only applicable payment exist' => [
                1, true, 'payment1', null
            ],
            'when config disable and only applicable payment exist' => [
                1, false, 'payment1', null
            ]
        ];
    }

    /**
     * Get mock for company payment methods
     *
     * @param int $companyId
     * @param bool $useConfigSettings
     * @param string $applicablePayment
     * @param string|null $availablePayment
     * @return array
     */
    private function getPaymentMethodsForCompanyMock(
        int $companyId,
        bool $useConfigSettings,
        string $applicablePayment,
        string $availablePayment = null
    ): array {
        $availablePayments = [$applicablePayment, $availablePayment];
        $companyRepository = $this->getMockForAbstractClass(CompanyRepositoryInterface::class);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $company->method('getId')
            ->willReturn($companyId);
        $paymentMethod = $this->getMockBuilder(CompanyPaymentMethodModel::class)
            ->addMethods(['getUseConfigSettings'])
            ->onlyMethods(['getApplicablePaymentMethod', 'getAvailablePaymentMethods', 'load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyPaymentMethodFactory->method('create')
            ->willReturn($paymentMethod);
        $paymentMethod->method('load')
            ->with($companyId)
            ->willReturn($paymentMethod);
        $paymentMethod->method('getId')
            ->willReturn(2);
        $companyExtension = $this->getMockForAbstractClass(
            CompanyExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setApplicablePaymentMethod', 'setAvailablePaymentMethods', 'setUseConfigSettings']
        );
        $this->companyExtensionFactory->method('create')
            ->willReturn($companyExtension);
        $paymentMethod->method('getApplicablePaymentMethod')
            ->willReturn($applicablePayment);
        $paymentMethod->method('getAvailablePaymentMethods')
            ->willReturn($availablePayments);
        $paymentMethod->method('getUseConfigSettings')
            ->willReturn($useConfigSettings);
        $companyExtension->method('setApplicablePaymentMethod')
            ->with($applicablePayment)
            ->willReturnSelf();
        $companyExtension->method('setAvailablePaymentMethods')
            ->with($availablePayments)
            ->willReturnSelf();
        $companyExtension->method('setUseConfigSettings')
            ->with($useConfigSettings)
            ->willReturnSelf();
        $company->method('setExtensionAttributes')
            ->with($companyExtension)
            ->willReturnSelf();
        return [$companyRepository, $company];
    }
}
