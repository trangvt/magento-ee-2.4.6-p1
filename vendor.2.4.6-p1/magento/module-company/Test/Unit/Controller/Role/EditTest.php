<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Role;

use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Controller\Role\Edit;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\Role;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditTest extends TestCase
{
    /**
     * @var Edit
     */
    private $controller;

    /**
     * @var RoleRepositoryInterface|MockObject
     */
    private $roleRepository;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var Page|MockObject
     */
    protected $resultPageMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var CompanyUser|MockObject
     */
    private $companyUser;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->roleRepository = $this->getMockBuilder(RoleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultPageFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyUser = $this->getMockBuilder(CompanyUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentCompanyId'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Edit::class,
            [
                '_request' => $this->request,
                'resultFactory' => $this->resultPageFactory,
                'messageManager' => $this->messageManager,
                'roleRepository' => $this->roleRepository,
                'companyUser' => $this->companyUser,
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $roleId = 1;
        $companyUserIdByRole = 1;
        $companyUserIdCurrent = 1;
        $phrase = new Phrase('Add New Role');
        $editRolePhrase = new Phrase('Edit Role');
        $resultPage = $this->createPartialMock(
            Page::class,
            ['getConfig']
        );
        $resultConfig = $this->getMockBuilder(Page::class)
            ->addMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultTitle = $this->createPartialMock(
            Title::class,
            ['set']
        );
        $role = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleRepository->expects($this->once())
            ->method('get')
            ->willReturn($role);
        $role->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyUserIdByRole);
        $this->companyUser->expects($this->once())
            ->method('getCurrentCompanyId')
            ->willReturn($companyUserIdCurrent);
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($roleId);
        $this->resultPageFactory->expects($this->once())->method('create')->willReturn($resultPage);
        $resultPage->expects($this->exactly(2))->method('getConfig')->willReturn($resultConfig);
        $resultConfig->expects($this->exactly(2))->method('getTitle')->willReturn($resultTitle);
        $resultTitle->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive([$phrase], [$editRolePhrase])
            ->willReturnSelf();
        $this->assertEquals($resultPage, $this->controller->execute());
    }

    /**
     * Test for execute method with NoSuchEntityException.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException()
    {
        $roleId = ' ';
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($roleId);
        $this->roleRepository->expects($this->once())->method('get')->with($roleId)->willThrowException(
            new NoSuchEntityException()
        );
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')->with('Bad Request')->willReturnSelf();
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultPage = $this->createPartialMock(
            Page::class,
            ['getConfig']
        );
        $resultConfig = $this->getMockBuilder(Page::class)
            ->addMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultTitle = $this->createPartialMock(
            Title::class,
            ['set']
        );
        $this->resultPageFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [ResultFactory::TYPE_PAGE, [], $resultPage],
                [ResultFactory::TYPE_REDIRECT, [], $result]
            ]);
        $resultPage->expects($this->once())->method('getConfig')->willReturn($resultConfig);
        $resultConfig->expects($this->once())->method('getTitle')->willReturn($resultTitle);
        $result->expects($this->once())->method('setPath')->with('*/role/')->willReturnSelf();
        $this->assertEquals($result, $this->controller->execute());
    }

    /**
     * Test execute method when provided and actual Company User ids mismatch.
     *
     * @return void
     */
    public function testExecuteIfCompanyUserIdsMismatched()
    {
        $roleId = 2;
        $companyUserIdByRole = 2;
        $companyUserIdCurrent = 1;
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($roleId);

        $role = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();
        $role->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyUserIdByRole);
        $this->roleRepository->expects($this->once())
            ->method('get')
            ->willReturn($role);
        $this->companyUser->expects($this->once())
            ->method('getCurrentCompanyId')
            ->willReturn($companyUserIdCurrent);
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')->with('Bad Request')->willReturnSelf();
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultPage = $this->createPartialMock(
            Page::class,
            ['getConfig']
        );
        $resultConfig = $this->getMockBuilder(Page::class)
            ->addMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultTitle = $this->createPartialMock(
            Title::class,
            ['set']
        );
        $this->resultPageFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [ResultFactory::TYPE_PAGE, [], $resultPage],
                [ResultFactory::TYPE_REDIRECT, [], $result]
            ]);
        $resultPage->expects($this->once())->method('getConfig')->willReturn($resultConfig);
        $resultConfig->expects($this->once())->method('getTitle')->willReturn($resultTitle);
        $result->expects($this->once())->method('setPath')->with('*/role/')->willReturnSelf();
        $this->assertEquals($result, $this->controller->execute());
    }
}
