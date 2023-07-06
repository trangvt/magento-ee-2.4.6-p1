<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Controller\Adminhtml\Index\NewAction;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewActionTest extends TestCase
{
    protected $new;

    /**
     * @var RequestInterface|MockObject
     */
    protected $request;

    /**
     * @var Title
     */
    protected $title;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $resultForwardFactory = $this->createMock(ForwardFactory::class);
        $pageFactory = $this->createPartialMock(
            PageFactory::class,
            ['create']
        );
        $page = $this->getMockBuilder(Page::class)
            ->addMethods(['setActiveMenu'])
            ->onlyMethods(['getConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        $config = $this->createMock(Config::class);
        $this->title = $this->createMock(Title::class);
        $config->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->title);
        $page->expects($this->atLeastOnce())->method('getConfig')->willReturn($config);
        $pageFactory->expects($this->atLeastOnce())->method('create')->willReturn($page);

        $companyRepository = $this->getMockForAbstractClass(CompanyRepositoryInterface::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $coreRegistry = $this->createMock(Registry::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->new = $objectManagerHelper->getObject(
            NewAction::class,
            [
                'coreRegistry' => $coreRegistry,
                'resultForwardFactory' => $resultForwardFactory,
                'resultPageFactory' => $pageFactory,
                'companyRepository' => $companyRepository,
                '_request' => $this->request
            ]
        );
    }

    /**
     * @dataProvider dataForExecute
     *
     * @param $companyId
     * @param $title
     */
    public function testExecute($companyId, $title)
    {
        $this->request->expects($this->any())->method('getParam')->willReturn($companyId);
        $result = '';
        $prependCallback = function ($prefix) use (&$result) {
            $result = $prefix;
        };
        $this->title->expects($this->once())->method('prepend')->willReturnCallback($prependCallback);

        $this->new->execute();
        $this->assertEquals($title, $result);
    }

    public function dataForExecute()
    {
        return [
            [0, 'New Company']
        ];
    }
}
