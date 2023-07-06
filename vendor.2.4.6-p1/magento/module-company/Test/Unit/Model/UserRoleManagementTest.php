<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Model\ResourceModel\UserRole\Collection;
use Magento\Company\Model\ResourceModel\UserRole\CollectionFactory;
use Magento\Company\Model\Role;
use Magento\Company\Model\UserRole;
use Magento\Company\Model\UserRoleFactory;
use Magento\Company\Model\UserRoleManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Acl\Data\CacheInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for class UserRoleManagement.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserRoleManagementTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $userRoleCollectionFactory;

    /**
     * @var \Magento\Company\Model\ResourceModel\Role\CollectionFactory|MockObject
     */
    private $roleCollectionFactory;

    /**
     * @var UserRoleFactory|MockObject
     */
    private $userRoleFactory;

    /**
     * @var RoleManagementInterface|MockObject
     */
    private $roleManagement;

    /**
     * @var CompanyAdminPermission|MockObject
     */
    private $companyAdminPermission;

    /**
     * @var UserRoleManagement
     */
    private $userRoleManagement;

    /**
     * @var CacheInterface|MockObject
     */
    private $aclDataCacheMock;

    /**
     * @var RoleRepositoryInterface|MockObject
     */
    private $roleRepository;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userRoleCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->roleCollectionFactory = $this->getMockBuilder(
            \Magento\Company\Model\ResourceModel\Role\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->userRoleFactory = $this->getMockBuilder(UserRoleFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->roleManagement = $this->getMockBuilder(RoleManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyDefaultRole', 'getAdminRole'])
            ->getMockForAbstractClass();
        $this->companyAdminPermission = $this->getMockBuilder(CompanyAdminPermission::class)
            ->disableOriginalConstructor()
            ->setMethods(['isGivenUserCompanyAdmin'])
            ->getMock();
        $this->aclDataCacheMock = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();
        $this->roleRepository = $this->getMockBuilder(RoleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList', 'getItems'])
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilter', 'create'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->userRoleManagement = $objectManager->getObject(
            UserRoleManagement::class,
            [
                'userRoleCollectionFactory' => $this->userRoleCollectionFactory,
                'roleCollectionFactory' => $this->roleCollectionFactory,
                'userRoleFactory' => $this->userRoleFactory,
                'customerRepository' => $this->customerRepository,
                'roleManagement' => $this->roleManagement,
                'companyAdminPermission' => $this->companyAdminPermission,
                'aclDataCache' => $this->aclDataCacheMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder
            ]
        );
    }

    /**
     * Test assignUserDefaultRole method.
     *
     * @return void
     */
    public function testAssignUserDefaultRole()
    {
        $userId = 1;
        $companyId = 1;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $userRoleCollection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'load', 'getItems'])
            ->getMock();
        $userRole = $this->getMockBuilder(UserRole::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();
        $roleModel = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRoleId', 'setUserId', 'save'])
            ->getMock();
        $this->roleManagement->expects($this->once())
            ->method('getCompanyDefaultRole')
            ->with($companyId)
            ->willReturn($role);
        $this->roleManagement->expects($this->once())
            ->method('getRolesByCompanyId')
            ->with($companyId)
            ->willReturn([$role]);
        $this->userRoleCollectionFactory->expects($this->once())->method('create')->willReturn($userRoleCollection);
        $userRoleCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('user_id', ['eq' => $userId])
            ->willReturnSelf();
        $userRoleCollection->expects($this->once())->method('load')->willReturnSelf();
        $userRoleCollection->expects($this->once())->method('getItems')->willReturn([$userRole]);
        $userRole->expects($this->once())->method('delete')->willReturn(true);
        $this->userRoleFactory->expects($this->once())->method('create')->willReturn($roleModel);
        $role->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $roleModel->expects($this->once())->method('setRoleId')->with(1)->willReturnSelf();
        $roleModel->expects($this->once())->method('setUserId')->with($userId)->willReturnSelf();
        $roleModel->expects($this->once())->method('save')->willReturnSelf();
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes', 'getCompanyId'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn($customer);
        $this->aclDataCacheMock->expects($this->once())
            ->method('clean');

        $this->userRoleManagement->assignUserDefaultRole($userId, $companyId);
    }

    /**
     * Test assignUserDefaultRole method with not exists company.
     *
     * @return void
     */
    public function testAssignUserDefaultRoleWithEmptyCompanyException()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('You cannot update the requested attribute. Row ID: roleId = 1.');
        $userId = 1;
        $companyId = 1;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $this->roleManagement->expects($this->once())
            ->method('getCompanyDefaultRole')
            ->with($companyId)
            ->willReturn($role);

        $role->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes', 'getCompanyId'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn(0);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn($customer);

        $this->userRoleManagement->assignUserDefaultRole($userId, $companyId);
    }

    /**
     * Test assignUserDefaultRole method with not assigned role.
     *
     * @return void
     */
    public function testAssignUserDefaultRoleWithEmptyRoleException()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('"id" is required. Enter and try again.');
        $userId = 1;
        $companyId = 1;
        $this->roleManagement->expects($this->once())
            ->method('getCompanyDefaultRole')
            ->with($companyId)
            ->willReturn(null);
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes', 'getCompanyId'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn($customer);

        $this->userRoleManagement->assignUserDefaultRole($userId, $companyId);
    }

    /**
     * Test assignUserDefaultRole method with exception of user is another company admin.
     *
     * @return void
     */
    public function testAssignUserDefaultRoleWithAnotherCompanyAdmin()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('You cannot assign a different role to a company admin.');
        $userId = 1;
        $companyId = 1;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $this->roleManagement->expects($this->once())
            ->method('getCompanyDefaultRole')
            ->with($companyId)
            ->willReturn($role);
        $role->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->companyAdminPermission
            ->expects($this->once())
            ->method('isGivenUserCompanyAdmin')
            ->with($userId)
            ->willReturn(true);
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes', 'getCompanyId'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn($customer);

        $this->userRoleManagement->assignUserDefaultRole($userId, $companyId);
    }

    /**
     * Test assignRoles method with multiple assigned roles to company.
     *
     * @return void
     */
    public function testAssignRolesWithMultipleRolesAssigned()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('You cannot assign multiple roles to a user.');
        $userId = 1;
        $companyId = 1;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $role->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturnOnConsecutiveCalls(1, 1, 3, 5);
        $this->roleManagement->expects($this->once())
            ->method('getRolesByCompanyId')
            ->with($companyId)
            ->willReturn([$role, $role]);

        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes', 'getCompanyId'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn($customer);

        $this->userRoleManagement->assignRoles($userId, [$role, $role, $role]);
    }

    /**
     * Test assignRoles method with assign of role that is not exist in company.
     *
     * @return void
     */
    public function testAssignRolesWithAssignAbsendRole()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Invalid value of "1" provided for the role_id field.');
        $userId = 1;
        $companyId = 1;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $role->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $userRole = $this->getMockBuilder(UserRole::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete', 'getId'])
            ->getMock();
        $userRole->expects($this->once())
            ->method('getId')
            ->willReturn(77);
        $this->roleManagement->expects($this->once())
            ->method('getRolesByCompanyId')
            ->with($companyId)
            ->willReturn([$userRole]);

        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes', 'getCompanyId'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        $extensionAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn($customer);

        $this->userRoleManagement->assignRoles($userId, [$role]);
    }

    /**
     * Test getRolesByUserId method.
     *
     * @param bool $isCompanyAdmin
     * @param array $userRole
     * @param int|null $roleCollectionSize
     * @param array $expectedResult
     * @return void
     * @dataProvider getRolesByUserIdDataProvider
     */
    public function testGetRolesByUserId($isCompanyAdmin, array $userRole, $roleCollectionSize, array $expectedResult)
    {
        $userId = 1;
        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $userRoleCollection = $this->createPartialMock(
            Collection::class,
            ['addFieldToFilter', 'load', 'getItems']
        );
        $roleCollection = $this->createPartialMock(
            \Magento\Company\Model\ResourceModel\Role\Collection::class,
            ['addFieldToFilter', 'load', 'getSize', 'getItems']
        );
        $this->companyAdminPermission->expects($this->once())
            ->method('isGivenUserCompanyAdmin')
            ->with($userId)
            ->willReturn($isCompanyAdmin);
        if ($isCompanyAdmin) {
            $this->roleManagement->expects($this->once())
                ->method('getAdminRole')
                ->willReturn($role);
        } else {
            $this->userRoleCollectionFactory->expects($this->once())->method('create')->willReturn($userRoleCollection);
            $userRoleCollection->expects($this->once())
                ->method('addFieldToFilter')
                ->with('user_id', ['eq' => $userId])
                ->willReturnSelf();
            $userRoleCollection->expects($this->once())->method('load')->willReturnSelf();
            $userRoleCollection->expects($this->once())->method('getItems')->willReturn($userRole);
            if (!empty($userRole)) {
                $userRole[0]->expects($this->atLeastOnce())->method('getRoleId')->willReturn(1);
                $this->roleCollectionFactory->expects($this->once())->method('create')->willReturn($roleCollection);
                $roleCollection->expects($this->once())
                    ->method('addFieldToFilter')
                    ->with('role_id', ['in' => [1]])
                    ->willReturnSelf();
                $roleCollection->expects($this->once())->method('load')->willReturnSelf();
                $roleCollection->expects($this->once())->method('getSize')->willReturn($roleCollectionSize);
                if ($roleCollectionSize) {
                    $roleCollection->expects($this->once())->method('getItems')->willReturn($expectedResult);
                }
            }
        }

        $this->assertEquals($expectedResult, $this->userRoleManagement->getRolesByUserId($userId));
    }

    /**
     * Test for getRolesByUserId() method if NoSuchEntityException for company admin appeared.
     *
     * @return void
     */
    public function testGetRolesByUserIdNoSuchEntityException()
    {
        $userId = 1;
        $expectedResult = [];
        $exception = new NoSuchEntityException();
        $this->companyAdminPermission->expects($this->once())
            ->method('isGivenUserCompanyAdmin')
            ->with($userId)
            ->willThrowException($exception);
        $this->roleManagement->expects($this->never())
            ->method('getAdminRole');
        $userRoleCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'load', 'getItems'])
            ->getMock();
        $this->userRoleCollectionFactory->expects($this->once())->method('create')->willReturn($userRoleCollection);
        $userRoleCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('user_id', ['eq' => $userId])
            ->willReturnSelf();
        $userRoleCollection->expects($this->once())->method('load')->willReturnSelf();
        $userRoleCollection->expects($this->once())->method('getItems')->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->userRoleManagement->getRolesByUserId($userId));
    }

    /**
     * Data provider for getRolesByUserId method.
     *
     * @return array
     */
    public function getRolesByUserIdDataProvider()
    {
        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $userRole = $this->createPartialMock(UserRole::class, ['getRoleId']);
        $roleModel = $this->createMock(Role::class);
        return [
            [true, [], null, [$role]],
            [false, [], null, []],
            [false, [$userRole], null, []],
            [false, [$userRole], 1, [$roleModel]],
        ];
    }

    /**
     * Test getUsersByRoleId method.
     *
     * @return void
     */
    public function testGetUsersByRoleId()
    {
        $roleId = 1;
        $userIds = [3, 4, 5];
        $usersMockArray = ['user1', 'user2', 'user3'];

        $userRoleCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'load', 'getItems', 'getColumnValues'])
            ->getMock();
        $this->userRoleCollectionFactory->expects($this->once())->method('create')->willReturn($userRoleCollection);
        $userRoleCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('role_id', ['eq' => $roleId])
            ->willReturnSelf();
        $userRoleCollection->expects($this->once())->method('getColumnValues')->willReturn($userIds);
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('entity_id', $userIds, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $this->customerRepository->expects($this->once())->method('getList')->with($searchCriteria)->willReturnSelf();
        $this->customerRepository->expects($this->once())->method('getItems')->willReturn($usersMockArray);
        $this->assertEquals($usersMockArray, $this->userRoleManagement->getUsersByRoleId($roleId));
    }

    /**
     * Test getUsersCountByRoleId method.
     *
     * @return void
     */
    public function testGetUsersCountByRoleId()
    {
        $roleId = 1;
        $userRoleCollection = $this->createPartialMock(
            Collection::class,
            ['addFieldToFilter', 'getSize']
        );
        $this->userRoleCollectionFactory->expects($this->once())->method('create')->willReturn($userRoleCollection);
        $userRoleCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('role_id', ['eq' => $roleId])
            ->willReturnSelf();
        $userRoleCollection->expects($this->once())->method('getSize')->willReturn(1);

        $this->assertEquals(1, $this->userRoleManagement->getUsersCountByRoleId($roleId));
    }

    /**
     * Test deleteRoles method.
     *
     * @return void
     */
    public function testDeleteRoles()
    {
        $userRoleCollectionMock = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getItems', 'addFieldToFilter', 'load'])
            ->getMock();
        $userRoleCollectionMock->expects($this->once())->method('addFieldToFilter')->willReturnSelf();
        $userRoleCollectionMock->expects($this->once())->method('load')->willReturnSelf();
        $userRole1 = $this->getMockBuilder(UserRole::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();
        $userRole2 = $this->getMockBuilder(UserRole::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();
        $userRoleCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$userRole1, $userRole2]);

        $userRole1->expects($this->once())->method('delete');
        $userRole2->expects($this->once())->method('delete');

        $this->aclDataCacheMock->expects($this->once())
            ->method('clean');

        $this->userRoleCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($userRoleCollectionMock);

        $this->userRoleManagement->deleteRoles(1);
    }
}
