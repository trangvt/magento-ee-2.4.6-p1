<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\CompanyPayment\Test\Unit\Model\Payment\Checks;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyPayment\Model\Payment\AvailabilityChecker;
use Magento\CompanyPayment\Model\Payment\Checks\CanUseForCompany;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CanUseForCompanyTest extends TestCase
{
    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var AvailabilityChecker|MockObject
     */
    private $availabilityChecker;

    /**
     * @var CanUseForCompany
     */
    private $canUseForCompany;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->companyManagement = $this->getMockForAbstractClass(CompanyManagementInterface::class);
        $this->availabilityChecker =
            $this->createMock(AvailabilityChecker::class);

        $objectManager = new ObjectManager($this);
        $this->canUseForCompany = $objectManager->getObject(
            CanUseForCompany::class,
            [
                'companyManagement' => $this->companyManagement,
                'availabilityChecker' => $this->availabilityChecker,
            ]
        );
    }

    /**
     * Test isApplicable.
     *
     * @param MethodInterface $paymentMethod
     * @param int $customerId
     * @param CompanyInterface|null $company
     * @param bool $result
     * @dataProvider dataProviderIsApplicable
     */
    public function testIsApplicable(
        MethodInterface $paymentMethod,
        $customerId,
        $company,
        $result
    ) {
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->atLeastOnce())->method('getCustomerId')->willReturn($customerId);
        if ($customerId) {
            $this->companyManagement->expects($this->once())->method('getByCustomerId')->willReturn($company);
        }
        if ($company) {
            $this->availabilityChecker->expects($this->once())
                ->method('isAvailableForCompany')
                ->with('testCode', $company)
                ->willReturn($result);
        }

        $this->assertEquals($result, $this->canUseForCompany->isApplicable($paymentMethod, $quote));
    }

    /**
     * Dataprovider for isApplicable.
     *
     * @return array
     */
    public function dataProviderIsApplicable()
    {
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        return [
            [$this->getMockForAbstractClass(MethodInterface::class), null, null, true],
            [$this->getMockForAbstractClass(MethodInterface::class), 1, null, true],
            [$this->getPaymentMethodMock(), 1, $company, true],
            [$this->getPaymentMethodMock(), 1, $company, false],
        ];
    }

    /**
     * Get payment method mock.
     *
     * @return MockObject
     */
    private function getPaymentMethodMock()
    {
        $paymentMethod = $this->getMockBuilder(MethodInterface::class)
            ->setMethods(['getCode'])
            ->getMockForAbstractClass();
        $paymentMethod->expects($this->once())->method('getCode')->willReturn('testCode');

        return $paymentMethod;
    }
}
