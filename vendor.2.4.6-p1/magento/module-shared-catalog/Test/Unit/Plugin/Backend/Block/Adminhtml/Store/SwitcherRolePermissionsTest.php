<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Backend\Block\Adminhtml\Store;

use Magento\Authorization\Model\Role;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\SharedCatalog\Block\Adminhtml\Store\Switcher;
use Magento\SharedCatalog\Plugin\Backend\Block\Adminhtml\Store\SwitcherRolePermissions;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SwitcherRolePermissionsTest extends TestCase
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
     * @var Switcher|MockObject
     */
    private $switcherMock;

    /**
     * @var Session
     */
    private $backendSessionMock;

    /**
     * @var SwitcherRolePermissions
     */
    private $switcherRolePermissions;

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

        $this->switcherMock = $this->getMockBuilder(Switcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->switcherRolePermissions = new SwitcherRolePermissions(
            $this->backendSessionMock,
            $this->arrayManagerMock
        );
    }

    /**
     * Test for afterGetStoreOptionsAsArray().
     *
     * @return void
     * @dataProvider afterGetStoreOptionsAsArrayDataProvider
     */
    public function testAfterGetStoreOptionsAsArray($allWebsitesPermissions, $result, $exists, $finalResult)
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
            ->with(Switcher::ALL_STORES_ID, $result)
            ->willReturn($exists);

        $this->assertEquals(
            $finalResult,
            $this->switcherRolePermissions
                ->afterGetStoreOptionsAsArray($this->switcherMock, $result)
        );
    }

    /**
     * Data provider for afterGetStoreOptionsAsArray() test.
     *
     * @return array
     */
    public function afterGetStoreOptionsAsArrayDataProvider()
    {
        return [
            [
                0,
                [
                    0 => ['id' => 0, 'label' => 'All Store Views'],
                    1 => ['id' => 1, 'label' => 'Main Website Store']
                ],
                1,
                [
                    0 => ['id' => 1, 'label' => 'Main Website Store']
                ]
            ],
            [0, [], false, []],
            [1, [], false, []]
        ];
    }
}
