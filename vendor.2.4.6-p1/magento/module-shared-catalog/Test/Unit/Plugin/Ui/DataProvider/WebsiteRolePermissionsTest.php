<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Ui\DataProvider;

use Magento\Authorization\Model\Role;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\SharedCatalog\Plugin\Ui\DataProvider\WebsiteRolePermissions;
use Magento\SharedCatalog\Ui\DataProvider\Website;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for WebsiteRolePermissions plugin.
 *
 */
class WebsiteRolePermissionsTest extends TestCase
{
    /**
     * @var ArrayManager|MockObject
     */
    private $arrayManagerMock;

    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var User
     */
    private $userMock;

    /**
     * @var Session
     */
    private $backendSessionMock;

    /**
     * @var Website|MockObject
     */
    private $websiteMock;

    /**
     * @var WebsiteRolePermissions
     */
    private $websiteRolePermissions;

    protected function setUp(): void
    {
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRole'])
            ->getMock();

        $this->roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGwsIsAll'])
            ->getMock();

        $this->backendSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();

        $this->websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteRolePermissions = new WebsiteRolePermissions($this->backendSessionMock, $this->arrayManagerMock);
    }

    /**
     * Test for afterGetWebsites().
     *
     * @return void
     * @dataProvider afterGetWebsitesDataProvider
     */
    public function testAfterGetWebsites($allWebsitesPermissions, $result, $exists, $finalResult)
    {
        $this->backendSessionMock->expects($this->once())
            ->method('getUser')
            ->willReturn($this->userMock);
        $this->userMock->expects($this->once())
            ->method('getRole')
            ->willReturn($this->roleMock);
        $this->roleMock->expects($this->once())
            ->method('getGwsIsAll')
            ->willReturn($allWebsitesPermissions);

        $this->arrayManagerMock->expects($this->any())
            ->method('exists')
            ->with(0, $result)
            ->willReturn($exists);

        $this->assertEquals(
            $finalResult,
            $this->websiteRolePermissions
                ->afterGetWebsites($this->websiteMock, $result)
        );
    }

    /**
     * Data provider for afterGetWebsites() test.
     *
     * @return array
     */
    public function afterGetWebsitesDataProvider()
    {
        return [
            [
                0,
                [
                    0 => ['value' => 0, 'label' => 'All Store Views'],
                    1 => ['value' => 1, 'label' => 'Main Website Store', 'store_ids' => [1]]
                ],
                1,
                [
                    0 => ['value' => 1, 'label' => 'Main Website Store', 'store_ids' => [1]]
                ]
            ],
            [0, [], false, []],
            [1, [], false, []]
        ];
    }
}
