<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Controller\Index;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\QuickOrder\Controller\Index\Index;
use Magento\QuickOrder\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\QuickOrder\Controller\Index\Index class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends TestCase
{
    /**
     * @var Index
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var RedirectInterface|MockObject
     */
    private $redirect;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var Config|MockObject
     */
    private $moduleConfig;

    /**
     * @var \Magento\AdvancedCheckout\Helper\Data|MockObject
     */
    private $helper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['getFullActionName', 'getRouteName', 'isDispatched']
        );

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->getMockForAbstractClass();

        $this->redirect = $this->getMockBuilder(RedirectInterface::class)
            ->getMockForAbstractClass();

        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->helper = $this->createMock(Data::class);
        $this->objectManager->expects($this->any())->method('get')->willReturn($this->helper);

        $eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();

        $this->resultPageFactory =
            $this->createPartialMock(PageFactory::class, ['create']);
        $this->moduleConfig = $this->createMock(Config::class);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Index::class,
            [
                'moduleConfig' => $this->moduleConfig,
                'resultPageFactory' => $this->resultPageFactory,
                '_redirect' => $this->redirect,
                '_request' => $this->request,
                '_response' => $response,
                '_eventManager' => $eventManager,
                '_objectManager' => $this->objectManager,
            ]
        );
    }

    /**
     * Test for execute() method.
     *
     * @return void
     */
    public function testExecute()
    {
        $page = $this->createMock(Page::class);
        $this->resultPageFactory->expects($this->any())->method('create')->willReturn($page);

        $config = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $page->expects($this->once())->method('getConfig')->willReturn($config);

        $title = $this->createPartialMock(Title::class, ['set']);
        $config->expects($this->once())->method('getTitle')->willReturn($title);

        $this->assertEquals($page, $this->controller->execute());
    }

    /**
     * Test for dispatch() method.
     *
     * @param bool $isSkuEnabled
     * @param bool $isModuleActive
     * @param bool $isRedirectExpected
     * @return void
     * @dataProvider dispatchDataProvider
     */
    public function testDispatch($isSkuEnabled, $isModuleActive, $isRedirectExpected)
    {
        $this->helper->expects($this->any())->method('isSkuEnabled')->willReturn($isSkuEnabled);
        $this->helper->expects($this->any())->method('isSkuApplied')->willReturn($isSkuEnabled);
        $this->moduleConfig->expects($this->any())->method('isActive')->willReturn($isModuleActive);
        $this->redirect->expects($isRedirectExpected ? $this->once() : $this->never())->method('redirect');

        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->controller->dispatch($this->request)
        );
    }

    /**
     * Test for dispatch() method with exception.
     *
     * @return void
     */
    public function testDispatchWithException()
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->helper->expects($this->any())->method('isSkuEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isSkuApplied')->willReturn(true);
        $this->moduleConfig->expects($this->any())->method('isActive')->willReturn(false);
        $this->redirect->expects($this->never())->method('redirect');

        $this->controller->dispatch($this->request);
    }

    /**
     * Data provider dispatch.
     *
     * @return array
     */
    public function dispatchDataProvider()
    {
        return [
            [false, true, true],
            [true, true, false],
        ];
    }
}
