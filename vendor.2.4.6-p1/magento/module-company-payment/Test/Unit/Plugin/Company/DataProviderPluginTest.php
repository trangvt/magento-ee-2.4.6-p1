<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\CompanyPayment\Test\Unit\Plugin\Company;

use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company\DataProvider;
use Magento\CompanyPayment\Plugin\Company\DataProviderPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class DataProviderPluginTest extends TestCase
{
    /**
     * @var DataProviderPlugin
     */
    private $dataProviderPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->dataProviderPlugin = $objectManager->getObject(
            DataProviderPlugin::class
        );
    }

    /**
     * Test for method aroundGetSettingsData.
     *
     * @return void
     */
    public function testAroundGetSettingsData()
    {
        $applicableMethod = 'payment1';
        $availableMethods = [$applicableMethod, 'payment2'];
        $useConfigSettings = true;
        $originalSettings = ['original_settings' => 'some settings'];
        $dataProvider = $this->createMock(DataProvider::class);
        $closure = function (CompanyInterface $company) use ($originalSettings) {
            return $originalSettings;
        };
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $companyExtension = $this->getMockForAbstractClass(
            CompanyExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getApplicablePaymentMethod', 'getAvailablePaymentMethods', 'getUseConfigSettings']
        );
        $company->expects($this->once())->method('getExtensionAttributes')->willReturn($companyExtension);
        $companyExtension->expects($this->once())->method('getApplicablePaymentMethod')->willReturn($applicableMethod);
        $companyExtension->expects($this->once())->method('getAvailablePaymentMethods')->willReturn($availableMethods);
        $companyExtension->expects($this->once())->method('getUseConfigSettings')->willReturn($useConfigSettings);
        $this->assertEquals(
            array_replace_recursive(
                [
                    'extension_attributes' => [
                        'applicable_payment_method' => $applicableMethod,
                        'available_payment_methods' => $availableMethods,
                        'use_config_settings' => $useConfigSettings,
                    ],
                ],
                $originalSettings
            ),
            $this->dataProviderPlugin->aroundGetSettingsData($dataProvider, $closure, $company)
        );
    }
}
