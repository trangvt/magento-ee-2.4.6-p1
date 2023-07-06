<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Account;

use Magento\Company\Controller\Account\Index;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var ResultFactory|\PHPUnit\Framework\MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var Index
     */
    private $index;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $objectManager = new ObjectManager($this);
        $this->index = $objectManager->getObject(
            Index::class,
            [
                'resultFactory' => $this->resultFactory,
            ]
        );
    }

    /**
     * Test for method execute
     */
    public function testExecute()
    {
        $resultPage = $this->createMock(Page::class);
        $title = $this->createMock(Title::class);
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('getTitle')->willReturn($title);
        $resultPage->expects($this->once())->method('getConfig')->willReturn($config);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($resultPage);
        $this->assertEquals($resultPage, $this->index->execute());
    }
}
