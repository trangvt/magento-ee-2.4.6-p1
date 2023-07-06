<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\CreditLimitFactory;
use Magento\CompanyCredit\Model\CreditLimitManagement;
use Magento\CompanyCredit\Model\ResourceModel\CreditLimit;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CreditLimitManagement model.
 */
class CreditLimitManagementTest extends TestCase
{
    /**
     * @var CreditLimitFactory|MockObject
     */
    private $creditLimitFactory;

    /**
     * @var CreditLimit|MockObject
     */
    private $creditLimitResource;

    /**
     * @var CreditLimitManagement
     */
    private $creditLimitManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimitFactory = $this->getMockBuilder(CreditLimitFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditLimitResource = $this
            ->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->creditLimitManagement = $objectManager->getObject(
            CreditLimitManagement::class,
            [
                'creditLimitFactory' => $this->creditLimitFactory,
                'creditLimitResource' => $this->creditLimitResource,
            ]
        );
    }

    /**
     * Test for method getCreditByCompanyId.
     *
     * @return void
     */
    public function testGetCreditByCompanyId()
    {
        $creditLimitId = 1;
        $companyId = 2;
        $creditLimit = $this->getMockBuilder(\Magento\CompanyCredit\Model\CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditLimitFactory->expects($this->once())->method('create')->willReturn($creditLimit);
        $this->creditLimitResource->expects($this->once())->method('load')
            ->with($creditLimit, $companyId, CreditLimitInterface::COMPANY_ID)
            ->willReturnSelf();
        $creditLimit->expects($this->once())->method('getId')->willReturn($creditLimitId);
        $this->assertEquals($creditLimit, $this->creditLimitManagement->getCreditByCompanyId($companyId));
    }

    /**
     * Test for method getCreditByCompanyId with exception.
     *
     * @return void
     */
    public function testGetCreditByCompanyIdWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('Requested company is not found. Row ID: CompanyID = 2.');
        $companyId = 2;
        $creditLimit = $this->getMockBuilder(\Magento\CompanyCredit\Model\CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditLimitFactory->expects($this->once())->method('create')->willReturn($creditLimit);
        $this->creditLimitResource->expects($this->once())->method('load')
            ->with($creditLimit, $companyId, CreditLimitInterface::COMPANY_ID)
            ->willReturnSelf();
        $creditLimit->expects($this->once())->method('getId')->willReturn(null);
        $this->creditLimitManagement->getCreditByCompanyId($companyId);
    }
}
