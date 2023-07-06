<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Company\Account;

use Magento\Company\Block\Company\Account\Create;
use Magento\Company\Model\CountryInformationProvider;
use Magento\Customer\Helper\Address;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    /**
     * @var string
     */
    private $formData = 'form_data';

    /**
     * @var CountryInformationProvider|\PHPUnit\Framework\MockObject_MockObject
     */
    private $countryInformationProvider;

    /**
     * @var Address|\PHPUnit\Framework\MockObject_MockObject
     */
    private $addressHelper;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @var Create
     */
    private $create;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->countryInformationProvider = $this->createMock(CountryInformationProvider::class);
        $this->addressHelper = $this->createMock(Address::class);
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $objectManager = new ObjectManager($this);
        $this->create = $objectManager->getObject(
            Create::class,
            [
                'countryInformationProvider' => $this->countryInformationProvider,
                'addressHelper' => $this->addressHelper,
                '_urlBuilder' => $this->urlBuilder,
                '_scopeConfig' => $this->scopeConfig,
                'data' => [],
            ]
        );
    }

    /**
     * Test method for getConfig.
     */
    public function testGetConfig()
    {
        $value = 'general/region/display_all/all';
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn($value);
        $this->assertEquals($value, $this->create->getConfig($value));
    }

    /**
     * Test method for getPostActionUrl.
     */
    public function testGetPostActionUrl()
    {
        $value = '*/account/createPost';
        $this->urlBuilder->expects($this->any())->method('getUrl')->willReturn($value);
        $this->assertEquals($value, $this->create->getPostActionUrl());
    }

    /**
     * Test method for getCountriesList.
     */
    public function testGetCountriesList()
    {
        $data = ['test'];
        $this->countryInformationProvider->expects($this->any())->method('getCountriesList')->willReturn($data);
        $this->assertEquals($data, $this->create->getCountriesList());
    }

    /**
     * Test method for getFormData.
     *
     * @param string|null $data
     * @param string|null $additionalData
     * @dataProvider getFromDataDataProvider
     */
    public function testGetFormData($data, $additionalData = null)
    {
        $formData = new DataObject();
        $formData->setRegion($data);
        if ($formData->getRegion() === null) {
            $formData->setRegionId($additionalData);
        }
        $this->create->setData($this->formData, $formData);
        $this->assertSame($formData, $this->create->getFormData());
    }

    /**
     * Data provider for testGetFormData.
     *
     * @return array
     */
    public function getFromDataDataProvider()
    {
        return [
            ['California'],
            [null, 56]
        ];
    }

    /**
     * Test method for getDefaultCountryId.
     */
    public function testGetDefaultCountryId()
    {
        $path = 'test/path';
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn($path);
        $this->assertEquals($path, $this->create->getDefaultCountryId());
    }

    /**
     * Test method for getAttributeValidationClass.
     */
    public function testGetAttributeValidationClass()
    {
        $attributeCode = 'test';
        $this->addressHelper->expects($this->once())
            ->method('getAttributeValidationClass')
            ->with($attributeCode)
            ->willReturn('testAttributeValidationClass');
        $this->assertEquals('testAttributeValidationClass', $this->create->getAttributeValidationClass($attributeCode));
    }
}
