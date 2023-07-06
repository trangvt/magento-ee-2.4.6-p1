<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Sales\Controller\Order;

use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Plugin\Sales\Controller\Order\HistoryPlugin;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Sales\Controller\Order\History;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Company\Plugin\Sales\Controller\Order\HistoryPlugin.
 */
class HistoryPluginTest extends TestCase
{
    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var \Magento\Sales\Controller\Order\HistoryPlugin
     */
    private $historyPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultRedirectFactory = $this
            ->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->historyPlugin = $objectManager->getObject(
            HistoryPlugin::class,
            [
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'authorization' => $this->authorization,
                'companyContext' => $this->companyContext,
            ]
        );
    }

    /**
     * Test afterExecute() method.
     *
     * @return void
     */
    public function testAfterExecute()
    {
        $controller = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorization->expects($this->once())
            ->method('isAllowed')->with('Magento_Sales::view_orders')->willReturn(false);
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $this->companyContext->expects($this->once())->method('isModuleActive')->willReturn(true);
        $this->companyContext->expects($this->once())->method('isCurrentUserCompanyUser')->willReturn(true);
        $resultRedirect->expects($this->once())->method('setPath')->with('company/accessdenied')->willReturnSelf();
        $this->assertEquals($resultRedirect, $this->historyPlugin->afterExecute($controller, $result));
    }

    /**
     * Test afterExecute() method with view permissions.
     *
     * @return void
     */
    public function testAfterExecuteWithViewPermissions()
    {
        $controller = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorization->expects($this->once())
            ->method('isAllowed')->with('Magento_Sales::view_orders')->willReturn(true);
        $this->assertEquals($result, $this->historyPlugin->afterExecute($controller, $result));
    }
}
