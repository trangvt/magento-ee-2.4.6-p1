<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Rma\Helper;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Plugin\Rma\Helper\RmaHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Helper\Data;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for RMAHelper plugin
 *
 * Check the rma return link for company admin account
 */
class RmaHelperTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var Data|MockObject
     */
    private $rmaMock;

    /**
     * @var RmaHelper|MockObject
     */
    private $helperMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->userContext = $this
            ->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->rmaMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperMock = $objectManagerHelper->getObject(
            RmaHelper::class,
            [
                'userContext' => $this->userContext
            ]
        );
    }

    /**
     * Test canCreateRma for company admin account.
     *
     * @param string $currentCustomerId
     * @param string $orderCustomerId
     * @param bool $expectedResult
     * @param bool $originalResult
     * @dataProvider dataProviderForAfterCanCreateRma
     */
    public function testCanCreateRmaHideRmaLinkForCompanyAdmin(
        string $currentCustomerId,
        string $orderCustomerId,
        bool $expectedResult,
        bool $originalResult
    ): void {
        $this->userContext
            ->expects($this->any())
            ->method('getUserId')
            ->willReturn($currentCustomerId);
        $this->userContext
            ->expects($this->any())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->orderMock
            ->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($orderCustomerId);
        $this->rmaMock
            ->expects($this->any())
            ->method('canCreateRma')
            ->willReturn($expectedResult);
        $this->assertEquals(
            $expectedResult,
            $this->helperMock->afterCanCreateRma($this->rmaMock, $originalResult, $this->orderMock)
        );
    }

    /**
     * Data provider for plugin afterCanCreateRma.
     *
     * @return array
     */
    public function dataProviderForAfterCanCreateRma(): array
    {
        return [
            'check create rma link for non logged in company user' => ['3', '1',false, true],
            'check create rma link for non logged in company user who place the order' => ['1', '1', true, true],
            'check create rma link for non logged in company user for user order' => ['1', '2', false, true]
        ];
    }
}
