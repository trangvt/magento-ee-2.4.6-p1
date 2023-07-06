<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidator\CompanyAddress;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Directory\Api\Data\RegionInformationInterface;
use Magento\Directory\Helper\Data;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for company address validator.
 */
class CompanyAddressTest extends TestCase
{
    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var InputException|MockObject
     */
    private $exception;

    /**
     * @var CountryInformationAcquirerInterface|MockObject
     */
    private $countryInformationAcquirer;

    /**
     * @var Data|MockObject
     */
    private $directoryData;

    /**
     * @var CompanyAddress
     */
    private $companyAddress;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->exception = $this->getMockBuilder(InputException::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->countryInformationAcquirer = $this
            ->getMockBuilder(CountryInformationAcquirerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->directoryData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->companyAddress = $objectManager->getObject(
            CompanyAddress::class,
            [
                'company' => $this->company,
                'exception' => $this->exception,
                'countryInformationAcquirer' => $this->countryInformationAcquirer,
                'directoryData' => $this->directoryData
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @param int $availableRegionId
     * @param int|null $regionId
     * @param bool $isRequired
     * @return void
     * @dataProvider regionsDataProvider
     */
    public function testExecute(int $availableRegionId, ?int $regionId, bool $isRequired): void
    {
        $countryId = 'US';
        $this->company->method('getCountryId')
            ->willReturn($countryId);
        $countryInformation = $this->getMockBuilder(CountryInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryData->method('isShowNonRequiredState')
            ->willReturn(true);
        $this->directoryData->method('isRegionRequired')
            ->willReturn($isRequired);
        $this->countryInformationAcquirer->method('getCountryInfo')
            ->with($countryId)
            ->willReturn($countryInformation);
        $region = $this->getMockBuilder(RegionInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $countryInformation->method('getAvailableRegions')
            ->willReturn([$region]);
        $this->company->method('getRegionId')
            ->willReturn($availableRegionId);
        $region->method('getId')
            ->willReturn($regionId);
        $this->exception->expects($this->never())
            ->method('addError');
        $this->companyAddress->execute();
    }

    /**
     * Test for execute method with non-existing region.
     *
     * @return void
     */
    public function testExecuteWithNonExistingRegion(): void
    {
        $countryId = 'US';
        $regionId = 11;
        $this->company->method('getCountryId')
            ->willReturn($countryId);
        $countryInformation = $this->getMockBuilder(CountryInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryData->method('isShowNonRequiredState')
            ->willReturn(true);
        $this->directoryData->method('isRegionRequired')
            ->willReturn(true);
        $this->countryInformationAcquirer->method('getCountryInfo')
            ->with($countryId)
            ->willReturn($countryInformation);
        $region = $this->getMockBuilder(RegionInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $countryInformation->method('getAvailableRegions')
            ->willReturn([$region]);
        $this->company->method('getRegionId')
            ->willReturn($regionId);
        $region->method('getId')
            ->willReturn(12);
        $this->exception->expects($this->once())
            ->method('addError')
            ->with(
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['fieldName' => 'region_id', 'value' => $regionId]
                )
            )
            ->willReturnSelf();
        $this->companyAddress->execute();
    }

    /**
     * Test for execute method with non-existing country.
     *
     * @return void
     */
    public function testExecuteWithNonExistingCountry(): void
    {
        $countryId = 'US';
        $this->company->method('getCountryId')
            ->willReturn($countryId);
        $this->directoryData->method('isShowNonRequiredState')
            ->willReturn(true);
        $this->directoryData->method('isRegionRequired')
            ->willReturn(true);
        $this->countryInformationAcquirer->method('getCountryInfo')
            ->with($countryId)
            ->willThrowException(new NoSuchEntityException());
        $this->exception->expects($this->once())
            ->method('addError')
            ->with(
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['fieldName' => 'country_id', 'value' => $countryId]
                )
            )
            ->willReturnSelf();
        $this->companyAddress->execute();
    }

    /**
     * Regions data provider
     *
     * @return array
     */
    public function regionsDataProvider(): array
    {
        return [
            [11, 11, true],
            [11, null, false],
        ];
    }
}
