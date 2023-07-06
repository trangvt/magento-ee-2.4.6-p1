<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Authorization\Loader;

use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Model\Authorization\Loader\Rule;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\Permission;
use Magento\Company\Model\ResourceModel\Permission\Collection;
use Magento\Framework\Acl;
use Magento\Framework\Acl\AclResource\ProviderInterface;
use Magento\Framework\Acl\RootResource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Company\Model\Authorization\Loader\Rule model.
 */
class RuleTest extends TestCase
{
    /**
     * @var RootResource|MockObject
     */
    private $rootResource;

    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * @var ProviderInterface|MockObject
     */
    private $resourceProvider;

    /**
     * @var RoleManagementInterface|MockObject
     */
    private $roleManagement;

    /**
     * @var CompanyUser|MockObject
     */
    private $companyUser;

    /**
     * @var Rule
     */
    private $rule;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rootResource = $this->getMockBuilder(RootResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceProvider = $this->getMockBuilder(ProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->roleManagement = $this->getMockBuilder(RoleManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyUser = $this->getMockBuilder(CompanyUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->rule = $objectManagerHelper->getObject(
            Rule::class,
            [
                'rootResource' => $this->rootResource,
                'collection' => $this->collection,
                'resourceProvider' => $this->resourceProvider,
                'roleManagement' => $this->roleManagement,
                'companyUser' => $this->companyUser,
            ]
        );
    }

    /**
     * Test for populateAcl method.
     *
     * @param string $expectedPermission
     * @param int $getIdCounter
     * @param int $allowCounter
     * @param int $denyCounter
     * @param array $aclResources
     * @return void
     * @dataProvider populateAclDataProvider
     */
    public function testPopulateAcl(
        $expectedPermission,
        $getIdCounter,
        $allowCounter,
        $denyCounter,
        array $aclResources
    ) {
        $resourceId = 1;
        $companyId = 2;
        $roleIdString = '3';
        $this->companyUser->expects($this->once())->method('getCurrentCompanyId')->willReturn($companyId);
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->roleManagement->expects($this->once())->method('getRolesByCompanyId')->with()->willReturn([$role]);
        $role->expects($this->once())->method('getId')->willReturn($roleIdString);
        $this->collection->expects($this->once())->method('addFieldToFilter')
            ->with(
                PermissionInterface::ROLE_ID,
                $this->callback(function ($condition) use ($roleIdString) {
                    return $condition === ['in' => [(int)$roleIdString]];
                })
            )->willReturnSelf();
        $permission = $this->getMockBuilder(Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->expects($this->once())->method('getItems')->willReturn([$permission]);
        $permission->expects($this->atLeastOnce())->method('getRoleId')->willReturn($roleIdString);
        $permission->expects($this->atLeastOnce())->method('getResourceId')->willReturn($resourceId);
        $permission->expects($this->atLeastOnce())->method('getPermission')->willReturn($expectedPermission);
        $acl = $this->getMockBuilder(Acl::class)
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->once())->method('hasResource')->with($resourceId)->willReturn(true);
        $this->rootResource->expects($this->exactly($getIdCounter))->method('getId')->willReturn(1);
        $acl->expects($this->exactly($allowCounter))->method('allow')->willReturnSelf();
        $acl->expects($this->exactly($denyCounter))->method('deny')->willReturnSelf();
        $this->resourceProvider->expects($this->once())->method('getAclResources')->willReturn($aclResources);

        $this->rule->populateAcl($acl);
    }

    /**
     * Data provider for populateAcl method.
     *
     * @return array
     */
    public function populateAclDataProvider()
    {
        return [
            [
                'allow',
                1,
                2,
                0,
                [
                    [
                        'children' => [
                            'children' => ['id' => 1]
                        ],
                        'id' => 2
                    ]
                ]
            ],
            [
                'deny',
                0,
                0,
                1,
                [
                    [
                        'children' => [
                            'children' => ['id' => 2]
                        ],
                        'id' => 1
                    ]
                ]
            ]
        ];
    }
}
