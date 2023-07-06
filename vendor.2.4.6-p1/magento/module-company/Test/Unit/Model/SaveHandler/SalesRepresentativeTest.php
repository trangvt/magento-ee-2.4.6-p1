<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveHandler;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Email\Sender;
use Magento\Company\Model\SaveHandler\SalesRepresentative;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Company/Model/SaveHandler/SalesRepresentative model.
 */
class SalesRepresentativeTest extends TestCase
{
    /**
     * @var Sender|MockObject
     */
    private $companyEmailSender;

    /**
     * @var SalesRepresentative
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyEmailSender = $this->createMock(
            Sender::class
        );
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            SalesRepresentative::class,
            [
                'companyEmailSender' => $this->companyEmailSender,
            ]
        );
    }

    /**
     * Test for execute() method.
     *
     * @return void
     */
    public function testExecute()
    {
        $company = $this
            ->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSalesRepresentativeId'])
            ->getMockForAbstractClass();
        $initialCompany = $this
            ->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSalesRepresentativeId'])
            ->getMockForAbstractClass();
        $initialCompany->expects($this->once())->method('getSalesRepresentativeId')->willReturn(1);
        $company->expects($this->atLeastOnce())->method('getSalesRepresentativeId')->willReturn(2);
        $this->companyEmailSender->expects($this->once())
            ->method('sendSalesRepresentativeNotificationEmail')
            ->willReturnSelf();
        $this->model->execute($company, $initialCompany);
    }

    /**
     * Test for execute() method if sales representatives IDs of company and initial company are equal.
     *
     * @return void
     */
    public function testExecuteIfSalesRepresentativesIdsEqual()
    {
        $salesRepresentativeId = 1;
        $company = $this
            ->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSalesRepresentativeId'])
            ->getMockForAbstractClass();
        $initialCompany = $this
            ->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSalesRepresentativeId'])
            ->getMockForAbstractClass();
        $initialCompany->expects($this->atLeastOnce())->method('getSalesRepresentativeId')
            ->willReturn($salesRepresentativeId);
        $company->expects($this->atLeastOnce())->method('getSalesRepresentativeId')
            ->willReturn($salesRepresentativeId);
        $this->companyEmailSender->expects($this->never())
            ->method('sendSalesRepresentativeNotificationEmail')
            ->willReturnSelf();
        $this->model->execute($company, $initialCompany);
    }
}
