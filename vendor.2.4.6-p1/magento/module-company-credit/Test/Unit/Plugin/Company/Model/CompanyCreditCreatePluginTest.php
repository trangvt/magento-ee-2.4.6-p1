<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Company\Model;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company\Save;
use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterfaceFactory;
use Magento\CompanyCredit\Plugin\Company\Model\CompanyCreditCreatePlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CompanyCreditCreatePlugin.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyCreditCreatePluginTest extends TestCase
{
    /**
     * @var CreditLimitInterfaceFactory|MockObject
     */
    private $creditLimitFactory;

    /**
     * @var CreditLimitRepositoryInterface|MockObject
     */
    private $creditLimitRepository;

    /**
     * @var CreditLimitManagementInterface|MockObject
     */
    private $creditLimitManagement;

    /**
     * @var WebsiteRepositoryInterface|MockObject
     */
    private $websiteRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CompanyCreditCreatePlugin
     */
    private $companyCreditCreatePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimitFactory = $this
            ->getMockBuilder(CreditLimitInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditLimitRepository = $this
            ->getMockBuilder(CreditLimitRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagement = $this
            ->getMockBuilder(CreditLimitManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->websiteRepository = $this->getMockBuilder(WebsiteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->companyCreditCreatePlugin = $objectManager->getObject(
            CompanyCreditCreatePlugin::class,
            [
                'creditLimitFactory' => $this->creditLimitFactory,
                'creditLimitRepository' => $this->creditLimitRepository,
                'creditLimitManagement' => $this->creditLimitManagement,
                'websiteRepository' => $this->websiteRepository,
                'request' => $this->request,
            ]
        );
    }

    /**
     * Test for afterSave method.
     *
     * @return void
     */
    public function testAfterSave()
    {
        $companyId = 1;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getId')->willReturn($companyId);
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn(2);
        $companySave = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals($company, $this->companyCreditCreatePlugin->afterSave($companySave, $company));
    }

    /**
     * Test for afterSave method without credit limit.
     *
     * @return void
     */
    public function testAfterSaveWithoutCreditLimit()
    {
        $companyId = 1;
        $currencyCode = 'USD';
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->atLeastOnce())->method('getId')->willReturn($companyId);
        $this->creditLimitManagement->expects($this->once())->method('getCreditByCompanyId')->with($companyId)
            ->willThrowException(new NoSuchEntityException());
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitFactory->expects($this->once())->method('create')->willReturn($creditLimit);
        $creditLimit->expects($this->atLeastOnce())->method('setCompanyId')->with($companyId)->willReturnSelf();
        $creditLimit->expects($this->once())->method('getId')->willReturn(null);
        $this->request->expects($this->once())->method('getParam')->with('company_credit')->willReturn(null);
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getBaseCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->websiteRepository->expects($this->once())->method('getDefault')->willReturn($website);
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn($currencyCode);
        $creditLimit->expects($this->once())->method('setCurrencyCode')->with($currencyCode)->willReturnSelf();
        $this->creditLimitRepository->expects($this->once())
            ->method('save')->with($creditLimit)->willReturn($creditLimit);
        $companySave = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals($company, $this->companyCreditCreatePlugin->afterSave($companySave, $company));
    }
}
