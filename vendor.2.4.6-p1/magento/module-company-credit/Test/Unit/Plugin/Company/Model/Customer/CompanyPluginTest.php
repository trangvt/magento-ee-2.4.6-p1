<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Company\Model\Customer;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Customer\Company;
use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterfaceFactory;
use Magento\CompanyCredit\Plugin\Company\Model\Customer\CompanyPlugin;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CompanyCredit\Plugin\Company\Model\Customer\CompanyPlugin class.
 */
class CompanyPluginTest extends TestCase
{
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
     * @var CreditLimitInterfaceFactory|MockObject
     */
    private $creditLimitFactory;

    /**
     * @var CompanyPlugin
     */
    private $companyPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
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
        $this->creditLimitFactory = $this
            ->getMockBuilder(CreditLimitInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->companyPlugin = $objectManager->getObject(
            CompanyPlugin::class,
            [
                'creditLimitRepository' => $this->creditLimitRepository,
                'creditLimitManagement' => $this->creditLimitManagement,
                'websiteRepository' => $this->websiteRepository,
                'creditLimitFactory' => $this->creditLimitFactory,
            ]
        );
    }

    /**
     * Test afterCreateCompany method.
     *
     * @return void
     */
    public function testAfterCreateCompany()
    {
        $companyId = 1;
        $baseCurrencyCode = 'USD';
        $subject = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getId')->willReturn($companyId);
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $website = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $this->websiteRepository->expects($this->once())->method('getDefault')->willReturn($website);
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $creditLimit->expects($this->once())->method('setCurrencyCode')->with($baseCurrencyCode)->willReturnSelf();
        $this->creditLimitRepository->expects($this->once())
            ->method('save')->with($creditLimit)->willReturn($creditLimit);
        $this->assertEquals($company, $this->companyPlugin->afterCreateCompany($subject, $company));
    }

    /**
     * Test afterCreateCompany method with exception.
     *
     * @return void
     */
    public function testAfterCreateCompanyWithException()
    {
        $companyId = 1;
        $baseCurrencyCode = 'USD';
        $exception = new NoSuchEntityException();
        $subject = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $website = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->atLeastOnce())->method('getId')->willReturn($companyId);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willThrowException($exception);
        $this->creditLimitFactory->expects($this->once())->method('create')->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('setCompanyId')->with($companyId)->willReturnSelf();
        $this->websiteRepository->expects($this->once())->method('getDefault')->willReturn($website);
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $creditLimit->expects($this->once())->method('setCurrencyCode')->with($baseCurrencyCode)->willReturnSelf();
        $this->creditLimitRepository->expects($this->once())
            ->method('save')->with($creditLimit)->willReturn($creditLimit);

        $this->assertEquals($company, $this->companyPlugin->afterCreateCompany($subject, $company));
    }
}
