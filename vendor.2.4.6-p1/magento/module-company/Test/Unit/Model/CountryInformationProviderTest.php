<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Model\CountryInformationProvider;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Api\Data\CountryInformationInterface;
use Magento\Directory\Api\Data\RegionInformationInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CountryInformationProviderTest extends TestCase
{
    /**
     * @var CountryInformationAcquirerInterface|MockObject
     */
    protected $countryInformationAcquirer;

    /**
     * @var ArrayUtils|MockObject
     */
    protected $arrayUtils;

    /**
     * @var ResolverInterface|MockObject
     */
    protected $resolver;

    /**
     * @var CountryInformationProvider|MockObject
     */
    protected $countryInformationProvider;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->countryInformationAcquirer = $this->createMock(
            CountryInformationAcquirerInterface::class
        );
        $this->arrayUtils = $this->createMock(
            ArrayUtils::class
        );
        $this->resolver = $this->createMock(
            ResolverInterface::class
        );
        $objectManager = new ObjectManager($this);
        $this->countryInformationProvider = $objectManager->getObject(
            CountryInformationProvider::class,
            [
                'countryInformationAcquirer' => $this->countryInformationAcquirer,
                'arrayUtils' => $this->arrayUtils,
                'resolver' => $this->resolver
            ]
        );
    }

    /**
     * Test getCountryNameByCode
     *
     * @param string $code
     * @param string $countryName
     * @dataProvider dataProviderGetCountryNameByCode
     */
    public function testGetCountryNameByCode($code, $countryName)
    {
        $countryInfo =
            $this->getMockForAbstractClass(CountryInformationInterface::class);
        $countryInfo->expects($this->any())->method('getId')->willReturn($code);
        $countryInfo->expects($this->any())->method('getFullNameLocale')->willReturn($countryName);
        $this->countryInformationAcquirer->expects($this->any())->method('getCountryInfo')->willReturn($countryInfo);

        $this->assertEquals($countryName, $this->countryInformationProvider->getCountryNameByCode($code));
    }

    /**
     * Test getCountriesList
     *
     * @param array $countriesList
     * @dataProvider dataProviderGetCountriesList
     */
    public function testGetCountriesList(array $countriesList)
    {
        $this->populateCountriesAndRegions();
        $this->resolver->expects($this->any())->method('getLocale')->willReturn('en_US');
        $this->arrayUtils->expects($this->any())->method('ksortMultibyte')->willReturn($countriesList);

        $this->assertEquals($countriesList, $this->countryInformationProvider->getCountriesList());
    }

    /**
     * Test getCountriesList when the full name locale of the country is empty
     *
     * @param int $countryId
     * @param string $countryCode
     * @param string $regionName
     * @param string $localeName
     * @param string $fullCountryNameLocale
     * @param array $countriesList
     * @dataProvider dataProviderGetCountryNameByCodeForCountryLocale
     */
    public function testGetCountriesListForCountryLocale(
        int $countryId,
        string $countryCode,
        string $regionName,
        string $localeName,
        string $fullCountryNameLocale,
        array $countriesList
    ) {
        $countryMock = $this->createMock(
            CountryInformationInterface::class
        );
        $regionMock = $this->createMock(
            RegionInformationInterface::class
        );
        $regionMock->expects($this->any())->method('getId')->willReturn($countryId);
        $regionMock->expects($this->any())->method('getName')->willReturn($regionName);
        $regionsIterator = new \ArrayIterator([$regionMock]);
        $countryMock->expects($this->any())->method('getId')->willReturn($countryCode);
        $countryMock->expects($this->any())->method('getFullNameLocale')->willReturn($fullCountryNameLocale);
        $countryMock->expects($this->any())->method('getAvailableRegions')->willReturn($regionsIterator);
        $countriesIterator = new \ArrayIterator([$countryMock]);
        $this->countryInformationAcquirer->expects($this->any())->method('getCountriesInfo')
            ->willReturn($countriesIterator);
        $this->resolver->expects($this->any())->method('getLocale')->willReturn($localeName);
        $this->arrayUtils->expects($this->any())->method('ksortMultibyte')->willReturn($countriesList);

        $this->assertEquals($countriesList, $this->countryInformationProvider->getCountriesList());
    }

    /**
     * Data provider getCountryNameByCode
     *
     * @return array
     */
    public function dataProviderGetCountryNameByCodeForCountryLocale()
    {
        return [
            'test country list with valid country name with country locale' => [1, 'US', 'Alabama', 'en_US', 'United States', ['US' => 'United States']],
            'test country list without country locale' => [1, 'AN', '', '', '', []]
        ];
    }

    /**
     * Test getActualRegionName
     *
     * @param string $countryCode
     * @param int $regionId
     * @param string $regionName
     * @dataProvider dataProviderGetActualRegionName
     */
    public function testGetActualRegionName($countryCode, $regionId, $regionName)
    {
        $this->populateCountriesAndRegions();

        $this->assertEquals(
            $regionName,
            $this->countryInformationProvider->getActualRegionName($countryCode, $regionId, $regionName)
        );
    }

    /**
     * Data provider getCountryNameByCode
     *
     * @return array
     */
    public function dataProviderGetCountryNameByCode()
    {
        return [
            ['US', 'United States']
        ];
    }

    /**
     * Data provider getRegionNameById
     *
     * @return array
     */
    public function dataProviderGetRegionNameById()
    {
        return [
            ['1', 'Alabama']
        ];
    }

    /**
     * Data provider getCountriesList
     *
     * @return array
     */
    public function dataProviderGetCountriesList()
    {
        return [
            [
                ['US' => 'United States', 'UK' => 'United Kingdom']
            ]
        ];
    }

    /**
     * Data provider getActualRegionId
     *
     * @return array
     */
    public function dataProviderGetActualRegionId()
    {
        return [
            [12, 12],
            [null, 1]
        ];
    }

    /**
     * Data provider getActualRegionName
     *
     * @return array
     */
    public function dataProviderGetActualRegionName()
    {
        return [
            ['US', '1', 'Alabama'],
            ['UK', '1', 'London']
        ];
    }

    /**
     * populateCountriesAndRegions
     */
    private function populateCountriesAndRegions()
    {
        $firstCountry = $this->createMock(
            CountryInformationInterface::class
        );
        $secondCountry = $this->createMock(
            CountryInformationInterface::class
        );
        $firstRegion = $this->createMock(
            RegionInformationInterface::class
        );
        $secondRegion = $this->createMock(
            RegionInformationInterface::class
        );
        $firstRegion->expects($this->any())->method('getId')->willReturn(1);
        $firstRegion->expects($this->any())->method('getName')->willReturn('Alabama');
        $secondRegion->expects($this->any())->method('getId')->willReturn(12);
        $secondRegion->expects($this->any())->method('getName')->willReturn('California');
        $regionsIterator = new \ArrayIterator([$firstRegion, $secondRegion]);
        $firstCountry->expects($this->any())->method('getId')->willReturn('US');
        $firstCountry->expects($this->any())->method('getFullNameLocale')->willReturn('United States');
        $firstCountry->expects($this->any())->method('getAvailableRegions')->willReturn($regionsIterator);
        $secondCountry->expects($this->any())->method('getId')->willReturn('UK');
        $secondCountry->expects($this->any())->method('getFullNameLocale')->willReturn('United Kingdom');
        $secondCountry->expects($this->any())->method('getAvailableRegions')->willReturn(null);
        $countriesIterator = new \ArrayIterator([$firstCountry, $secondCountry]);
        $this->countryInformationAcquirer->expects($this->any())->method('getCountriesInfo')
            ->willReturn($countriesIterator);
    }
}
