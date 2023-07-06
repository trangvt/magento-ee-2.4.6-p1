<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Role;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\Role\Validator;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\Company\Model\Role\Validator class.
 */
class ValidatorTest extends TestCase
{
    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var RoleRepositoryInterface|MockObject
     */
    private $roleRepository;

    /**
     * @var AclInterface|MockObject
     */
    private $userRoleManagement;

    /**
     * @var RoleManagementInterface|MockObject
     */
    private $roleManagement;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $dataObjectHelper;

    /**
     * @var Validator
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->roleRepository = $this->getMockBuilder(RoleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userRoleManagement = $this->getMockBuilder(AclInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->roleManagement = $this->getMockBuilder(RoleManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dataObjectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Validator::class,
            [
                'companyRepository' => $this->companyRepository,
                'roleRepository' => $this->roleRepository,
                'userRoleManagement' => $this->userRoleManagement,
                'roleManagement' => $this->roleManagement,
                'dataObjectHelper' => $this->dataObjectHelper,
            ]
        );
    }

    /**
     * Test retrieveRole method.
     *
     * @return void
     */
    public function testRetrieveRole()
    {
        $roleId = 1;
        $requestedRole = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $originalRole = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestedRole->expects($this->atLeastOnce())->method('getId')->willReturn($roleId);
        $requestedRole->expects($this->once())->method('getCompanyId')->willReturn(1);
        $this->roleRepository->expects($this->once())->method('get')->with($roleId)->willReturn($originalRole);
        $this->dataObjectHelper->expects($this->once())
            ->method('mergeDataObjects')
            ->with(RoleInterface::class, $originalRole, $originalRole)
            ->willReturnSelf();
        $originalRole->expects($this->atLeastOnce())->method('getCompanyId')->willReturn(1);
        $originalRole->expects($this->once())->method('getRoleName')->willReturn('Role Name');
        $this->companyRepository->expects($this->once())->method('get')->with(1)->willReturn($company);

        $this->model->retrieveRole($requestedRole);
    }

    /**
     * Test retrieveRole method without role name.
     *
     * @return void
     */
    public function testRetrieveRoleWithoutRoleName()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('"role_name" is required. Enter and try again.');
        $requestedRole = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestedRole->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $requestedRole->expects($this->once())->method('getRoleName')->willReturn(null);

        $this->model->retrieveRole($requestedRole);
    }

    /**
     * Test retrieveRole method without role id.
     *
     * @return void
     */
    public function testRetrieveRoleWithInvalidRoleId()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('"company_id" is required. Enter and try again.');
        $requestedRole = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestedRole->expects($this->once())->method('getRoleName')->willReturn('Role Name');
        $requestedRole->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $requestedRole->expects($this->once())->method('getCompanyId')->willReturn(null);

        $this->model->retrieveRole($requestedRole);
    }

    /**
     * Test retrieveRole method with NoSuchEntityException.
     *
     * @return void
     */
    public function testRetrieveRoleWithNoSuchEntityException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with company_id = 1');
        $requestedRole = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $exception = new NoSuchEntityException(__('No such entity.'));
        $requestedRole->expects($this->once())->method('getRoleName')->willReturn('Role Name');
        $requestedRole->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $requestedRole->expects($this->atLeastOnce())->method('getCompanyId')->willReturn(1);
        $this->companyRepository->expects($this->once())->method('get')->willThrowException($exception);

        $this->model->retrieveRole($requestedRole);
    }

    /**
     * Test validatePermissions method.
     *
     * @return void
     */
    public function testValidatePermissions()
    {
        $permission = $this->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $allowedResources = ['Magento_Company::users_view'];
        $permission->expects($this->once())->method('getResourceId')->willReturn('Magento_Company::users_view');

        $this->model->validatePermissions([$permission], $allowedResources);
    }

    /**
     * Test validatePermissions method with InputException.
     *
     * @return void
     */
    public function testValidatePermissionsWithInputException()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage(
            'Invalid value of "Magento_Company::contacts" provided for the resource_id field.'
        );
        $permission = $this->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $allowedResources = ['Magento_Company::contacts'];
        $permission->expects($this->once())->method('getResourceId')->willReturn('Magento_Company::users_view');

        $this->model->validatePermissions([$permission], $allowedResources);
    }

    /**
     * Test validateRoleBeforeDelete method.
     *
     * @return void
     */
    public function testValidateRoleBeforeDelete()
    {
        $roleId = 1;
        $companyId = 3;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $role->expects($this->once())->method('getId')->willReturn($roleId);
        $this->userRoleManagement->expects($this->once())
            ->method('getUsersCountByRoleId')
            ->with($roleId)
            ->willReturn(null);
        $role->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->roleManagement->expects($this->once())
            ->method('getRolesByCompanyId')
            ->with($companyId, false)
            ->willReturn([$role, $role]);

        $this->model->validateRoleBeforeDelete($role);
    }

    /**
     * Test validateRoleBeforeDelete method with users assigned to tole.
     *
     * @return void
     */
    public function testValidateRoleBeforeDeleteWithUsersAssigned()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->expectExceptionMessage('This role cannot be deleted because users are assigned to it.');
        $roleId = 1;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $role->expects($this->once())->method('getId')->willReturn($roleId);
        $this->userRoleManagement->expects($this->once())
            ->method('getUsersCountByRoleId')
            ->with($roleId)
            ->willReturn(1);

        $this->model->validateRoleBeforeDelete($role);
    }

    /**
     * Test validateRoleBeforeDelete method when this role is the only one in the company.
     *
     * @return void
     */
    public function testValidateRoleBeforeDeleteWhenTheOnlyRole()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->expectExceptionMessage('You cannot delete a role when it is the only role in the company.');
        $roleId = 1;
        $companyId = 3;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $role->expects($this->once())->method('getId')->willReturn($roleId);
        $this->userRoleManagement->expects($this->once())
            ->method('getUsersCountByRoleId')
            ->with($roleId)
            ->willReturn(null);
        $role->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->roleManagement->expects($this->once())
            ->method('getRolesByCompanyId')
            ->with($companyId, false)
            ->willReturn([$role]);

        $this->model->validateRoleBeforeDelete($role);
    }

    /**
     * Test checkRoleExist method.
     *
     * @return void
     */
    public function testCheckRoleExist()
    {
        $roleId = 1;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $role->expects($this->once())->method('getId')->willReturn($roleId);

        $this->model->checkRoleExist($role, $roleId);
    }

    /**
     * Test checkRoleExist method with exception.
     *
     * @return void
     */
    public function testCheckRoleExistWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with roleId = 1');
        $roleId = 1;
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $role->expects($this->once())->method('getId')->willReturn(null);

        $this->model->checkRoleExist($role, $roleId);
    }
}
