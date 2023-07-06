<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Company\Role;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\Data\RoleInterfaceFactory;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Block\Company\Role\Edit;
use Magento\Company\Model\Authorization\PermissionProvider;
use Magento\Framework\Acl\AclResource\ProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EditTest extends TestCase
{
    /**
     * @var RoleRepositoryInterface|MockObject
     */
    private $roleRepository;

    /**
     * @var RoleInterfaceFactory|MockObject
     */
    private $roleFactory;

    /**
     * @var Data|MockObject
     */
    private $jsonHelper;

    /**
     * @var ProviderInterface|MockObject
     */
    private $resourceProvider;

    /**
     * @var PermissionProvider|MockObject
     */
    private $permissionProvider;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Edit
     */
    private $edit;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->roleRepository = $this->getMockForAbstractClass(RoleRepositoryInterface::class);
        $this->roleFactory = $this->createPartialMock(
            RoleInterfaceFactory::class,
            ['create']
        );
        $this->jsonHelper = $this->createMock(Data::class);
        $this->resourceProvider = $this->getMockForAbstractClass(ProviderInterface::class);
        $this->permissionProvider = $this->createMock(PermissionProvider::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->edit = $objectManager->getObject(
            Edit::class,
            [
                'roleRepository' => $this->roleRepository,
                'roleFactory' => $this->roleFactory,
                'jsonHelper' => $this->jsonHelper,
                'resourceProvider' => $this->resourceProvider,
                'permissionProvider' => $this->permissionProvider,
                '_request' => $this->request,
                'data' => []
            ]
        );
    }

    /**
     * Test for getRole method.
     *
     * @return void
     */
    public function testGetRole()
    {
        $roleId = 1;
        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $this->request->expects($this->exactly(2))->method('getParam')->with('id')->willReturn($roleId);
        $this->roleRepository->expects($this->once())->method('get')->with($roleId)->willReturn($role);
        $this->assertEquals($role, $this->edit->getRole());
    }

    /**
     * Test for getRole method with duplicate.
     *
     * @return void
     */
    public function testGetRoleWithDuplicate()
    {
        $roleId = 1;
        $roleName = 'Role 1';
        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['id'], ['duplicate_id'], ['id'])
            ->willReturnOnConsecutiveCalls(null, $roleId, null);
        $this->roleRepository->expects($this->once())->method('get')->with($roleId)->willReturn($role);
        $role->expects($this->once())->method('setId')->with(null)->willReturnSelf();
        $role->expects($this->once())->method('getRoleName')->willReturn($roleName);
        $role->expects($this->once())->method('setRoleName')->with($roleName . __(' - Duplicated'))->willReturnSelf();
        $this->assertEquals($role, $this->edit->getRole());
    }

    /**
     * Test for getRole with empty id.
     *
     * @return void
     */
    public function testGetRoleWithEmptyId()
    {
        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['id'], ['duplicate_id'])
            ->willReturnOnConsecutiveCalls(null, null);
        $this->roleFactory->expects($this->once())->method('create')->willReturn($role);
        $this->assertEquals($role, $this->edit->getRole());
    }

    /**
     * Test for getRole with invalid id.
     *
     * @param int $roleId
     * @return void
     * @dataProvider dataProviderGetRoleWithInvalidId
     */
    public function testGetRoleWithInvalidId($roleId): void
    {
        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($roleId);
        $this->roleFactory->expects($this->once())->method('create')->willReturn($role);
        $this->assertEquals($role, $this->edit->getRole());
    }

    /**
     * Test for getTreeJsOptions method.
     *
     * @return void
     */
    public function testGetTreeJsOptions()
    {
        $roleId = 1;
        $rolePermissions = [
            1 => 'allow'
        ];
        $aclResources = [
            [
                'id' => 1,
                'title' => 'Resource 1',
                'sort_order' => 10,
                'children' => [
                    [
                        'id' => 3,
                        'title' => 'Subresource 1',
                        'sort_order' => 15
                    ]
                ]
            ],
            [
                'id' => 2,
                'title' => 'Resource 2',
                'sort_order' => 20,
                'children' => []
            ]
        ];
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($roleId);
        $this->permissionProvider->expects($this->once())
            ->method('retrieveRolePermissions')->with($roleId)->willReturn($rolePermissions);
        $this->resourceProvider->expects($this->once())->method('getAclResources')->willReturn($aclResources);
        $data = $this->edit->getTreeJsOptions();
        $this->assertInstanceOf(Phrase::class, $data['roleTree']['data'][0]['text']);
        $this->assertEquals(
            [
                'roleTree' => [
                    'data' => [
                        [
                            'id' => 1,
                            'children' => [
                                [
                                    'id' => 3,
                                    'text' => 'Subresource 1',
                                    'state' => []
                                ],
                            ],
                            'text' => 'Resource 1',
                            'state' => [
                                'opened' => 'open',
                                'selected' => true
                            ],
                            'li_attr' => [
                                'class' => 'root-collapsible'
                            ]
                        ],
                        [
                            'id' => 2,
                            'children' => [],
                            'text' => 'Resource 2',
                            'state' => [],
                            'li_attr' => [
                                'class' => 'root-collapsible'
                            ]
                        ]
                    ]
                ]
            ],
            $data
        );
    }

    /**
     * @return array
     */
    public function dataProviderGetRoleWithInvalidId(): array
    {
        return [
            [' '],
            ['_']
        ];
    }
}
