<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Account;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Company\Controller\Account\Create;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject_MockObject;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var Create
     */
    private $create;

    /**
     * @var Session|MockObject
     */
    private $customerSession;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->customerSession = $this->createMock(Session::class);
        $objectManager = new ObjectManager($this);
        $this->create = $objectManager->getObject(
            Create::class,
            [
                'resultFactory' => $this->resultFactory,
                'customerSession' => $this->customerSession
            ]
        );
    }

    /**
     * Test for method execute
     */
    public function testExecuteLoggedIn()
    {
        $isLoggedIn = true;
        $this->customerSession->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn($isLoggedIn);

        $this->checkIfCreatePageOpens();
    }

    /**
     * Test for method execute
     */
    public function testExecuteNotLoggedIn()
    {
        $isLoggedIn = false;
        $this->customerSession->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn($isLoggedIn);

        $this->checkIfCreatePageOpens();
    }

    /**
     * Executing Create company page controllers action and checks result
     *
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    private function checkIfCreatePageOpens()
    {
        $resultPage = $this->createPartialMock(
            Page::class,
            ['getConfig']
        );

        $title = $this->createMock(Title::class);
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('getTitle')->willReturn($title);
        $resultPage->expects($this->once())->method('getConfig')->willReturn($config);
        $this->resultFactory->expects($this->once())->method('create')->willReturn($resultPage);

        $this->assertEquals($resultPage, $this->create->execute());
    }
}
