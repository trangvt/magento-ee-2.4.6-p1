<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\StatusServiceInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyUserPermission;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\CompanyContext class.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyContextTest extends TestCase
{
    /**
     * @var StatusServiceInterface|MockObject
     */
    private $moduleConfig;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var CompanyUserPermission|MockObject
     */
    private $companyUserPermission;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Context|MockObject
     */
    private $httpContext;

    /**
     * @var CompanyContext
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->moduleConfig = $this->getMockBuilder(StatusServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyUserPermission = $this->getMockBuilder(CompanyUserPermission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->httpContext = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            CompanyContext::class,
            [
                'moduleConfig' => $this->moduleConfig,
                'userContext' => $this->userContext,
                'authorization' => $this->authorization,
                'companyUserPermission' => $this->companyUserPermission,
                'customerRepository' => $this->customerRepository,
                'httpContext' => $this->httpContext,
            ]
        );
    }

    /**
     * Test isModuleActive method.
     *
     * @return void
     */
    public function testIsModuleActive()
    {
        $this->moduleConfig->expects($this->once())->method('isActive')->willReturn(true);

        $this->assertTrue($this->model->isModuleActive());
    }

    /**
     * Test isStorefrontRegistrationAllowed method.
     *
     * @return void
     */
    public function testIsStorefrontRegistrationAllowed()
    {
        $this->moduleConfig->expects($this->once())->method('isStorefrontRegistrationAllowed')->willReturn(true);

        $this->assertTrue($this->model->isStorefrontRegistrationAllowed());
    }

    /**
     * Test isCustomerLoggedIn method.
     *
     * @return void
     */
    public function testIsCustomerLoggedIn()
    {
        $userId = 1;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);

        $this->assertTrue($this->model->isCustomerLoggedIn());
    }

    /**
     * Test isResourceAllowed method.
     *
     * @return void
     */
    public function testIsResourceAllowed()
    {
        $resource = 'Magento_Company::users_view';
        $this->authorization->expects($this->once())->method('isAllowed')->with($resource, null)->willReturn(true);

        $this->assertTrue($this->model->isResourceAllowed($resource));
    }

    /**
     * Test getCustomerId method.
     *
     * @return void
     */
    public function testGetCustomerId()
    {
        $userId = 1;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);

        $this->assertEquals($userId, $this->model->getCustomerId());
    }

    /**
     * Test isCurrentUserCompanyUser method.
     *
     * @return void
     */
    public function testIsCurrentUserCompanyUser()
    {
        $userId = 1;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->companyUserPermission->expects($this->once())->method('isCurrentUserCompanyUser')->willReturn(true);

        $this->assertTrue($this->model->isCurrentUserCompanyUser());
    }

    /**
     * Test getCustomerGroupId method.
     *
     * @return void
     */
    public function testGetCustomerGroupId()
    {
        $userId = 1;
        $customerGroupId = 3;
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $customer->expects($this->once())->method('getGroupId')->willReturn($customerGroupId);

        $this->assertEquals($customerGroupId, $this->model->getCustomerGroupId());
    }

    /**
     * Test getCustomerGroupId method without customer id.
     *
     * @return void
     */
    public function testGetCustomerGroupIdWithoutCustomerId()
    {
        $customerGroupId = 3;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(null);
        $this->httpContext->expects($this->once())
            ->method('getValue')
            ->with(\Magento\Customer\Model\Context::CONTEXT_GROUP)
            ->willReturn($customerGroupId);

        $this->assertEquals($customerGroupId, $this->model->getCustomerGroupId());
    }

    /**
     * Test getCustomerGroupId method with NoSuchEntityException exception.
     *
     * @return void
     */
    public function testGetCustomerGroupIdWithNoSuchEntityException()
    {
        $userId = 1;
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->customerRepository->expects($this->once())->method('getById')->willThrowException($exception);

        $this->assertEquals(
            GroupInterface::NOT_LOGGED_IN_ID,
            $this->model->getCustomerGroupId()
        );
    }
}
