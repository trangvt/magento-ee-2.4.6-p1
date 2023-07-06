<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Authorization\Loader;

use Magento\Authorization\Model\Acl\Role\User;
use Magento\Authorization\Model\Acl\Role\UserFactory;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Model\Authorization\Loader\Role;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\ResourceModel\Role\Collection;
use Magento\Framework\Acl;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\Company\Model\Authorization\Loader\Role model.
 */
class RoleTest extends TestCase
{
    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * @var UserFactory|MockObject
     */
    private $roleFactory;

    /**
     * @var CompanyUser|MockObject
     */
    private $companyUser;

    /**
     * @var Role
     */
    private $role;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleFactory = $this->getMockBuilder(UserFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyUser = $this->getMockBuilder(CompanyUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->role = $objectManager->getObject(
            Role::class,
            [
                'collection' => $this->collection,
                'roleFactory' => $this->roleFactory,
                'companyUser' => $this->companyUser,
            ]
        );
    }

    /**
     * Test for populateAcl method.
     *
     * @return void
     */
    public function testPopulateAcl()
    {
        $companyId = 1;
        $roleId = 2;
        $this->companyUser->expects($this->once())->method('getCurrentCompanyId')->willReturn($companyId);
        $this->collection->expects($this->once())->method('addFieldToFilter')
            ->with(RoleInterface::COMPANY_ID, $companyId)->willReturnSelf();
        $role = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collection->expects($this->once())->method('getItems')->willReturn([$role]);
        $acl = $this->getMockBuilder(Acl::class)
            ->disableOriginalConstructor()
            ->getMock();
        $aclRole = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $role->expects($this->once())->method('getId')->willReturn($roleId);
        $this->roleFactory->expects($this->once())->method('create')->with(['roleId' => $roleId])->willReturn($aclRole);
        $acl->expects($this->once())->method('addRole')->with($aclRole)->willReturnSelf();
        $this->role->populateAcl($acl);
    }
}
