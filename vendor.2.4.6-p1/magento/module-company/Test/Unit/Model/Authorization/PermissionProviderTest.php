<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Authorization;

use Magento\Company\Model\Authorization\PermissionProvider;
use Magento\Company\Model\ResourceModel\Permission\Collection;
use Magento\Company\Model\ResourcePool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PermissionProviderTest extends TestCase
{
    /**
     * @var Collection|MockObject
     */
    private $permissionCollection;

    /**
     * @var ResourcePool|MockObject
     */
    private $resourcePool;

    /**
     * @var PermissionProvider
     */
    private $permissionProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->permissionCollection = $this->createMock(
            Collection::class
        );
        $this->resourcePool = $this->createMock(
            ResourcePool::class
        );
        $objectManager = new ObjectManager($this);
        $this->permissionProvider = $objectManager->getObject(
            PermissionProvider::class,
            [
                'permissionCollection' => $this->permissionCollection,
                'resourcePool' => $this->resourcePool,
            ]
        );
    }

    /**
     * Test retrieve role permissions.
     *
     * @return void
     */
    public function testRetrieveRolePermissions()
    {
        $roleId = 1;
        $this->permissionCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('role_id', ['eq' => $roleId])
            ->willReturnSelf();
        $this->permissionCollection->expects($this->once())
            ->method('toOptionHash')
            ->with('resource_id', 'permission')
            ->willReturnSelf();

        $this->assertInstanceOf(
            Collection::class,
            $this->permissionProvider->retrieveRolePermissions($roleId)
        );
    }

    /**
     * Test retrieveDefaultPermissions method.
     *
     * @param array $allowedResources
     * @param array $expectedResult
     * @return void
     * @dataProvider retrieveDefaultPermissionsDataProvider
     */
    public function testRetrieveDefaultPermissions(array $allowedResources, array $expectedResult)
    {
        $this->resourcePool->expects($this->once())->method('getDefaultResources')->willReturn($allowedResources);
        $this->assertEquals($expectedResult, $this->permissionProvider->retrieveDefaultPermissions());
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
                ['Magento_NegotiableQuote::checkout', 'Magento_NegotiableQuote::view_quotes'],
                [
                    'Magento_NegotiableQuote::checkout' => 'allow',
                    'Magento_NegotiableQuote::view_quotes' => 'allow',
                ]
            ]
        ];
    }
}
