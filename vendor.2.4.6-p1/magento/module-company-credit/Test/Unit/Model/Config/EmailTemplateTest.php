<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\Config;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Model\Config\EmailTemplate;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailTemplateTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var EmailTemplate
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(
            StoreManagerInterface::class
        );
        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );
        $this->scopeConfig = $this->createMock(
            ScopeConfigInterface::class
        );
        $this->logger = $this->createMock(
            LoggerInterface::class
        );
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            EmailTemplate::class,
            [
                'storeManager' => $this->storeManager,
                'companyRepository' => $this->companyRepository,
                'scopeConfig' => $this->scopeConfig,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Test getCreditChangeCopyTo method.
     *
     * @return void
     */
    public function testGetCreditChangeCopyTo()
    {
        $copyToEmail = 'email@example.com';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('company/email/company_credit_change_copy', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($copyToEmail);
        $this->assertEquals($copyToEmail, $this->model->getCreditChangeCopyTo());
    }

    /**
     * Test getSenderByStoreId method.
     *
     * @return void
     */
    public function testGetSenderByStoreId()
    {
        $storeId = 1;
        $sender = 'general';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('company/email/company_credit_change', ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn($sender);
        $this->assertEquals($sender, $this->model->getSenderByStoreId($storeId));
    }

    /**
     * Test getCreditCreateCopyMethod method.
     *
     * @return void
     */
    public function testGetCreditCreateCopyMethod()
    {
        $copyMethod = 'bcc';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('company/email/company_credit_copy_method', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($copyMethod);
        $this->assertEquals($copyMethod, $this->model->getCreditCreateCopyMethod());
    }

    /**
     * Test getDefaultStoreId method.
     *
     * @return void
     */
    public function testGetDefaultStoreId()
    {
        $websiteId = 1;
        $storeIds = [1, 2];
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $website = $this->getMockForAbstractClass(
            WebsiteInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getStoreIds']
        );
        $customer->expects($this->exactly(2))->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManager->expects($this->once())->method('getWebsite')->with($websiteId)->willReturn($website);
        $website->expects($this->once())->method('getStoreIds')->willReturn($storeIds);

        $this->assertEquals($storeIds[0], $this->model->getDefaultStoreId($customer));
    }

    /**
     * Test getTemplateId method.
     *
     * @param int $historyStatus
     * @param $counter
     * @param string|null $templatePath
     * @param string $expectedResult
     * @return void
     * @dataProvider getTemplateIdDataProvider
     */
    public function testGetTemplateId(
        $historyStatus,
        $counter,
        $templatePath,
        $expectedResult
    ) {
        $storeId = 1;
        $this->scopeConfig->expects($counter)
            ->method('getValue')
            ->with($templatePath, ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn('company_email_credit_allocated_email_template');
        $this->assertEquals($expectedResult, $this->model->getTemplateId($historyStatus, $storeId));
    }

    /**
     * Data provider for getTemplateId method.
     *
     * @return array
     */
    public function getTemplateIdDataProvider()
    {
        return [
            [
                1,
                $this->once(),
                'company/email/credit_allocated_email_template',
                'company_email_credit_allocated_email_template'
            ],
            [
                3,
                $this->never(),
                null,
                ''
            ]
        ];
    }

    /**
     * Test canSendNotification method.
     *
     * @return void
     */
    public function testCanSendNotification()
    {
        $companyId = 1;
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCompanyAttributes']
        );
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $company = $this->createMock(
            CompanyInterface::class
        );
        $customer->expects($this->once())->method('getExtensionAttributes')->willReturn($customerExtension);
        $customerExtension->expects($this->once())->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())
            ->method('getStatus')
            ->willReturn(CompanyInterface::STATUS_APPROVED);

        $this->assertTrue($this->model->canSendNotification($customer));
    }

    /**
     * Test canSendNotification method throws exception.
     *
     * @return void
     */
    public function testCanSendNotificationWithException()
    {
        $companyId = 1;
        $exception = new NoSuchEntityException();
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCompanyAttributes']
        );
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $customer->expects($this->once())->method('getExtensionAttributes')->willReturn($customerExtension);
        $customerExtension->expects($this->once())->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with($companyId)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->willReturnSelf();

        $this->assertFalse($this->model->canSendNotification($customer));
    }
}
