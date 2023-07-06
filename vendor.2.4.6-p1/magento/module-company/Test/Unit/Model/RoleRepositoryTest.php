<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\Data\RoleInterfaceFactory;
use Magento\Company\Api\Data\RoleSearchResultsInterface;
use Magento\Company\Api\Data\RoleSearchResultsInterfaceFactory;
use Magento\Company\Model\PermissionManagementInterface;
use Magento\Company\Model\ResourceModel\Role;
use Magento\Company\Model\ResourceModel\Role\Collection;
use Magento\Company\Model\ResourceModel\Role\CollectionFactory;
use Magento\Company\Model\Role\Permission;
use Magento\Company\Model\Role\Validator;
use Magento\Company\Model\RoleRepository;
use Magento\Framework\Acl\Data\CacheInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for test RoleRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RoleRepositoryTest extends TestCase
{
    /**
     * @var RoleInterfaceFactory|MockObject
     */
    private $roleFactory;

    /**
     * @var \Magento\Company\Model\ResourceModel\Role|MockObject
     */
    private $roleResource;

    /**
     * @var CollectionFactory|MockObject
     */
    private $roleCollectionFactory;

    /**
     * @var RoleSearchResultsInterfaceFactory|MockObject
     */
    private $searchResultsFactory;

    /**
     * @var \Magento\Company\Model\ResourceModel\Permission\CollectionFactory|MockObject
     */
    private $permissionCollectionFactory;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Company\Model\Role|MockObject
     */
    private $role;

    /**
     * @var \Magento\Company\Model\Role\Permission|MockObject
     */
    private $rolePermission;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var CacheInterface|MockObject
     */
    private $aclDataCacheMock;

    /**
     * @var PermissionManagementInterface|MockObject
     */
    private $permissionManagement;

    /**
     * @var Validator|MockObject
     */
    private $validator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->roleFactory = $this->getMockBuilder(RoleInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->roleResource = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->setMethods(['save', 'delete', 'load'])
            ->getMock();
        $this->roleCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->searchResultsFactory = $this->getMockBuilder(
            RoleSearchResultsInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->permissionCollectionFactory = $this->getMockBuilder(
            \Magento\Company\Model\ResourceModel\Permission\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilter', 'create'])
            ->getMock();
        $this->role = $this->getMockBuilder(\Magento\Company\Model\Role::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRoleName', 'getCompanyId', 'getId', 'getPermissions', 'load', 'setPermissions'])
            ->getMock();
        $this->aclDataCacheMock = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();
        $userRoleManagement = $this->getMockBuilder(AclInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->rolePermission = $this->getMockBuilder(Permission::class)
            ->setMethods([
                'delete',
                'setRoleId',
                'save',
                'saveRolePermissions',
                'deleteRolePermissions',
                'getRolePermissions',
                'getRoleUsersCount'
            ])
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                'permissionCollectionFactory' => $this->permissionCollectionFactory,
                'aclDataCache' => $this->aclDataCacheMock,
                'userRoleManagement' => $userRoleManagement
            ])
            ->getMock();
        $this->permissionManagement = $this->getMockBuilder(PermissionManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['retrieveAllowedResources', 'populatePermissions'])
            ->getMockForAbstractClass();
        $this->validator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->setMethods(['retrieveRole', 'validateRoleBeforeDelete', 'checkRoleExist'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->roleRepository = $objectManager->getObject(
            RoleRepository::class,
            [
                'roleFactory' => $this->roleFactory,
                'roleResource' => $this->roleResource,
                'roleCollectionFactory' => $this->roleCollectionFactory,
                'searchResultsFactory' => $this->searchResultsFactory,
                'rolePermission' => $this->rolePermission,
                'permissionManagement' => $this->permissionManagement,
                'validator' => $this->validator
            ]
        );
    }

    /**
     * Test save method.
     *
     * @return void
     */
    public function testSave()
    {
        $roleId = 1;
        $this->validateRoleName(false);
        $this->role->expects($this->atLeastOnce())->method('getId')->willReturn($roleId);
        $this->roleResource->expects($this->once())->method('save')->with($this->role)->willReturnSelf();
        $this->validator->expects($this->once())
            ->method('retrieveRole')
            ->with($this->role)
            ->willReturn($this->role);
        $this->role->expects($this->once())
            ->method('getPermissions')
            ->willReturn($this->getPermissionArray());
        $this->permissionManagement->expects($this->once())
            ->method('retrieveAllowedResources')
            ->willReturn([7]);
        $this->permissionManagement->expects($this->once())
            ->method('populatePermissions')
            ->willReturn([$this->mockPermission()]);
        $this->rolePermission->expects($this->once())->method('saveRolePermissions')->willReturnSelf();
        $this->assertEquals($this->role, $this->roleRepository->save($this->role));
    }

    /**
     * Test save method throws exception.
     *
     * @return void
     */
    public function testSaveRoleWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage(
            'User role with this name already exists. Enter a different name to save this role.'
        );
        $roleId = 1;
        $this->validateRoleName(true);
        $this->role->expects($this->atLeastOnce())->method('getId')->willReturn($roleId);
        $this->validator->expects($this->once())
            ->method('retrieveRole')
            ->with($this->role)
            ->willReturn($this->role);
        $this->role->expects($this->once())
            ->method('getPermissions')
            ->willReturn($this->getPermissionArray());
        $this->permissionManagement->expects($this->once())
            ->method('retrieveAllowedResources')
            ->willReturn([7]);
        $this->permissionManagement->expects($this->once())
            ->method('populatePermissions')
            ->willReturn([$this->mockPermission()]);
        $this->assertEquals($this->role, $this->roleRepository->save($this->role));
    }

    /**
     * Test should skip name validation if not changed (performance optimization)
     *
     * @return void
     */
    public function testSkipNameValidationIfNotChanged()
    {
        $roleId = 1;
        $roleName = 'Custom Role';
        $this->role = $this->getMockBuilder(\Magento\Company\Model\Role::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load'])
            ->getMockForAbstractClass();
        $this->role->setOrigData('role_name', $roleName);
        $this->role->setData('role_name', $roleName);
        $this->role->setData('role_id', $roleId);
        $this->role->setPermissions($this->getPermissionArray());
        $this->roleCollectionFactory->expects($this->never())->method('create');
        $this->validator->expects($this->once())
            ->method('retrieveRole')
            ->with($this->role)
            ->willReturn($this->role);
        $this->permissionManagement->expects($this->once())
            ->method('retrieveAllowedResources')
            ->willReturn([7]);
        $this->permissionManagement->expects($this->once())
            ->method('populatePermissions')
            ->willReturn([$this->mockPermission()]);
        $this->assertEquals($this->role, $this->roleRepository->save($this->role));
    }

    /**
     * Test save method throws CouldNotSaveException.
     *
     * @return void
     */
    public function testSaveRoleWithCouldNotSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not save role');
        $roleId = 1;
        $exception = new CouldNotSaveException(__('Could not save role'));
        $this->validateRoleName(false);
        $this->role->expects($this->atLeastOnce())->method('getId')->willReturn($roleId);
        $this->validator->expects($this->once())
            ->method('retrieveRole')
            ->with($this->role)
            ->willReturn($this->role);
        $this->role->expects($this->once())
            ->method('getPermissions')
            ->willReturn($this->getPermissionArray());
        $this->permissionManagement->expects($this->once())
            ->method('retrieveAllowedResources')
            ->willReturn([7]);
        $this->permissionManagement->expects($this->once())
            ->method('populatePermissions')
            ->willReturn([$this->mockPermission()]);
        $this->roleResource->expects($this->once())->method('save')->willThrowException($exception);
        $this->roleRepository->save($this->role);
    }

    /**
     * Test get method.
     *
     * @return void
     */
    public function testGet()
    {
        $roleId = 1;
        $this->roleFactory->expects($this->once())->method('create')->willReturn($this->role);
        $this->rolePermission->expects($this->once())
            ->method('getRolePermissions')
            ->willReturn([$this->getPermissionArray()]);

        $this->assertEquals($this->role, $this->roleRepository->get($roleId));
    }

    /**
     * Test get method throws NoSuchEntityException.
     *
     * @return void
     */
    public function testGetWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with roleId = 2');
        $exception = new NoSuchEntityException(
            __('No such entity with %fieldName = %fieldValue', ['fieldName' => 'roleId', 'fieldValue' => 2])
        );
        $this->roleResource->expects($this->once())
            ->method('load')
            ->with($this->role, 2)
            ->willReturn($this->role);
        $this->roleFactory->expects($this->once())->method('create')->willReturn($this->role);
        $this->validator->expects($this->once())
            ->method('checkRoleExist')
            ->with($this->role, 2)
            ->willThrowException($exception);

        $this->roleRepository->get(2);
    }

    /**
     * Test delete method.
     *
     * @return void
     */
    public function testDelete()
    {
        $roleId = 1;
        $this->roleResource->expects($this->once())->method('delete')->with($this->role)->willReturnSelf();
        $this->prepareRolePermissions($roleId);
        $this->roleResource->expects($this->once())
            ->method('delete')
            ->with($this->role)
            ->willReturnSelf();
        $this->rolePermission->expects($this->once())->method('deleteRolePermissions')->willReturnSelf();

        $this->assertTrue($this->roleRepository->delete($roleId));
    }

    /**
     * Test delete method throws StateException.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->expectExceptionMessage('Cannot delete role with id 1');
        $roleId = 1;
        $exception = new \Exception();
        $this->role->expects($this->exactly(1))->method('getId')->willReturn($roleId);
        $this->validator->expects($this->once())
            ->method('validateRoleBeforeDelete')
            ->with($this->role)
            ->willReturn(true);
        $this->prepareRolePermissions($roleId);
        $this->roleResource->expects($this->once())
            ->method('delete')
            ->with($this->role)
            ->willThrowException($exception);
        $this->roleRepository->delete($roleId);
    }

    /**
     * Mock validateRoleName.
     *
     * @param bool $totalCount
     * @return void
     */
    private function validateRoleName($totalCount)
    {
        $roleName = 'Custom Role';
        $companyId = 1;
        $roleId = 1;
        $this->role->method('getRoleName')->willReturn($roleName);
        $this->role->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $roleCollection = $this->createPartialMock(
            Collection::class,
            ['addFieldToFilter', 'getSize', 'addOrder', 'setCurPage', 'setPageSize', 'getItems']
        );
        $this->roleCollectionFactory->expects($this->once())->method('create')->willReturn($roleCollection);
        $roleCollection->expects($this->exactly(3))
            ->method('addFieldToFilter')
            ->withConsecutive(
                [RoleInterface::ROLE_NAME, ['eq' => $roleName]],
                [RoleInterface::COMPANY_ID, ['eq' => $companyId]],
                [RoleInterface::ROLE_ID, ['neq' => $roleId]]
            )->willReturnSelf();
        $roleCollection->expects($this->once())->method('getSize')->willReturn($totalCount);
    }

    /**
     * Test getList.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchCriteria = $this->createPartialMock(
            SearchCriteria::class,
            ['getFilterGroups', 'getSortOrders', 'getCurrentPage', 'getPageSize']
        );

        $searchResults = $this->getMockForAbstractClass(
            RoleSearchResultsInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setSearchCriteria', 'setTotalCount', 'setItems', 'getTotalCount']
        );
        $roleCollection = $this->createPartialMock(
            Collection::class,
            ['addFieldToFilter', 'getSize', 'addOrder', 'setCurPage', 'setPageSize', 'getItems']
        );
        $filterGroup = $this->createPartialMock(
            FilterGroup::class,
            ['getFilters']
        );
        $filter = $this->createPartialMock(
            Filter::class,
            ['getConditionType', 'getField', 'getValue']
        );
        $sortOrder = $this->createPartialMock(
            SortOrder::class,
            ['getField', 'getDirection']
        );
        $this->searchResultsFactory->expects($this->once())->method('create')->willReturn($searchResults);
        $searchResults->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteria)
            ->willReturnSelf();
        $this->roleCollectionFactory->expects($this->once())->method('create')->willReturn($roleCollection);
        $searchCriteria->expects($this->once())->method('getFilterGroups')->willReturn([$filterGroup]);
        $filterGroup->expects($this->once())->method('getFilters')->willReturn([$filter]);
        $filter->expects($this->once())->method('getConditionType')->willReturn(null);
        $filter->expects($this->once())->method('getField')->willReturn('some_field');
        $filter->expects($this->once())->method('getValue')->willReturn('some_value');
        $roleCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('some_field', ['eq' => 'some_value'])
            ->willReturnSelf();
        $roleCollection->expects($this->once())->method('getSize')->willReturn(1);
        $searchResults->expects($this->once())->method('setTotalCount')->with(1)->willReturnSelf();
        $searchCriteria->expects($this->once())->method('getSortOrders')->willReturn([$sortOrder]);
        $sortOrder->expects($this->once())->method('getField')->willReturn('some_field');
        $sortOrder->expects($this->once())->method('getDirection')->willReturn('ASC');
        $roleCollection->expects($this->once())->method('addOrder')->with('some_field', 'ASC')->willReturnSelf();
        $searchCriteria->expects($this->once())->method('getCurrentPage')->willReturn(1);
        $searchCriteria->expects($this->once())->method('getPageSize')->willReturn(1);
        $roleCollection->expects($this->once())->method('setCurPage')->with(1)->willReturnSelf();
        $roleCollection->expects($this->once())->method('setPageSize')->with(1)->willReturnSelf();
        $roleCollection->expects($this->once())->method('getItems')->willReturn([$this->role]);
        $this->rolePermission->expects($this->once())
            ->method('getRolePermissions')
            ->willReturn([$this->getPermissionArray()]);
        $searchResults->expects($this->once())->method('setItems')->willReturnSelf();

        $this->assertEquals($searchResults, $this->roleRepository->getList($searchCriteria));
    }

    /**
     * Mock Permission.
     *
     * @return PermissionInterface
     */
    private function mockPermission()
    {
        $permission = $this->getMockBuilder(\Magento\Company\Model\Permission::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResourceId'])
            ->getMock();
        $permission->expects($this->atLeastOnce())
            ->method('getResourceId')
            ->willReturn(7);

        return $permission;
    }

    /**
     * Method to get dummy permissions.
     *
     * @return array
     */
    private function getPermissionArray()
    {
        return [
            'permission_id' => 4,
            'role_id' => 3,
            'resource_id' => 7,
            'permission' => 1
        ];
    }

    /**
     * Prepate role factory and role permission mocks for test.
     *
     * @param int $roleId
     * @return void
     */
    private function prepareRolePermissions($roleId)
    {
        $this->roleFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->role);
        $this->roleResource->expects($this->atLeastOnce())
            ->method('load')
            ->with($this->role, $roleId)
            ->willReturn($this->role);
        $this->rolePermission->expects($this->atLeastOnce())
            ->method('getRolePermissions')
            ->willReturn($this->getPermissionArray());
    }
}
