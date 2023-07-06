<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Eav\Validator\Attribute;

use Magento\Company\Plugin\Eav\Validator\Attribute\TaxVatValidatorPlugin;
use Magento\Eav\Model\Validator\Attribute\Data;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for test TaxVatValidatorPlugin.
 */
class TaxVatValidatorPluginTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $eavAttributeValidatorDataMock;

    /**
     * @var AbstractModel|MockObject
     */
    private $customerAbstractModelMock;

    /**
     * @var TaxVatValidatorPlugin
     */
    private $taxVatValidatorPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->eavAttributeValidatorDataMock = $this->createMock(
            Data::class
        );
        $this->customerAbstractModelMock = $this->createMock(
            AbstractModel::class
        );
        $objectManagerHelper = new ObjectManager($this);
        $this->taxVatValidatorPlugin = $objectManagerHelper->getObject(
            TaxVatValidatorPlugin::class,
            []
        );
    }

    /**
     * Test for method beforeIsValid.
     *
     * @param array $data
     * @return void
     * @dataProvider dataProviderIsValid
     */
    public function testBeforeIsValid(array $data): void
    {
        $this->customerAbstractModelMock->expects($this->any())
            ->method('addData')
            ->with($data)
            ->willReturnSelf();
        if (array_key_exists('company_attributes', $data) && array_key_exists('taxvat', $data)) {
            $this->customerAbstractModelMock->expects($this->any())
                ->method('hasData')
                ->with('company_attributes')
                ->willReturn($data['company_attributes']);
            $this->eavAttributeValidatorDataMock->expects($this->any())
                ->method('setDeniedAttributesList')
                ->with([CustomerInterface::TAXVAT])
                ->willReturnSelf();
        }
        $this->taxVatValidatorPlugin->beforeIsValid(
            $this->eavAttributeValidatorDataMock,
            $this->customerAbstractModelMock
        );
    }

    /**
     * Data provider for method testBeforeIsValid.
     *
     * @return array
     */
    public function dataProviderIsValid(): array
    {
        return [
            'when company has no company_attributes' => [
                [
                    'id' => 1,
                    'company_name' => 'test',
                    'created_at' => '12-12-22'
                ]
            ],
            'when company has company_attributes and taxvat' => [
                [
                    'id' => 1,
                    'company_name' => 'test',
                    'created_at' => '12-12-22',
                    'company_attributes' => [
                        'company_id' => 1,
                        'is_active' => true
                    ],
                    'taxvat' => true
                ]
            ],
        ];
    }
}
