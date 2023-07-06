<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\Page;
use Magento\Company\Controller\Adminhtml\Index\Index;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

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
        $this->resultPageFactory = $this->createPartialMock(
            PageFactory::class,
            ['create']
        );

        $objectManager = new ObjectManager($this);
        $this->index = $objectManager->getObject(
            Index::class,
            [
                'resultPageFactory' => $this->resultPageFactory
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
        $phrase = new Phrase('Companies');
        $resultPage = $this->createPartialMock(
            Page::class,
            ['getConfig', 'setActiveMenu', 'addBreadcrumb']
        );
        $resultConfig = $this->getMockBuilder(\Magento\Framework\View\Result\Page::class)->addMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultTitle = $this->createPartialMock(
            Title::class,
            ['prepend']
        );
        $this->resultPageFactory->expects($this->once())->method('create')->willReturn($resultPage);
        $resultPage->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Company::company_index')
            ->willReturnSelf();
        $resultPage->expects($this->once())->method('getConfig')->willReturn($resultConfig);
        $resultConfig->expects($this->once())->method('getTitle')->willReturn($resultTitle);
        $resultTitle->expects($this->once())->method('prepend')->with($phrase)->willReturnSelf();
        $resultPage->expects($this->once())
            ->method('addBreadcrumb')
            ->with($phrase, $phrase)
            ->willReturnSelf();

        $this->assertEquals($resultPage, $this->index->execute());
    }
}
