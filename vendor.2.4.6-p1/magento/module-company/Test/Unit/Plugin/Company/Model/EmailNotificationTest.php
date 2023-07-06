<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Company\Model;

use Magento\Backend\Model\UrlInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company;
use Magento\Company\Model\Company\Save;
use Magento\Company\Model\Email\Sender;
use Magento\Company\Plugin\Company\Model\EmailNotification;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Email notification plugin which
 * notify customer withe emails after create company account through API
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailNotificationTest extends TestCase
{
    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var Sender|MockObject
     */
    private $companyEmailSender;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Save|MockObject
     */
    private $companySave;

    /**
     * @var EmailNotification|MockObject
     */
    private $emailNotificationPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyEmailSender = $this->getMockBuilder(Sender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companySave = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->emailNotificationPlugin = $objectManagerHelper->getObject(
            EmailNotification::class,
            [
                'companyEmailSender' => $this->companyEmailSender,
                'urlBuilder' => $this->urlBuilder,
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    /**
     * Test afterSave.
     *
     * @param array $company
     * @param array $customerData
     * @param bool $shouldSendEmailNotification
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @dataProvider dataProvider
     */
    public function testAfterSave(array $company, array $customerData, bool $shouldSendEmailNotification): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->company = $objectManagerHelper->getObject(
            Company::class,
            [
                'data' => $company
            ]
        );
        $this->customer = $objectManagerHelper->getObject(
            Customer::class,
            [
                'data' => $customerData
            ]
        );
        $this->customerRepository
            ->expects($this->exactly((int)$shouldSendEmailNotification))
            ->method('getById')
            ->willReturn($this->customer);
        if (!empty($company)) {
            $this->urlBuilder
                ->expects($this->exactly((int)$shouldSendEmailNotification))
                ->method('getUrl')
                ->willReturn($company['company_url']);
            $this->companyEmailSender
                ->expects($this->exactly((int)$shouldSendEmailNotification))
                ->method('sendAdminNotificationEmail')
                ->willReturn(true);
        }
        $this->assertEquals(
            $this->company,
            $this->emailNotificationPlugin
                ->afterSave(
                    $this->companySave,
                    $this->company
                )
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'test with valid new company and customer data' => [
                    'company' => [
                        'company_name' => 'Test Company',
                        'company_email' => 'test+admin@test.com',
                        'company_url' => 'company/index/edit/1',
                        'street' => [ '5 Fifth Avenue' ],
                        'city' => 'Sidney',
                        'country_id' => 'AU',
                        'region' => 'NSW',
                        'region_id' => 570,
                        'postcode' => 2000,
                        'telephone' => 2323232,
                        'super_user_id' => 5,
                        'customer_group_id' => 1
                    ],
                    'customerData' => [
                        'id' => 5,
                        'firstname' => 'First Name',
                        'lastname' => 'Last Name',
                        'email' => 'test+user@test.com',
                        'dob' => '01/01/1987',
                        'gender' => 'male',
                        'website_id' => 1,
                        'store_id' => 1,
                        'default_shipping' => 'test street, test city, USA'
                    ],
                    'shouldSendEmailNotification' => true
                ],
            'test with valid existing company and customer data' => [
                    'company' => [
                        'company_name' => 'Test Company',
                        'company_email' => 'test+admin@test.com',
                        'company_url' => 'company/index/edit/1',
                        'entity_id' => 1,
                        'street' => [ '5 Fifth Avenue' ],
                        'city' => 'Sidney',
                        'country_id' => 'AU',
                        'region' => 'NSW',
                        'region_id' => 570,
                        'postcode' => 2000,
                        'telephone' => 2323232,
                        'super_user_id' => 5,
                        'customer_group_id' => 1
                    ],
                    'customerData' => [
                        'id' => 5,
                        'firstname' => 'First Name',
                        'lastname' => 'Last Name',
                        'email' => 'test+user@test.com',
                        'dob' => '01/01/1987',
                        'gender' => 'male',
                        'website_id' => 1,
                        'store_id' => 1,
                        'default_shipping' => 'test street, test city, USA'
                    ],
                    'shouldSendEmailNotification' => false
                ],
            'test with empty company and valid customer data' => [
                'company' => [],
                'customerData' => [
                    'id' => 5,
                    'firstname' => 'First Name',
                    'lastname' => 'Last Name',
                    'email' => 'test+user@test.com',
                    'dob' => '01/01/1987',
                    'gender' => 'male',
                    'website_id' => 1,
                    'store_id' => 1,
                    'default_shipping' => 'test street, test city, USA'
                ],
                'shouldSendEmailNotification' => true
            ],
            'test with empty company and empty customer data' => [
                'company' => [],
                'customerData' => [],
                'shouldSendEmailNotification' => true
            ],
        ];
    }
}
