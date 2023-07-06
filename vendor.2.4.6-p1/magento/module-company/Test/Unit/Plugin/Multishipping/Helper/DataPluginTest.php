<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Multishipping\Helper;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Model\Customer\PermissionInterface;
use Magento\Company\Plugin\Multishipping\Helper\DataPlugin;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Multishipping\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataPluginTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var PermissionInterface|MockObject
     */
    private $permission;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var DataPlugin
     */
    private $plugin;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->userContext = $this->createMock(
            UserContextInterface::class
        );
        $this->customerRepository = $this
            ->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->permission = $this
            ->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCheckoutAllowed'])
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            DataPlugin::class,
            [
                'customerRepository' => $this->customerRepository,
                'userContext' => $this->userContext,
                'permission' => $this->permission
            ]
        );
    }

    /**
     * Test afterIsMultishippingCheckoutAvailable.
     *
     * @param bool $expectedResult
     * @dataProvider dataProviderIsMultishippingCheckoutAvailable
     */
    public function testAfterIsMultishippingCheckoutAvailable($expectedResult)
    {
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $helper = $this->createMock(
            Data::class
        );
        $this->userContext->expects($this->any())->method('getUserId')->willReturn(1);
        $this->customerRepository->expects($this->once())->method('getById')->with(1)->willReturn($customer);
        $this->permission->expects($this->any())
            ->method('isCheckoutAllowed')
            ->with($customer)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->plugin->afterIsMultishippingCheckoutAvailable($helper, $expectedResult)
        );
    }

    /**
     * Data provider isMultishippingCheckoutAvailable.
     *
     * @return array
     */
    public function dataProviderIsMultishippingCheckoutAvailable()
    {
        return [
            [false],
            [true]
        ];
    }
}
