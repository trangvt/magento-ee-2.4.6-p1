<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Requisition;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\RequisitionList\Controller\Requisition\Index;
use Magento\RequisitionList\Model\Action\RequestValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class ActionTest extends TestCase
{
    /**
     * @var RequestValidator|MockObject
     */
    protected $requestValidator;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactory;

    /**
     * @var Index
     */
    protected $mock;

    /**
     * @var string
     */
    protected $mockClass;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->requestValidator = $this->createMock(RequestValidator::class);
        $this->resultFactory =
            $this->createPartialMock(ResultFactory::class, ['create']);
        $objectManager = new ObjectManager($this);
        $this->mock = $objectManager->getObject(
            'Magento\RequisitionList\Controller\Requisition\\' . $this->mockClass,
            [
                'resultFactory' => $this->resultFactory,
                'requestValidator' => $this->requestValidator
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn(null);
        $resultPage = $this->createMock(Page::class);
        $title = $this->createPartialMock(Title::class, ['set']);
        $title->expects($this->any())->method('set')->willReturnSelf();
        $pageConfig = $this->createMock(Config::class);
        $pageConfig->expects($this->any())->method('getTitle')->willReturn($title);
        $layout = $this->getMockBuilder(Layout::class)
            ->addMethods(['setActive'])
            ->onlyMethods(['getBlock'])
            ->disableOriginalConstructor()
            ->getMock();
        $layout->expects($this->atLeastOnce())->method('getBlock')->willReturn($layout);
        $layout->expects($this->once())->method('setActive')->willReturnSelf();
        $resultPage->expects($this->any())->method('getConfig')->willReturn($pageConfig);
        $resultPage->expects($this->any())->method('getLayout')->willReturn($layout);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($resultPage);

        $this->assertInstanceOf(Page::class, $this->mock->execute());
    }

    /**
     * Test execute method not allowed action
     */
    public function testExecuteWithNotAllowedAction()
    {
        $resultRedirect = $this->createMock(Redirect::class);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($resultRedirect);
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn($resultRedirect);

        $this->assertInstanceOf(Redirect::class, $this->mock->execute());
    }
}
