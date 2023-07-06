<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\CreditDetails;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Model\CreditDetails\CustomerProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerProviderTest extends TestCase
{
    /**
     * @var CreditDataProviderInterface|MockObject
     */
    private $creditDataProvider;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var CustomerProvider
     */
    private $customerProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditDataProvider = $this->createMock(
            CreditDataProviderInterface::class
        );
        $this->userContext = $this->createMock(
            UserContextInterface::class
        );
        $this->companyManagement = $this->createMock(
            CompanyManagementInterface::class
        );

        $objectManager = new ObjectManager($this);
        $this->customerProvider = $objectManager->getObject(
            CustomerProvider::class,
            [
                'creditDataProvider' => $this->creditDataProvider,
                'userContext' => $this->userContext,
                'companyManagement' => $this->companyManagement,
            ]
        );
    }

    /**
     * Test for method getCurrentUserCredit.
     *
     * @return void
     */
    public function testGetCurrentUserCredit()
    {
        $userId = 1;
        $companyId = 2;
        $this->userContext->expects($this->exactly(2))->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->once())
            ->method('getUserType')->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')->with($userId)->willReturn($company);
        $credit = $this->getMockForAbstractClass(CreditDataInterface::class);
        $company->expects($this->once())->method('getId')->willReturn($companyId);
        $this->creditDataProvider->expects($this->once())->method('get')->with($companyId)->willReturn($credit);
        $this->assertEquals($credit, $this->customerProvider->getCurrentUserCredit());
    }
}
