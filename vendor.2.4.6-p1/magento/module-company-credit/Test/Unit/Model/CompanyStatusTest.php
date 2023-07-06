<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Model\CompanyStatus;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompanyStatusTest extends TestCase
{
    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var CompanyStatus
     */
    private $companyStatus;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );

        $objectManager = new ObjectManager($this);
        $this->companyStatus = $objectManager->getObject(
            CompanyStatus::class,
            [
                'companyRepository' => $this->companyRepository,
            ]
        );
    }

    /**
     * Test for method isRefundAvailable.
     *
     * @return void
     */
    public function testIsRefundAvailable()
    {
        $companyId = 1;
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())
            ->method('getStatus')->willReturn(CompanyInterface::STATUS_APPROVED);
        $this->assertTrue($this->companyStatus->isRefundAvailable($companyId));
    }

    /**
     * Test for method isRefundAvailable with rejected company.
     *
     * @return void
     */
    public function testIsRefundAvailableWithRejectedCompany()
    {
        $companyId = 1;
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())
            ->method('getStatus')->willReturn(CompanyInterface::STATUS_REJECTED);
        $this->assertFalse($this->companyStatus->isRefundAvailable($companyId));
    }

    /**
     * Test for method isRefundAvailable without company.
     *
     * @return void
     */
    public function testIsRefundAvailableWithoutCompany()
    {
        $companyId = 1;
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)
            ->willThrowException(new NoSuchEntityException());
        $this->assertFalse($this->companyStatus->isRefundAvailable($companyId));
    }

    /**
     * Test for method isRevertAvailable.
     *
     * @return void
     */
    public function testIsRevertAvailable()
    {
        $companyId = 1;
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())
            ->method('getStatus')->willReturn(CompanyInterface::STATUS_APPROVED);
        $this->assertTrue($this->companyStatus->isRevertAvailable($companyId));
    }
}
