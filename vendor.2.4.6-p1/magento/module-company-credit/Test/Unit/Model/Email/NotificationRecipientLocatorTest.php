<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\Email;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\Email\NotificationRecipientLocator;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NotificationRecipientLocatorTest extends TestCase
{
    /**
     * @var CreditLimitRepositoryInterface|MockObject
     */
    private $creditLimitRepository;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var NotificationRecipientLocator
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimitRepository = $this->createMock(
            CreditLimitRepositoryInterface::class
        );
        $this->companyManagement = $this->createMock(
            CompanyManagementInterface::class
        );
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            NotificationRecipientLocator::class,
            [
                'creditLimitRepository' => $this->creditLimitRepository,
                'companyManagement' => $this->companyManagement,
            ]
        );
    }

    /**
     * Test getByRecord method.
     *
     * @return void
     */
    public function testGetByRecord()
    {
        $companyCreditId = 1;
        $companyId = 1;
        $history = $this->createMock(
            HistoryInterface::class
        );
        $creditLimit = $this->createMock(
            CreditLimitInterface::class
        );
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $history->expects($this->once())->method('getCompanyCreditId')->willReturn($companyCreditId);
        $this->creditLimitRepository->expects($this->once())
            ->method('get')
            ->with($companyCreditId)
            ->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->companyManagement->expects($this->once())
            ->method('getAdminByCompanyId')
            ->with($companyId)
            ->willReturn($customer);

        $this->assertEquals($customer, $this->model->getByRecord($history));
    }
}
