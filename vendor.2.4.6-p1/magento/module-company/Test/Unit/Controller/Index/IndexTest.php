<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Index;

use Magento\Company\Controller\Index\Index;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var ViewInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $view;

    /**
     * @var Index
     */
    private $index;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->view = $this->getMockBuilder(ViewInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadLayout', 'loadLayoutUpdates', 'getPage', 'renderLayout'])
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->index = $objectManager->getObject(
            Index::class,
            [
                '_view' => $this->view,
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
        $phrase = new Phrase('Company Structure');
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
        $this->view->expects($this->once())
            ->method('loadLayout')
            ->willReturnSelf();
        $this->view->expects($this->once())
            ->method('loadLayoutUpdates')
            ->willReturnSelf();
        $this->view->expects($this->once())
            ->method('getPage')
            ->willReturn($resultPage);
        $resultPage->expects($this->once())->method('getConfig')->willReturn($resultConfig);
        $resultConfig->expects($this->once())->method('getTitle')->willReturn($resultTitle);
        $resultTitle->expects($this->once())->method('set')->with($phrase)->willReturnSelf();
        $this->view->expects($this->once())
            ->method('renderLayout')
            ->willReturnSelf();

        $this->index->execute();
    }
}
