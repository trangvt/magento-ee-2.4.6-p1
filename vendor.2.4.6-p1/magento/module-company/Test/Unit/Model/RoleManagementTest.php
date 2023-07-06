<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Model\ResourceModel\Role\Collection;
use Magento\Company\Model\ResourceModel\Role\CollectionFactory;
use Magento\Company\Model\Role;
use Magento\Company\Model\RoleFactory;
use Magento\Company\Model\RoleManagement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\RoleManagement class.
 */
class RoleManagementTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $roleCollectionFactory;

    /**
     * @var RoleFactory|MockObject
     */
    private $roleFactory;

    /**
     * @var RoleManagement
     */
    private $roleManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->roleFactory = $this->getMockBuilder(RoleFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->roleCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->roleManagement = $objectManager->getObject(
            RoleManagement::class,
            [
                'roleCollectionFactory' => $this->roleCollectionFactory,
                'roleFactory' => $this->roleFactory,
            ]
        );
    }

    /**
     * Test getRolesByCompanyId method.
     *
     * @return void
     */
    public function testGetRolesByCompanyId()
    {
        $companyId = 1;
        $roleCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $roleModel = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleCollectionFactory->expects($this->once())->method('create')->willReturn($roleCollection);
        $roleCollection->expects($this->once())->method('addFieldToFilter')
            ->with('company_id', ['eq' => $companyId])
            ->willReturnSelf();
        $roleCollection->expects($this->once())->method('setOrder')->with('role_id', 'ASC')->willReturnSelf();
        $roleCollection->expects($this->once())->method('load')->willReturnSelf();
        $roleCollection->expects($this->once())->method('getItems')->willReturn([]);
        $this->roleFactory->expects($this->once())->method('create')->willReturn($roleModel);
        $roleModel->expects($this->once())->method('setId')->with(0)->willReturnSelf();
        $roleModel->expects($this->once())->method('setRoleName')->with('Company Administrator')->willReturnSelf();

        $this->assertEquals([$roleModel], $this->roleManagement->getRolesByCompanyId($companyId));
    }

    /**
     * Test getCompanyDefaultRole method.
     *
     * @return void
     */
    public function testGetCompanyDefaultRole()
    {
        $companyId = 1;
        $roleCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->roleCollectionFactory->expects($this->once())->method('create')->willReturn($roleCollection);
        $roleCollection->expects($this->once())->method('addFieldToFilter')
            ->with('company_id', ['eq' => $companyId])
            ->willReturnSelf();
        $roleCollection->expects($this->once())->method('setOrder')->with('role_id', 'ASC')->willReturnSelf();
        $roleCollection->expects($this->once())->method('load')->willReturnSelf();
        $roleCollection->expects($this->once())->method('getItems')->willReturn([$role]);

        $this->assertEquals($role, $this->roleManagement->getCompanyDefaultRole($companyId));
    }
}
