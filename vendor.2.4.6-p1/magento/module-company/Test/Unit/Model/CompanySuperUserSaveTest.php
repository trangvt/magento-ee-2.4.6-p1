<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Action\Company\ReplaceSuperUser;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanySuperUserSave;
use Magento\Company\Model\Customer\CompanyAttributes;
use Magento\Company\Model\Email\Sender;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for model CompanySuperUserSave.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanySuperUserSaveTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var AccountManagementInterface|MockObject
     */
    private $customerManagerMock;

    /**
     * @var Sender|MockObject
     */
    private $companyEmailSenderMock;

    /**
     * @var Structure|MockObject
     */
    private $companyStructureMock;

    /**
     * @var CompanyAttributes|MockObject
     */
    private $companyAttributesMock;

    /**
     * @var CompanySuperUserSave
     */
    private $companySuperUserSave;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var CustomerInterface|MockObject
     */
    private $oldCustomer;

    /**
     * @var ReplaceSuperUser|MockObject
     */
    private $replaceSuperUserMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerManagerMock = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyEmailSenderMock = $this->getMockBuilder(Sender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyStructureMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyAttributesMock = $this->getMockBuilder(CompanyAttributes::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->replaceSuperUserMock =
            $this->getMockBuilder(ReplaceSuperUser::class)
                ->disableOriginalConstructor()
                ->getMock();

        $objectManager = new ObjectManager($this);
        $this->companySuperUserSave = $objectManager->getObject(
            CompanySuperUserSave::class,
            [
                'companyAttributes'  => $this->companyAttributesMock,
                'companyStructure'   => $this->companyStructureMock,
                'customerRepository' => $this->customerRepositoryMock,
                'customerManager'    => $this->customerManagerMock,
                'companyEmailSender' => $this->companyEmailSenderMock,
                'replaceSuperUser'   => $this->replaceSuperUserMock,
            ]
        );
    }

    /**
     * Test for saveCustomer() method.
     *
     * @param int $keepActive
     * @param int $companyStatus
     * @param string $callback
     * @dataProvider saveCustomerDataProvider
     * @return void
     */
    public function testSaveCustomer($keepActive, $companyStatus, $callback)
    {
        $customerId = 17;
        $oldSuperUserId = 18;

        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->replaceSuperUserMock->expects($this->once())->method('execute')->with(
            $this->customer,
            $oldSuperUserId,
            $keepActive
        )->willReturnSelf();

        $this->customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->customerRepositoryMock->expects($this->atLeastOnce())->method('save')->willReturn($this->customer);
        $this->oldCustomer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->oldCustomer->expects($this->atLeastOnce())->method('getId')->willReturn($oldSuperUserId);

        switch ($callback) {
            case 'sendEmailsKeepActiveConfigure':
                $this->sendEmailsKeepActiveConfigure();
                break;
            case 'sendEmailsNotKeepActiveConfigure':
                $this->sendEmailsNotKeepActiveConfigure();
                break;
        }
        $this->companySuperUserSave->saveCustomer($this->customer, $this->oldCustomer, $companyStatus, $keepActive);
    }

    /**
     * Test for saveCustomer() method with NoSuchEntityException.
     *
     * @return void
     */
    public function testSaveCustomerWithNoSuchEntityException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $customerId = 17;
        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->customerRepositoryMock->expects($this->once())->method('save')->willReturn($this->customer);

        $this->companySuperUserSave->saveCustomer(
            $this->customer,
            null,
            CompanyInterface::STATUS_APPROVED
        );
    }

    /**
     * Data provider for saveCustomer method.
     *
     * @return array
     */
    public function saveCustomerDataProvider()
    {
        return [
            [1, null, 'simpleConfigure'],
            [1, CompanyInterface::STATUS_APPROVED, 'sendEmailsKeepActiveConfigure'],
            [0, CompanyInterface::STATUS_APPROVED, 'sendEmailsNotKeepActiveConfigure'],
        ];
    }

    /**
     * Additional configuration for Save with send emails.
     *
     * @return void
     */
    private function sendEmailsKeepActiveConfigure()
    {
        $companyId = 33;
        $customerAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyAttributesMock->expects($this->atLeastOnce())
            ->method('getCompanyAttributesByCustomer')->willReturn($customerAttributes);
        $customerAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $this->customerRepositoryMock->expects($this->atLeastOnce())
            ->method('getById')->willReturn($this->oldCustomer);

        $this->companyEmailSenderMock->expects($this->once())->method('sendRemoveSuperUserNotificationEmail');
    }

    /**
     * Additional configuration for Save with send emails.
     *
     * @return void
     */
    private function sendEmailsNotKeepActiveConfigure()
    {
        $companyId = 33;
        $customerAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyAttributesMock->expects($this->atLeastOnce())
            ->method('getCompanyAttributesByCustomer')->willReturn($customerAttributes);
        $customerAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $this->customerRepositoryMock->expects($this->atLeastOnce())
            ->method('getById')->willReturn($this->oldCustomer);

        $this->companyEmailSenderMock->expects($this->once())->method('sendInactivateSuperUserNotificationEmail');
    }
}
