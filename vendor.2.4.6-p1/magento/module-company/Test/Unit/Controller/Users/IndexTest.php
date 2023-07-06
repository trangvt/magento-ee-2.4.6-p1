<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Users;

use Magento\Company\Controller\Users\Index;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

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
        $this->companyContext = $this->createMock(
            CompanyContext::class
        );
        $this->resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->resultRedirectFactory = $this->createPartialMock(
            RedirectFactory::class,
            ['create']
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->index = $objectManagerHelper->getObject(
            Index::class,
            [
                'companyContext' => $this->companyContext,
                'resultFactory' => $this->resultFactory,
                'resultRedirectFactory' => $this->resultRedirectFactory,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn(1);
        $result = $this->createMock(Page::class);
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_PAGE)->willReturn($result);
        $config = $this->createMock(Config::class);
        $result->expects($this->once())->method('getConfig')->willReturn($config);
        $title = $this->createMock(Title::class);
        $config->expects($this->once())->method('getTitle')->willReturn($title);
        $title->expects($this->once())->method('set')->with(__('Company Users'))->willReturnSelf();
        $this->assertEquals($result, $this->index->execute());
    }

    /**
     * Test for execute method with empty user id.
     *
     * @return void
     */
    public function testExecuteWithEmptyUserId()
    {
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn(null);
        $result = $this->createMock(Redirect::class);
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($result);
        $result->expects($this->once())->method('setRefererUrl')->willReturnSelf();
        $this->assertEquals($result, $this->index->execute());
    }
}
