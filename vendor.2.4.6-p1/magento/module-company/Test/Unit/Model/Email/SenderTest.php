<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Email;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Config\EmailTemplate;
use Magento\Company\Model\Email\CustomerData;
use Magento\Company\Model\Email\Sender;
use Magento\Company\Model\Email\Transporter;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Company/Model/Email/Sender model.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SenderTest extends TestCase
{
    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var DataObjectProcessor|MockObject
     */
    private $dataProcessor;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerViewHelper;

    /**
     * @var CustomerData|MockObject
     */
    private $customerData;

    /**
     * @var EmailTemplate|MockObject
     */
    private $emailTemplateConfig;

    /**
     * @var Transporter|MockObject
     */
    private $transporter;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var Website|MockObject
     */
    private $website;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->website = $this->getMockBuilder(Website::class)
            ->setMethods(['getStoreIds'])->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->website);

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dataProcessor = $this->getMockBuilder(DataObjectProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailTemplateConfig = $this->getMockBuilder(EmailTemplate::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataProcessor->expects($this->any())
            ->method('buildOutputDataArray')->willReturn([]);
        $this->customerViewHelper = $this->getMockBuilder(CustomerNameGenerationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->transporter = $this->getMockBuilder(Transporter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $companyModel = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getName', 'getSalesRepresentativeId'])
            ->getMockForAbstractClass();
        $companyModel->expects($this->any())->method('getName')->willReturn('Company Name');
        $companyModel->expects($this->any())->method('getSalesRepresentativeId')->willReturn(1);

        $this->customerData = $this->getMockBuilder(CustomerData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerData = new DataObject(['email' => 'example@example.com', 'name' => 'test']);
        $this->customerData->expects($this->any())->method('getDataObjectByCustomer')->willReturn($customerData);
        $this->customerData->expects($this->any())->method('getDataObjectSuperUser')->willReturn($customerData);

        $salesRepData = new DataObject(['email' => 'salesrep@example.com', 'name' => 'test']);
        $this->customerData->expects($this->any())
            ->method('getDataObjectSalesRepresentative')->willReturn($salesRepData);

        $objectManagerHelper = new ObjectManager($this);
        $this->sender = $objectManagerHelper->getObject(
            Sender::class,
            [
                'storeManager' => $this->storeManager,
                'scopeConfig' => $this->scopeConfig,
                'transporter' => $this->transporter,
                'customerViewHelper' => $this->customerViewHelper,
                'customerData' => $this->customerData,
                'emailTemplateConfig' => $this->emailTemplateConfig,
                'companyRepository' => $this->companyRepository
            ]
        );
    }

    /**
     * Test sendAssignSuperUserNotificationEmail.
     *
     * @return void
     */
    public function testSendAssignSuperUserNotificationEmail()
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getStoreId', 'getWebsiteId', 'getName', 'getEmail', 'load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->any())->method('getStoreId')->willReturn(0);
        $customer->expects($this->any())->method('getWebsiteId')->willReturn(2);
        $customer->expects($this->any())->method('getEmail')->willReturn('example@example.com');
        $this->website->method('getStoreIds')
            ->willReturn([1, 2, 3]);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository->expects($this->atLeastOnce())
            ->method('get')
            ->willReturn($company);

        $this->customerViewHelper->expects($this->any())->method('getCustomerName')->willReturn('test');
        $this->transporter->expects($this->atLeastOnce())->method('sendMessage')->withConsecutive(
            ['salesrep@example.com', 'test'],
            ['example@example.com', 'test']
        );

        $this->assertEquals($this->sender, $this->sender->sendAssignSuperUserNotificationEmail($customer, 1));
    }

    /**
     * Test sendSalesRepresentativeNotificationEmail.
     *
     * @return void
     */
    public function testSendSalesRepresentativeNotificationEmail()
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getStoreId', 'getWebsiteId', 'getName', 'getEmail', 'load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->any())->method('getStoreId')->willReturn(0);
        $customer->expects($this->any())->method('getWebsiteId')->willReturn(2);
        $customer->expects($this->any())->method('getName')->willReturn('test');
        $customer->expects($this->any())->method('getEmail')->willReturn('salesrep@example.com');
        $customer->expects($this->any())->method('load')->willReturnSelf();
        $this->transporter->expects($this->once())->method('sendMessage')->with('salesrep@example.com', 'test');

        $this->assertEquals($this->sender, $this->sender->sendSalesRepresentativeNotificationEmail(1, 2));
    }

    /**
     * Test sendCustomerCompanyAssignNotificationEmail
     *
     * @return void
     */
    public function testSendCustomerCompanyAssignNotificationEmail()
    {
        $storeId = 1;
        $email = 'example@example.com';
        $name = 'customer name';
        $sender = ['email' => 'owner@example.com', 'name' => 'owner name'];
        $templateId = 'companyCreateNotifyAdminTemplateId';

        /** @var CustomerInterface|MockObject $customer */
        $customer = $this->createMock(CustomerInterface::class);
        $this->customerViewHelper->method('getCustomerName')->with($customer)->willReturn($name);
        $customer->method('getStoreId')->willReturn($storeId);
        $customer->method('getEmail')->willReturn($email);
        /** @var DataObject $customerData */
        $customerData = $this->customerData->getDataObjectByCustomer($customer);
        $customerData->setData('companyAdminEmail', $customerData->getEmail());
        $this->emailTemplateConfig->method('getCompanyCustomerAssignUserTemplateId')
            ->with('store', $storeId)
            ->willReturn($templateId);
        $this->scopeConfig->method('getValue')
            ->with('customer/create_account/email_identity', 'store', $storeId)
            ->willReturn($sender);
        $this->transporter->expects($this->once())
            ->method('sendMessage')
            ->with($email, $name, $sender, $templateId, ['customer' => $customerData], $storeId);

        $this->assertEquals($this->sender, $this->sender->sendCustomerCompanyAssignNotificationEmail($customer, 2));
    }

    /**
     * Test sendRemoveSuperUserNotificationEmail.
     *
     * @return void
     */
    public function testSendRemoveSuperUserNotificationEmail()
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getStoreId', 'getWebsiteId', 'getName', 'getEmail', 'load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->once())->method('getStoreId')->willReturn(0);
        $customer->expects($this->exactly(2))->method('getWebsiteId')->willReturn(2);
        $customer->expects($this->once())->method('getEmail')->willReturn('example@example.com');
        $this->website->method('getStoreIds')
            ->willReturn([1, 2, 3]);
        $this->customerViewHelper->expects($this->once())->method('getCustomerName')->willReturn('test');
        $this->transporter->expects($this->once())->method('sendMessage')->with('example@example.com', 'test');

        $this->assertEquals($this->sender, $this->sender->sendRemoveSuperUserNotificationEmail($customer, 2));
    }

    /**
     * Test sendInactivateSuperUserNotificationEmail.
     *
     * @return void
     */
    public function testSendInactivateSuperUserNotificationEmail()
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getStoreId', 'getWebsiteId', 'getName', 'getEmail', 'load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->once())->method('getStoreId')->willReturn(0);
        $customer->expects($this->exactly(2))->method('getWebsiteId')->willReturn(2);
        $customer->expects($this->once())->method('getEmail')->willReturn('example@example.com');
        $this->website->method('getStoreIds')
            ->willReturn([1, 2, 3]);
        $this->customerViewHelper->expects($this->once())->method('getCustomerName')->willReturn('test');
        $this->transporter->expects($this->once())->method('sendMessage')->with('example@example.com', 'test');

        $this->assertEquals($this->sender, $this->sender->sendInactivateSuperUserNotificationEmail($customer, 2));
    }

    /**
     * Test sendCompanyStatusChangeNotificationEmail.
     *
     * @return void
     */
    public function testSendCompanyStatusChangeNotificationEmail()
    {
        $template = 'company/email/company_status_pending_approval_to_active_template';
        $customerEmailIdentity = 'customer/create_account/email_identity';
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getStoreId', 'getWebsiteId', 'getName', 'getEmail', 'load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->once())->method('getStoreId')->willReturn(0);
        $customer->expects($this->exactly(2))->method('getWebsiteId')->willReturn(2);
        $customer->expects($this->once())->method('getEmail')->willReturn('example@example.com');
        $this->website->method('getStoreIds')
            ->willReturn([1, 2, 3]);
        $this->scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [$template, 'store', 1],
                [$customerEmailIdentity, 'store', 1]
            )
            ->willReturnOnConsecutiveCalls(
                'template',
                'example@example.com'
            );
        $this->customerViewHelper->expects($this->exactly(1))->method('getCustomerName')->willReturn('test');
        $this->transporter->expects($this->exactly(1))->method('sendMessage');

        $this->assertEquals(
            $this->sender,
            $this->sender->sendCompanyStatusChangeNotificationEmail($customer, 2, $template)
        );
    }

    /**
     * Test sendAdminNotificationEmail.
     *
     * @return void
     */
    public function testSendAdminNotificationEmail()
    {
        $customer = $this->mockCustomer();
        $this->transporter->expects($this->once())->method('sendMessage');

        $this->assertEquals(
            $this->sender,
            $this->sender->sendAdminNotificationEmail($customer, 'Test Company', 'http://example.com')
        );
    }

    /**
     * Tests sendAdminNotificationEmail method will send email with appropriate Customer's Store Id.
     *
     * @param int $customerStoreId
     * @param int $expectedStoreId
     * @return void
     * @dataProvider expectedStoreIdsForSendMessageDependingOnCustomerStoreIdProvider
     */
    public function testSendAdminNotificationEmailWillSendMessageWithAppropriateCustomerStoreId(
        int $customerStoreId,
        int $expectedStoreId
    ): void {
        $customer = $this->mockCustomer();
        $customer->method('getStoreId')
            ->willReturn($customerStoreId);
        $this->transporter->expects($this->once())
            ->method('sendMessage')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $expectedStoreId
            );

        $this->assertEquals(
            $this->sender,
            $this->sender->sendAdminNotificationEmail(
                $customer,
                'Test Company',
                'http://example.com'
            )
        );
    }

    /**
     * Expected Store Ids for Transport send message calls
     *
     * @return array
     */
    public function expectedStoreIdsForSendMessageDependingOnCustomerStoreIdProvider(): array
    {
        return [
            'customer_store_id_1' => [
                'customer_store_id' => 1,
                'expected_store_id' => 1,
            ],
            'customer_store_id_2' => [
                'customer_store_id' => 2,
                'expected_store_id' => 2,
            ],
        ];
    }

    /**
     * Tests sendAdminNotificationEmail method will send email with appropriate Store Id when Customer has not Store Id.
     *
     * @param int $customerWebsiteId
     * @param int[] $websiteStoreIds
     * @param int $expectedStoreId
     * @return void
     * @dataProvider expectedStoreIdsForSendMessageWhenCustomerHasNotStoreIdProvider
     */
    public function testSendAdminNotificationEmailWillSendMessageWithAppropriateStoreIdWhenCustomerHasNotStoreId(
        int $customerWebsiteId,
        array $websiteStoreIds,
        int $expectedStoreId
    ): void {
        $customer = $this->mockCustomer();
        $customer->method('getStoreId')
            ->willReturn(0);
        $customer->method('getWebsiteId')
            ->willReturn($customerWebsiteId);

        $this->website->method('getStoreIds')
            ->willReturn($websiteStoreIds);

        $this->transporter->expects($this->once())
            ->method('sendMessage')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $expectedStoreId
            );

        $this->assertEquals(
            $this->sender,
            $this->sender->sendAdminNotificationEmail(
                $customer,
                'Test Company',
                'http://example.com'
            )
        );
    }

    /**
     * Expected Store ids for Send Message calls when Customer has not Store Id.
     *
     * @return array
     */
    public function expectedStoreIdsForSendMessageWhenCustomerHasNotStoreIdProvider(): array
    {
        return [
            'no_website_id_provided' => [
                'customer_website_id' => 0,
                'website_store_ids' => [],
                'expected_store_id' => Store::DEFAULT_STORE_ID,
            ],
            'website_id_provided' => [
                'customer_website_id' => 2,
                'website_store_ids' => [3, 4, 5],
                'expected_store_id' => 3,
            ],
        ];
    }

    /**
     * Mock customer.
     *
     * @return CustomerInterface
     */
    private function mockCustomer()
    {
        $pathFirst = 'trans_email/ident_/email';
        $pathSecond = 'trans_email/ident_/name';
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scopeConfig->method('getValue')
            ->withConsecutive([$pathFirst], [$pathSecond])
            ->willReturnOnConsecutiveCalls('example@example.com', 'test1 test2');
        $this->emailTemplateConfig->expects($this->once())->method('getCompanyCreateNotifyAdminTemplateId');
        $customer->method('getEmail')->willReturn('example@example.com');
        $this->customerViewHelper->method('getCustomerName')->willReturn('test1 test2');
        $customer->method('getFirstname')->willReturn('test1');

        return $customer;
    }

    /**
     * Test sendUserStatusChangeNotificationEmail.
     *
     * @param int $customerStatus
     * @param string $method
     * @param string $template
     * @dataProvider sendUserStatusChangeNotificationEmailDataProvider
     * @return void
     */
    public function testSendUserStatusChangeNotificationEmail($customerStatus, $method, $template)
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getStoreId', 'getWebsiteId', 'getName', 'getEmail', 'load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->emailTemplateConfig->expects($this->once())->method($method)->willReturn($template);
        $customer->expects($this->once())->method('getEmail')->willReturn('example@example.com');
        $this->customerViewHelper->expects($this->once())->method('getCustomerName')->willReturn('test1 test2');
        $this->transporter->expects($this->once())->method('sendMessage')->with('example@example.com', 'test1 test2');
        $this->sender->sendUserStatusChangeNotificationEmail($customer, $customerStatus);
    }

    /**
     * Data provider for testSendUserStatusChangeNotificationEmail.
     *
     * @return array
     */
    public function sendUserStatusChangeNotificationEmailDataProvider()
    {
        return [
            [1, 'getActivateCustomerTemplateId', 'customer/customer_change_status/email_activate_customer_template'],
            [0, 'getInactivateCustomerTemplateId', 'customer/customer_change_status/email_lock_customer_template']
        ];
    }
}
