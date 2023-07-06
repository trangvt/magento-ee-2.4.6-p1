<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Store\Model;

use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Plugin\Store\Model\WebsitePlugin;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsitePluginTest extends TestCase
{
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
     * @var WebsitePlugin
     */
    private $websitePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->creditLimitRepository =
            $this->getMockForAbstractClass(CreditLimitRepositoryInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);

        $objectManager = new ObjectManager($this);
        $this->websitePlugin = $objectManager->getObject(
            WebsitePlugin::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'creditLimitRepository' => $this->creditLimitRepository,
                'messageManager' => $this->messageManager,
                'urlBuilder' => $this->urlBuilder,
            ]
        );
    }

    /**
     * Test method for afterAfterDelete.
     *
     * @return void
     */
    public function testAfterAfterDelete()
    {
        $websiteName = 'Website Name';
        $currencyCode = 'USD';
        $url = '/admin/company/index';
        $website = $this->createMock(Website::class);
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn($currencyCode);
        $website->expects($this->once())->method('getName')->willReturn($websiteName);
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
        $this->assertSame($website, $this->websitePlugin->afterAfterDelete($website));
    }
}
