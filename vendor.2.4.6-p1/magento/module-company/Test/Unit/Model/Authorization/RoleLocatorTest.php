<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Authorization;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AclInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Model\Authorization\RoleLocator;
use Magento\Company\Model\CompanyAdminPermission;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RoleLocatorTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var AclInterface|MockObject
     */
    private $roleManagement;

    /**
     * @param \Magento\Company\Model\CompanyAdminPermission|PHPUnitFrameworkMockObjectMockObject
     */
    private $adminPermission;

    /**
     * @var RoleLocator
     */
    private $roleLocatorModel;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockForAbstractClass(
            UserContextInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getUserId']
        );
        $this->roleManagement = $this->getMockForAbstractClass(
            AclInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getRolesByUserId']
        );
        $this->adminPermission = $this->createMock(
            CompanyAdminPermission::class
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->roleLocatorModel = $objectManagerHelper->getObject(
            RoleLocator::class,
            [
                'userContext' => $this->userContext,
                'roleManagement' => $this->roleManagement,
                'adminPermission' => $this->adminPermission,
            ]
        );
    }

    /**
     * Test getAclRoleId method.
     *
     * @param array|null $role
     * @param int $roleId
     * @return void
     * @dataProvider getAclRoleIdDataProvider
     */
    public function testGetAclRoleId($role, $roleId)
    {
        $userId = 1;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->roleManagement->expects($this->once())->method('getRolesByUserId')->with($userId)->willReturn($role);
        if (!empty($role)) {
            $role = array_shift($role);
            $role->expects($this->once())->method('getData')->with('role_id')->willReturn(1);
        } else {
            $this->adminPermission->expects($this->once())->method('isGivenUserCompanyAdmin')->with($userId)
                ->willReturn(true);
        }
        $this->assertEquals($roleId, $this->roleLocatorModel->getAclRoleId());
    }

    /**
     * Data provider for getAclRoleId method.
     *
     * @return array
     */
    public function getAclRoleIdDataProvider()
    {
        $role = $this->getMockForAbstractClass(
            RoleInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getData']
        );
        return [
            [[$role], 1],
            [null, 0]
        ];
    }
}
