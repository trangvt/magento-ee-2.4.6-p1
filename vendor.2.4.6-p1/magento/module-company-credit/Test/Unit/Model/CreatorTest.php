<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\CompanyCredit\Model\Creator;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\Integration;
use Magento\NegotiableQuote\Model\Purged\Provider;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\ResourceModel\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Creator model.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatorTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Creator
     */
    private $creator;

    /**
     * @var User|MockObject
     */
    private $userResourceMock;

    /**
     * @var UserInterfaceFactory|MockObject
     */
    private $userFactoryMock;

    /**
     * @var IntegrationServiceInterface|MockObject
     */
    private $integrationMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerNameGenerationMock;

    /**
     * @var Provider|MockObject
     */
    private $providerMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userResourceMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userFactoryMock = $this->getMockBuilder(UserInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->integrationMock = $this->getMockBuilder(IntegrationServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerNameGenerationMock = $this->getMockBuilder(CustomerNameGenerationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->providerMock = $this->getMockBuilder(Provider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->creator = $this->objectManagerHelper->getObject(
            Creator::class,
            [
                'userResource' => $this->userResourceMock,
                'userFactory' => $this->userFactoryMock,
                'integration' => $this->integrationMock,
                'customerRepository' => $this->customerRepositoryMock,
                'customerNameGeneration' => $this->customerNameGenerationMock,
                'provider' => $this->providerMock
            ]
        );
    }

    /**
     * Test for retrieveCreatorName() method if user type is admin.
     *
     * @return void
     */
    public function testRetrieveCreatorNameIfUserTypeAdmin()
    {
        $userId = 1;
        $userFirstName = 'John';
        $userLastName = 'Doe';

        $userMock = $this->getMockBuilder(\Magento\User\Model\User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userFactoryMock->expects($this->once())->method('create')->willReturn($userMock);
        $this->userResourceMock->expects($this->once())->method('load')->with($userMock, $userId);
        $userMock->expects($this->once())->method('getFirstName')->willReturn($userFirstName);
        $userMock->expects($this->once())->method('getLastName')->willReturn($userLastName);
        $result = $userFirstName . ' ' . $userLastName;

        $this->assertEquals(
            $result,
            $this->creator->retrieveCreatorName(UserContextInterface::USER_TYPE_ADMIN, $userId)
        );
    }

    /**
     * Test for retrieveCreatorName() method if user type is integration.
     *
     * @return void
     */
    public function testRetrieveCreatorNameIfUserTypeIntegration()
    {
        $userId = 1;
        $userName = 'John Doe';

        $integrationMock = $this->getMockBuilder(Integration::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName'])
            ->getMock();
        $this->integrationMock->expects($this->once())->method('get')->with($userId)
            ->willReturn($integrationMock);
        $integrationMock->expects($this->once())->method('getName')->willReturn($userName);

        $this->assertEquals(
            $userName,
            $this->creator->retrieveCreatorName(UserContextInterface::USER_TYPE_INTEGRATION, $userId)
        );
    }

    /**
     * Test for retrieveCreatorName() method if user type is customer.
     *
     * @return void
     */
    public function testRetrieveCreatorNameIfUserTypeCustomer()
    {
        $userId = 1;
        $userName = 'John Doe';

        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryMock->expects($this->once())->method('getById')->with($userId)
            ->willReturn($customerMock);
        $this->customerNameGenerationMock->expects($this->once())->method('getCustomerName')->willReturn($userName);

        $this->assertEquals(
            $userName,
            $this->creator->retrieveCreatorName(UserContextInterface::USER_TYPE_CUSTOMER, $userId)
        );
    }

    /**
     * Test for retrieveCreatorName() method if user type is not expected.
     *
     * @return void
     */
    public function testRetrieveCreatorName()
    {
        $userId = 1;

        $this->assertEquals('', $this->creator->retrieveCreatorName(UserContextInterface::USER_TYPE_GUEST, $userId));
    }
}
