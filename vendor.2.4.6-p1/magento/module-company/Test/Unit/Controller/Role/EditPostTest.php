<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Role;

use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\Data\RoleInterfaceFactory;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Controller\Role\EditPost;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\PermissionManagementInterface;
use Magento\Company\Model\RoleRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditPostTest extends TestCase
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
     * @var CompanyUser|MockObject
     */
    private $companyUser;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var RedirectInterface|MockObject
     */
    private $redirect;

    /**
     * @var ResponseInterface|MockObject
     */
    private $response;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var PermissionManagementInterface|MockObject
     */
    private $permissionManagement;

    /**
     * @var EditPost
     */
    private $editPost;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->roleRepository = $this->createMock(RoleRepository::class);
        $this->roleFactory = $this->createPartialMock(
            RoleInterfaceFactory::class,
            ['create']
        );
        $this->companyUser = $this->createMock(CompanyUser::class);
        $this->request = $this->createMock(
            RequestInterface::class
        );
        $this->redirect = $this->createMock(
            RedirectInterface::class
        );
        $this->response = $this->getMockForAbstractClass(ResponseInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->permissionManagement = $this->createMock(
            PermissionManagementInterface::class
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->editPost = $objectManagerHelper->getObject(
            EditPost::class,
            [
                'roleRepository' => $this->roleRepository,
                'roleFactory' => $this->roleFactory,
                'companyUser' => $this->companyUser,
                'permissionManagement' => $this->permissionManagement,
                '_request' => $this->request,
                '_redirect' => $this->redirect,
                '_response' => $this->response,
                'messageManager' => $this->messageManager,
                'logger' => $this->logger,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $roleId = 1;
        $roleName = 'Role 1';
        $rolePermissions = '3';
        $companyId = 2;

        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['id'], ['role_name'], ['role_permissions'])
            ->willReturnOnConsecutiveCalls($roleId, $roleName, $rolePermissions);

        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $role->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);
        $this->roleFactory->expects($this->once())
            ->method('create')
            ->willReturn($role);
        $this->companyUser->expects($this->once())
            ->method('getCurrentCompanyId')
            ->willReturn($companyId);
        $this->roleRepository->expects($this->once())
            ->method('get')
            ->with($roleId)
            ->willReturn($role);
        $role->expects($this->once())
            ->method('setRoleName')
            ->with($roleName)
            ->willReturnSelf();
        $role->expects($this->once())
            ->method('setCompanyId')
            ->with($companyId)
            ->willReturnSelf();

        $permission = $this->getMockForAbstractClass(PermissionInterface::class);
        $this->permissionManagement->expects($this->once())
            ->method('populatePermissions')
            ->willReturn([$permission]);
        $role->expects($this->once())
            ->method('setPermissions')
            ->with([$permission])
            ->willReturnSelf();
        $this->roleRepository->expects($this->once())
            ->method('save')
            ->with($role)
            ->willReturn($role);
        $this->redirect->expects($this->once())
            ->method('redirect')
            ->with($this->response, '*/*/', [])
            ->willReturn($this->response);

        $this->assertEquals($this->response, $this->editPost->execute());
    }

    /**
     * Test for execute method with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $roleId = 1;
        $companyId = 2;

        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['id'])
            ->willReturnOnConsecutiveCalls($roleId);

        $role = $this->getMockForAbstractClass(RoleInterface::class);
        $this->roleRepository->expects($this->once())
            ->method('get')
            ->with($roleId)
            ->willReturn($role);
        $role->expects($this->once())
            ->method('getCompanyId')
            ->willReturn(3);
        $this->roleFactory->expects($this->once())
            ->method('create')
            ->willReturn($role);
        $this->companyUser->expects($this->once())
            ->method('getCurrentCompanyId')
            ->willReturn($companyId);
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with('Bad Request')
            ->willReturnSelf();
        $this->logger->expects($this->once())
            ->method('critical');
        $this->redirect->expects($this->once())
            ->method('redirect')
            ->with($this->response, '*/role/edit', ['id' => $roleId])
            ->willReturn($this->response);

        $this->assertEquals($this->response, $this->editPost->execute());
    }
}
