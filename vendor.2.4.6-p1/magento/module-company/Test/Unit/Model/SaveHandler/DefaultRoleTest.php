<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveHandler;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\PermissionManagementInterface;
use Magento\Company\Model\Role;
use Magento\Company\Model\RoleFactory;
use Magento\Company\Model\RoleManagement;
use Magento\Company\Model\SaveHandler\DefaultRole;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DefaultRole save handler.
 */
class DefaultRoleTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DefaultRole
     */
    private $defaultRole;

    /**
     * @var RoleFactory|MockObject
     */
    private $roleFactoryMock;

    /**
     * @var RoleRepositoryInterface|MockObject
     */
    private $roleRepositoryMock;

    /**
     * @var PermissionManagementInterface|MockObject
     */
    private $permissionManagementMock;

    /**
     * @var RoleManagement|MockObject
     */
    private $roleManagementMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->roleFactoryMock = $this->getMockBuilder(RoleFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->roleRepositoryMock = $this->getMockBuilder(RoleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->permissionManagementMock = $this->getMockBuilder(PermissionManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->roleManagementMock = $this->getMockBuilder(RoleManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->defaultRole = $this->objectManagerHelper->getObject(
            DefaultRole::class,
            [
                'roleFactory' => $this->roleFactoryMock,
                'roleRepository' => $this->roleRepositoryMock,
                'permissionManagement' => $this->permissionManagementMock,
                'roleManagement' => $this->roleManagementMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $roleName = 'role name';
        $companyId = 1;
        $permissions = [];

        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $initialCompanyMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $initialCompanyMock->expects($this->once())->method('getId')->willReturn(null);
        $roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleFactoryMock->expects($this->once())->method('create')->willReturn($roleMock);
        $this->roleManagementMock->expects($this->once())->method('getCompanyDefaultRoleName')->willReturn($roleName);
        $roleMock->expects($this->once())->method('setRoleName')->with($roleName);
        $companyMock->expects($this->once())->method('getId')->willReturn($companyId);
        $roleMock->expects($this->once())->method('setCompanyId')->with($companyId);
        $this->permissionManagementMock->expects($this->once())->method('retrieveDefaultPermissions')
            ->willReturn($permissions);
        $roleMock->expects($this->once())->method('setPermissions')->with($permissions);
        $this->roleRepositoryMock->expects($this->once())->method('save')->with($roleMock);

        $this->defaultRole->execute($companyMock, $initialCompanyMock);
    }
}
