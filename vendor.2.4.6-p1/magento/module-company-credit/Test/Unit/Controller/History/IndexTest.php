<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Controller\History;

use Magento\CompanyCredit\Controller\History\Index;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

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
        $this->resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->index = $objectManager->getObject(
            Index::class,
            [
                'resultFactory' => $this->resultFactory,
                '_request' => $this->request,
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $resultPage = $this->createPartialMock(
            Page::class,
            ['getConfig', 'getLayout']
        );
        $title = $this->createMock(Title::class);
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('getTitle')->willReturn($title);
        $resultPage->expects($this->once())->method('getConfig')->willReturn($config);
        $title->expects($this->once())->method('set')->with(__('Company Credit'))->willReturnSelf();
        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $resultPage->expects($this->once())->method('getLayout')->willReturn($layout);
        $block = $this->getMockForAbstractClass(
            BlockInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setActive']
        );
        $layout->expects($this->once())->method('getBlock')
            ->with('customer-account-navigation-company-credit-history-link')->willReturn($block);
        $block->expects($this->once())->method('setActive')->with('company_credit/history')->willReturnSelf();
        $this->resultFactory->expects($this->once())
            ->method('create')->with(ResultFactory::TYPE_PAGE)->willReturn($resultPage);
        $this->assertEquals($resultPage, $this->index->execute());
    }
}
