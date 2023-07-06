<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\Payment\Checks;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\CompanyCredit\Model\Payment\Checks\HasCompanyPermission;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HasCompanyPermissionTest extends TestCase
{
    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var HasCompanyPermission
     */
    private $hasCompanyPermission;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->setMethods(['isAllowed'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->setMethods(['getUserType'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->hasCompanyPermission = $objectManager->getObject(
            HasCompanyPermission::class,
            [
                'authorization' => $this->authorization,
                'userContext' => $this->userContext
            ]
        );
    }

    /**
     * Test for method isApplicable.
     *
     * @return void
     */
    public function testIsApplicable()
    {
        $paymentMethod = $this->getMockForAbstractClass(MethodInterface::class);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())->method('getCustomerId')->willReturn(1);
        $paymentMethod->expects($this->once())->method('getCode')->willReturn('paymentCode');
        $this->assertTrue($this->hasCompanyPermission->isApplicable($paymentMethod, $quote));
    }

    /**
     * Test for method isApplicable with non-authorized user.
     *
     * @return void
     */
    public function testIsApplicableWithNonAuthorizedUser()
    {
        $paymentMethod = $this->getMockForAbstractClass(MethodInterface::class);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())->method('getCustomerId')->willReturn(null);
        $this->assertTrue($this->hasCompanyPermission->isApplicable($paymentMethod, $quote));
    }

    /**
     * Test for method isApplicable with Payment on Account method.
     *
     * @return void
     */
    public function testIsApplicableWithPaymentOnAccountMethod()
    {
        $paymentMethod = $this->getMockForAbstractClass(MethodInterface::class);
        $paymentMethod->expects($this->once())->method('getCode')->willReturn(
            HasCompanyPermission::PAYMENT_ACCOUNT_METHOD_CODE
        );

        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())->method('getCustomerId')->willReturn(1);

        $userType = UserContextInterface::USER_TYPE_CUSTOMER;
        $this->userContext->expects($this->exactly(1))->method('getUserType')->willReturn($userType);

        $this->authorization->expects($this->once())
            ->method('isAllowed')->with('Magento_Sales::payment_account')->willReturn(false);
        $this->assertFalse($this->hasCompanyPermission->isApplicable($paymentMethod, $quote));
    }
}
