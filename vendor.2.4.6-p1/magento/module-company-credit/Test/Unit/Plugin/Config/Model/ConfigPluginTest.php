<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Config\Model;

use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Plugin\Config\Model\ConfigPlugin;
use Magento\Config\Model\Config;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigPluginTest extends TestCase
{
    /**
     * @var WebsiteRepositoryInterface|MockObject
     */
    private $websiteRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CreditLimitRepositoryInterface|MockObject
     */
    private $creditLimitRepository;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var ConfigPlugin
     */
    private $configPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->websiteRepository = $this->createMock(
            WebsiteRepositoryInterface::class
        );
        $this->searchCriteriaBuilder = $this->createMock(
            SearchCriteriaBuilder::class
        );
        $this->creditLimitRepository = $this->createMock(
            CreditLimitRepositoryInterface::class
        );
        $this->messageManager = $this->createMock(
            ManagerInterface::class
        );
        $this->urlBuilder = $this->createMock(
            UrlInterface::class
        );

        $objectManager = new ObjectManager($this);
        $this->configPlugin = $objectManager->getObject(
            ConfigPlugin::class,
            [
                'websiteRepository' => $this->websiteRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'creditLimitRepository' => $this->creditLimitRepository,
                'messageManager' => $this->messageManager,
                'urlBuilder' => $this->urlBuilder,
            ]
        );
    }

    /**
     * Test method for aroundSave.
     *
     * @return void
     */
    public function testAroundSave()
    {
        $websiteId = 1;
        $websiteName = 'Website Name';
        $currencyCode = 'USD';
        $newCurrencyCode = 'EUR';
        $url = '/admin/company/index';
        $config = $this->getMockBuilder(Config::class)
            ->addMethods(['getSection'])
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())->method('getSection')->willReturn('currency');
        $website = $this->createMock(Website::class);
        $this->websiteRepository->expects($this->exactly(2))->method('getList')->willReturn([$website]);
        $website->expects($this->once())->method('getCode')->willReturn('website_code');
        $website->expects($this->exactly(2))->method('getId')->willReturn($websiteId);
        $website->expects($this->exactly(2))
            ->method('getBaseCurrencyCode')->willReturnOnConsecutiveCalls($currencyCode, $newCurrencyCode);
        $website->expects($this->once())->method('getName')->willReturn($websiteName);
        $this->websiteRepository->expects($this->once())->method('clean');
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')->with(
            CreditLimitInterface::CURRENCY_CODE,
            $currencyCode
        )->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('setPageSize')->with(0)->willReturnSelf();
        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $this->creditLimitRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(1);
        $this->urlBuilder->expects($this->once())->method('getUrl')->with('company/index')->willReturn($url);
        $this->messageManager->expects($this->once())->method('addComplexWarningMessage')->with(
            'baseCurrencyChangeWarning',
            [
                'websiteName' => $websiteName,
                'currencyCode' => $currencyCode,
                'url' => $url,
            ]
        )->willReturnSelf();
        $this->assertSame(
            $config,
            $this->configPlugin->aroundSave(
                $config,
                function () use ($config) {
                    return $config;
                }
            )
        );
    }
}
