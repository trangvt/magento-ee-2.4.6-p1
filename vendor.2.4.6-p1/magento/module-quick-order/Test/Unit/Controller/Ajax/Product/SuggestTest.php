<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Controller\Ajax\Product;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\QuickOrder\Controller\Ajax\Product\Suggest;
use Magento\QuickOrder\Model\Config;
use Magento\QuickOrder\Model\Product\Suggest\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SuggestTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Suggest|MockObject
     */
    private $suggest;

    /**
     * @var Config|MockObject
     */
    private $moduleConfigMock;

    /**
     * @var DataProvider|MockObject
     */
    private $suggestDataProviderMock;

    /**
     * @var Json|MockObject
     */
    private $jsonResult;

    /**
     * @var Redirect|MockObject
     */
    private $redirectResult;

    /**
     * @var Http|MockObject
     */
    private $request;

    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->suggestDataProviderMock = $this->getMockBuilder(
            DataProvider::class
        )->disableOriginalConstructor()
            ->getMock();

        $resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonResult = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectResult = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultFactory->expects($this->any())
            ->method('create')
            ->willReturnMap([
                ['json', [], $this->jsonResult],
                ['redirect', [], $this->redirectResult]
            ]);

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->suggest = $this->objectManagerHelper->getObject(
            Suggest::class,
            [
                'moduleConfig' => $this->moduleConfigMock,
                'suggestDataProvider' => $this->suggestDataProviderMock,
                'resultFactory' => $resultFactory,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $this->moduleConfigMock->expects($this->any())
            ->method('isActive')
            ->willReturn(true);

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['q', null, 'query']
            ]);

        $this->suggestDataProviderMock->expects($this->any())
            ->method('getItems')
            ->willReturn([]);

        $this->assertInstanceOf(Json::class, $this->suggest->execute());
    }

    /**
     * Test execute disabled module
     *
     * @return void
     */
    public function testExecuteDisabledModule()
    {
        $this->moduleConfigMock->expects($this->any())
            ->method('isActive')
            ->willReturn(false);

        $this->redirectResult->expects($this->once())
            ->method('setRefererUrl')
            ->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->suggest->execute());
    }
}
