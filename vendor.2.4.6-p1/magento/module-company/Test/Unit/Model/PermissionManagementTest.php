<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\PermissionInterfaceFactory;
use Magento\Company\Model\PermissionManagement;
use Magento\Company\Model\ResourcePool;
use Magento\Framework\Acl\AclResource\ProviderInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\PermissionManagement class.
 */
class PermissionManagementTest extends TestCase
{
    /**
     * @var ResourcePool|MockObject
     */
    private $resourcePool;

    /**
     * @var PermissionInterfaceFactory|MockObject
     */
    private $permissionFactory;

    /**
     * @var ProviderInterface|MockObject
     */
    private $resourceProvider;

    /**
     * @var PermissionManagement
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resourceProvider = $this->getMockBuilder(ProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->permissionFactory = $this->getMockBuilder(PermissionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resourcePool = $this->getMockBuilder(ResourcePool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            PermissionManagement::class,
            [
                'resourceProvider' => $this->resourceProvider,
                'permissionFactory' => $this->permissionFactory,
                'resourcePool' => $this->resourcePool,
            ]
        );
    }

    /**
     * Test retrieveDefaultPermissions method.
     *
     * @param array $aclResources
     * @param array $allowedResources
     * @return void
     * @dataProvider retrieveDefaultPermissionsDataProvider
     */
    public function testRetrieveDefaultPermissions(array $aclResources, array $allowedResources)
    {
        $permission = $this->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resourcePool->expects($this->once())->method('getDefaultResources')->willReturn($allowedResources);
        $this->resourceProvider->expects($this->once())->method('getAclResources')->willReturn($aclResources);
        $this->permissionFactory->expects($this->exactly(3))->method('create')->willReturn($permission);
        $permission->expects($this->exactly(3))
            ->method('setPermission')
            ->withConsecutive(['allow'], ['allow'], ['deny'])
            ->willReturnSelf();
        $permission->expects($this->exactly(3))
            ->method('setResourceId')
            ->withConsecutive([1], [3], [2])
            ->willReturnSelf();
        $this->assertEquals([$permission, $permission, $permission], $this->model->retrieveDefaultPermissions());
    }

    /**
     * Data provider for retrieveDefaultPermissions method.
     *
     * @return array
     */
    public function retrieveDefaultPermissionsDataProvider()
    {
        return [
            [
                [
                    [
                        'id' => 1,
                        'title' => 'Resource 1',
                        'sort_order' => 10,
                        'children' => [
                            [
                                'id' => 3,
                                'title' => 'Subresource 1',
                                'sort_order' => 15,
                            ],
                        ],
                    ],
                    [
                        'id' => 2,
                        'title' => 'Resource 2',
                        'sort_order' => 20,
                        'children' => [],
                    ],

                ],
                [1, 3]
            ]
        ];
    }

    /**
     * Test retrieveAllowedResources method.
     *
     * @return void
     */
    public function testRetrieveAllowedResources()
    {
        $permission = $this->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $permission->expects($this->once())
            ->method('getPermission')
            ->willReturn(PermissionInterface::ALLOW_PERMISSION);
        $permission->expects($this->once())
            ->method('getResourceId')
            ->willReturn('Magento_Company::contacts');

        $this->assertEquals(['Magento_Company::contacts'], $this->model->retrieveAllowedResources([$permission]));
    }
}
