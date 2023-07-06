<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\CatalogPermissions\Model;

use Magento\CatalogPermissions\Model\Permission;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\Config;
use Magento\SharedCatalog\Plugin\CatalogPermissions\Model\UpdateSharedCatalogCategoryPermissionsPlugin;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\SharedCatalog\Plugin\CatalogPermissions\Model\UpdateSharedCatalogCategoryPermissionsPlugin.
 */
class UpdateSharedCatalogCategoryPermissionsPluginTest extends TestCase
{
    /**
     * @var CatalogPermissionManagement|MockObject
     */
    private $catalogPermissionManagement;

    /**
     * @var Config|MockObject
     */
    private $sharedCatalogConfig;

    /**
     * @var UpdateSharedCatalogCategoryPermissionsPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->catalogPermissionManagement = $this->getMockBuilder(
            CatalogPermissionManagement::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            UpdateSharedCatalogCategoryPermissionsPlugin::class,
            [
                'catalogPermissionManagement' => $this->catalogPermissionManagement,
                'sharedCatalogConfig' => $this->sharedCatalogConfig
            ]
        );
    }

    /**
     * Test afterSave method.
     *
     * @return void
     */
    public function testAfterSave()
    {
        $categoryId = 12;
        $customerGroup = 3;
        $websiteId = 1;

        $subject = $this->getMockBuilder(Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->getMockBuilder(Permission::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId', 'getCustomerGroupId', 'getWebsiteId', 'getGrantCatalogCategoryView'])
            ->getMock();
        $result->expects($this->once())->method('getCategoryId')->willReturn($categoryId);
        $result->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroup);
        $result->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $result->expects($this->once())->method('getGrantCatalogCategoryView')->willReturn(1);
        $this->sharedCatalogConfig->expects($this->once())
            ->method('isActive')
            ->with(ScopeInterface::SCOPE_WEBSITE, $websiteId)
            ->willReturn(true);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('updateSharedCatalogPermission')
            ->with($categoryId, $websiteId, $customerGroup, 1);

        $this->assertEquals($result, $this->plugin->afterSave($subject, $result));
    }

    /**
     * Test afterDelete method.
     *
     * @return void
     */
    public function testAfterDelete()
    {
        $categoryId = 12;
        $customerGroup = 3;
        $websiteId = 1;
        $permission = Permission::PERMISSION_DENY;

        $subject = $this->getMockBuilder(Permission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->getMockBuilder(Permission::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryId', 'getCustomerGroupId', 'getWebsiteId'])
            ->getMock();
        $result->expects($this->once())->method('getCategoryId')->willReturn($categoryId);
        $result->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroup);
        $result->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->sharedCatalogConfig->expects($this->once())
            ->method('isActive')
            ->with(ScopeInterface::SCOPE_WEBSITE, $websiteId)
            ->willReturn(true);
        $this->catalogPermissionManagement->expects($this->once())
            ->method('updateSharedCatalogPermission')
            ->with($categoryId, $websiteId, $customerGroup, $permission);

        $this->assertEquals($result, $this->plugin->afterDelete($subject, $result));
    }
}
