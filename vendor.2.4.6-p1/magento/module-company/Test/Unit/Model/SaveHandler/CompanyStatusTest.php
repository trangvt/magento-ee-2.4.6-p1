<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveHandler;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanyManagement;
use Magento\Company\Model\Email\Sender;
use Magento\Company\Model\ResourceModel\Company;
use Magento\Company\Model\SaveHandler\CompanyStatus;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\SaveHandler\CompanyStatus class.
 */
class CompanyStatusTest extends TestCase
{
    /**
     * @var Sender|MockObject
     */
    private $companyEmailSender;

    /**
     * @var CompanyManagement|MockObject
     */
    private $companyManagement;

    /**
     * @var Company|MockObject
     */
    private $companyResource;

    /**
     * @var DateTime|MockObject
     */
    private $date;

    /**
     * @var CompanyStatus
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyEmailSender = $this->getMockBuilder(Sender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyManagement = $this->getMockBuilder(CompanyManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyResource = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->date = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            CompanyStatus::class,
            [
                'companyEmailSender' => $this->companyEmailSender,
                'companyManagement' => $this->companyManagement,
                'companyResource' => $this->companyResource,
                'date' => $this->date
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @param int $oldStatus
     * @param int $newStatus
     * @param string $template
     * @return void
     * @dataProvider dataProviderExecute
     */
    public function testExecute($oldStatus, $newStatus, $template)
    {
        $date = '2016-07-08 17:03:43';
        $company = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $initialCompany = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $initialCompany->expects($this->atLeastOnce())->method('getStatus')->willReturn($oldStatus);
        $company->expects($this->atLeastOnce())->method('getStatus')->willReturn($newStatus);
        $company->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->companyManagement->expects($this->once())->method('getAdminByCompanyId')->willReturn($customer);
        $this->companyEmailSender->expects($this->once())
            ->method('sendCompanyStatusChangeNotificationEmail')
            ->with($customer, 1, $template)
            ->willReturnSelf();
        if ($newStatus == CompanyInterface::STATUS_REJECTED && $oldStatus != CompanyInterface::STATUS_REJECTED) {
            $this->date->expects($this->once())->method('gmtDate')->willReturn($date);
            $company->expects($this->once())->method('setRejectedAt')->with($date)->willReturnSelf();
            $this->companyResource->expects($this->once())->method('save')->with($company)->willReturn($company);
        }

        $this->model->execute($company, $initialCompany);
    }

    /**
     * Test execute method with non-existing status.
     *
     * @return void
     */
    public function testExecuteWithNonExistingStatus()
    {
        $company = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $initialCompany = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $initialCompany->expects($this->atLeastOnce())->method('getStatus')->willReturn(-1);
        $company->expects($this->atLeastOnce())->method('getStatus')->willReturn(-2);
        $this->companyEmailSender->expects($this->never())->method('sendCompanyStatusChangeNotificationEmail');
        $this->model->execute($company, $initialCompany);
    }

    /**
     * DataProvider for execute method.
     *
     * @return array
     */
    public function dataProviderExecute()
    {
        return [
            [
                CompanyInterface::STATUS_PENDING,
                CompanyInterface::STATUS_APPROVED,
                'company/email/company_status_pending_approval_to_active_template'
            ],
            [
                CompanyInterface::STATUS_REJECTED,
                CompanyInterface::STATUS_APPROVED,
                'company/email/company_status_rejected_blocked_to_active_template'
            ],
            [
                CompanyInterface::STATUS_BLOCKED,
                CompanyInterface::STATUS_APPROVED,
                'company/email/company_status_rejected_blocked_to_active_template'
            ],
            [
                CompanyInterface::STATUS_BLOCKED,
                CompanyInterface::STATUS_PENDING,
                'company/email/company_status_pending_approval_template'
            ],
            [
                CompanyInterface::STATUS_BLOCKED,
                CompanyInterface::STATUS_REJECTED,
                'company/email/company_status_rejected_template'
            ],
            [
                CompanyInterface::STATUS_PENDING,
                CompanyInterface::STATUS_BLOCKED,
                'company/email/company_status_blocked_template'
            ]
        ];
    }
}
