<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\CreditDataFactory;
use Magento\CompanyCredit\Model\CreditDataProvider as SystemUnderTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditDataProviderTest extends TestCase
{
    /**
     * @var CreditLimitManagementInterface|MockObject
     */
    private $creditLimitManagement;

    /**
     * @var CreditDataFactory|MockObject
     */
    private $creditDataFactory;

    /**
     * @var SystemUnderTest
     */
    private $creditDataProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimitManagement = $this->createMock(
            CreditLimitManagementInterface::class
        );
        $this->creditDataFactory = $this->createPartialMock(
            CreditDataFactory::class,
            ['create']
        );

        $objectManager = new ObjectManager($this);
        $this->creditDataProvider = $objectManager->getObject(
            SystemUnderTest::class,
            [
                'creditLimitManagement' => $this->creditLimitManagement,
                'creditDataFactory' => $this->creditDataFactory,
            ]
        );
    }

    /**
     * Test for get method.
     *
     * @return void
     */
    public function testGet()
    {
        $companyId = 1;
        $creditLimit = $this->getMockForAbstractClass(CreditLimitInterface::class);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $creditData = $this->getMockForAbstractClass(CreditDataInterface::class);
        $this->creditDataFactory->expects($this->once())
            ->method('create')->with(['credit' => $creditLimit])->willReturn($creditData);
        $this->assertEquals($creditData, $this->creditDataProvider->get($companyId));
    }
}
