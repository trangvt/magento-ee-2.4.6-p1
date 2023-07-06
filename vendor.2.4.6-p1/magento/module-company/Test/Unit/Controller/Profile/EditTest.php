<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Profile;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\StatusServiceInterface;
use Magento\Company\Controller\Profile\Edit;
use Magento\Company\Model\CompanyAdminPermission;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditTest extends TestCase
{
    /**
     * @var CompanyAdminPermission|\PHPUnit\Framework\MockObject_MockObject
     */
    private $companyAdminPermission;

    /**
     * @var RedirectFactory|\PHPUnit\Framework\MockObject_MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Edit|\PHPUnit\Framework\MockObject_MockObject
     */
    private $edit;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $moduleConfig = $this->getMockBuilder(StatusServiceInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isActive'])
            ->getMockForAbstractClass();
        $userContext = $this->createMock(
            UserContextInterface::class
        );
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->companyAdminPermission = $this->createMock(
            CompanyAdminPermission::class
        );
        $this->resultRedirectFactory = $this->createPartialMock(
            RedirectFactory::class,
            ['create']
        );
        $resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $resultPage = $this->getMockBuilder(Page::class)
            ->addMethods(['getTitle', 'set', 'getBlock', 'setData'])
            ->onlyMethods(['getConfig', 'getLayout'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultPage->expects($this->any())->method('getConfig')->willReturnSelf();
        $resultPage->expects($this->any())->method('getTitle')->willReturnSelf();
        $resultPage->expects($this->any())->method('set')->willReturnSelf();
        $resultPage->expects($this->any())->method('getLayout')->willReturnSelf();
        $resultPage->expects($this->any())->method('getBlock')->willReturnSelf();
        $resultPage->expects($this->any())->method('setData')->willReturnSelf();
        $resultFactory->expects($this->any())->method('create')->willReturn($resultPage);

        $objectManager = new ObjectManager($this);
        $this->edit = $objectManager->getObject(
            Edit::class,
            [
                'resultFactory' => $resultFactory,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'moduleConfig' => $moduleConfig,
                'customerContext' => $userContext,
                'logger' => $logger,
                'companyAdminPermission' => $this->companyAdminPermission
            ]
        );
    }

    /**
     * Test execute.
     *
     * @param bool $isCurrentUserCompanyAdmin
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($isCurrentUserCompanyAdmin)
    {
        $this->prepareResultRedirect();
        $this->companyAdminPermission->expects($this->any())
            ->method('isCurrentUserCompanyAdmin')
            ->willReturn($isCurrentUserCompanyAdmin);

        $this->assertInstanceOf(Page::class, $this->edit->execute());
    }

    /**
     * Prepare resultRedirect.
     *
     * @return void
     */
    private function prepareResultRedirect()
    {
        $resultRedirect = $this->createMock(Redirect::class);
        $resultRedirect->expects($this->any())->method('setPath')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($resultRedirect);
    }

    /**
     * DataProvider execute.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [true]
        ];
    }
}
