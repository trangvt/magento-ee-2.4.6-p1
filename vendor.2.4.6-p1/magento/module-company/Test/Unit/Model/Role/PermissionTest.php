<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Role;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Model\Permission;
use Magento\Company\Model\ResourceModel\Permission\Collection;
use Magento\Company\Model\ResourceModel\Permission\CollectionFactory;
use Magento\Company\Model\Role;
use Magento\Framework\Acl\Data\CacheInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for test RoleRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PermissionTest extends TestCase
{
    const ROLE_PERMISSION_ROLE_ID = 1;

    /**
     * @var CollectionFactory|MockObject
     */
    private $permissionCollectionFactory;

    /**
     * @var Collection|MockObject
     */
    private $permissionCollection;

    /**
     * @var CacheInterface|MockObject
     */
    private $aclDataCache;

    /**
     * @var AclInterface|MockObject
     */
    private $userRoleManagement;

    /**
     * @var Role|MockObject
     */
    private $role;

    /**
     * @var Permission|MockObject
     */
    private $permission;

    /**
     * @var \Magento\Company\Model\Role\Permission
     */
    private $object;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->permissionCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->permissionCollection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'load', 'getItems'])
            ->getMock();
        $this->aclDataCache = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userRoleManagement = $this->getMockBuilder(AclInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->permission = $this->getMockBuilder(Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->permission->setData($this->getPermissionArray());

        $objectManager = new ObjectManager($this);
        $this->object = $objectManager->getObject(
            \Magento\Company\Model\Role\Permission::class,
            [
                'permissionCollectionFactory' => $this->permissionCollectionFactory,
                'aclDataCache' => $this->aclDataCache,
                'userRoleManagement' => $this->userRoleManagement
            ]
        );
    }

    /**
     * Test getRoleUsersCount method.
     *
     * @return void
     */
    public function testGetRoleUsersCount()
    {
        $roleId = 7;
        $roles = [
            [1],
            [2],
            [3],
        ];
        $this->userRoleManagement->expects($this->once())->method('getUsersByRoleId')->with($roleId)
            ->willReturn($roles);

        $this->assertEquals(3, $this->object->getRoleUsersCount($roleId));
    }

    /**
     * Test getRolePermissions method.
     *
     * @return void
     */
    public function testGetRolePermissions()
    {
        $this->prepareGetRolePermissions([$this->permission]);
        $this->assertEquals([$this->permission], $this->object->getRolePermissions($this->role));
    }

    /**
     * Test deleteRolePermissions method.
     *
     * @return void
     */
    public function testDeleteRolePermissions()
    {
        $this->prepareGetRolePermissions([$this->permission]);
        $this->permission->expects($this->once())->method('delete');

        $this->aclDataCache->expects($this->exactly(1))->method('clean');

        $this->object->deleteRolePermissions($this->role);
    }

    /**
     * Test saveRolePermissions method.
     *
     * @return void
     */
    public function testSaveRolePermissions()
    {
        $data = [
            [
                'permission_id' => 1,
                'role_id' => self::ROLE_PERMISSION_ROLE_ID,
                'resource_id' => 'Magento_Test::all',
                'permission' => PermissionInterface::DENY_PERMISSION
            ],
            [
                'permission_id' => 2,
                'role_id' => self::ROLE_PERMISSION_ROLE_ID,
                'resource_id' => 'Magento_Test::view',
                'permission' => PermissionInterface::DENY_PERMISSION
            ],
            [
                'resource_id' => 'Magento_Test::view',
                'permission' => PermissionInterface::ALLOW_PERMISSION
            ],
            [
                'resource_id' => 'Magento_Test::manage',
                'permission' => PermissionInterface::DENY_PERMISSION
            ]
        ];
        $permissions = [];
        foreach ($data as $key => $item) {
            $permissions[$key] = $this->getMockBuilder(Permission::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['delete', 'save', 'setRoleId'])
                ->getMockForAbstractClass();
            $permissions[$key]->setData($item);
        }

        $this->prepareGetRolePermissions(
            [
                $permissions[0],
                $permissions[1]
            ]
        );
        $this->role->expects($this->once())
            ->method('getPermissions')
            ->willReturn([$permissions[0], $permissions[2], $permissions[3]]);

        $permissions[0]->expects($this->never())->method('delete');
        $permissions[0]->expects($this->never())->method('save');

        $permissions[1]->expects($this->once())->method('delete');
        $permissions[1]->expects($this->never())->method('save');

        $permissions[2]->expects($this->never())->method('delete');
        $permissions[2]->expects($this->once())->method('save');
        $permissions[2]->expects($this->once())->method('setRoleId')->with(self::ROLE_PERMISSION_ROLE_ID);

        $permissions[3]->expects($this->never())->method('delete');
        $permissions[3]->expects($this->once())->method('save');
        $permissions[3]->expects($this->once())->method('setRoleId')->with(self::ROLE_PERMISSION_ROLE_ID);

        $this->aclDataCache->expects($this->exactly(1))->method('clean');

        $this->object->saveRolePermissions($this->role);
    }

    /**
     * Prepare getRolePermissions data.
     *
     * @param array $permissions
     * @return void
     */
    private function prepareGetRolePermissions(array $permissions = [])
    {
        $this->role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getPermissions'])
            ->getMockForAbstractClass();
        $this->role->expects($this->atLeastOnce())->method('getId')->willReturn(self::ROLE_PERMISSION_ROLE_ID);
        $this->permissionCollectionFactory->expects($this->once())
            ->method('create')->willReturn($this->permissionCollection);
        $this->permissionCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('role_id', ['eq' => self::ROLE_PERMISSION_ROLE_ID])
            ->willReturnSelf();
        $this->permissionCollection->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->permissionCollection->expects($this->once())
            ->method('getItems')
            ->willReturn($permissions);
    }

    /**
     * Get permissions array.
     *
     * @return array
     */
    private function getPermissionArray()
    {
        return [
            'permission_id' => 3,
            'role_id' => self::ROLE_PERMISSION_ROLE_ID,
            'resource_id' => 7,
            'permission' => 1
        ];
    }
}
