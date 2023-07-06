<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Structure;

use Magento\Company\Block\Company\Management;
use Magento\Company\Controller\Structure\Get;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetTest extends TestCase
{
    /**
     * @var Get
     */
    protected $get;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJson;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $resultJsonFactory = $this->createPartialMock(
            JsonFactory::class,
            ['create']
        );
        $this->resultJson = $this->createPartialMock(
            Json::class,
            ['setData']
        );
        $resultJsonFactory->expects($this->any())
            ->method('create')->willReturn($this->resultJson);

        $layoutFactory = $this->createPartialMock(
            LayoutFactory::class,
            ['create']
        );
        $layout = $this->createPartialMock(
            Layout::class,
            ['createBlock']
        );
        $block = $this->createMock(
            Management::class
        );
        $structureManager = $this->createMock(
            Structure::class
        );
        $layoutFactory->expects($this->any())
            ->method('create')->willReturn($layout);
        $layout->expects($this->any())
            ->method('createBlock')->willReturn($block);
        $block->expects($this->once())->method('getTree')->willReturn([]);

        $logger = $this->createMock(
            LoggerInterface::class
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->get = $objectManagerHelper->getObject(
            Get::class,
            [
                'resultJsonFactory' => $resultJsonFactory,
                'layoutFactory' => $layoutFactory,
                'structureManager' => $structureManager,
                'logger' => $logger
            ]
        );
    }

    /**
     * Test execute
     */
    public function testExecute()
    {
        $result = '';
        $setDataCallback = function ($data) use (&$result) {
            $result = $data['status'];
        };
        $this->resultJson->expects($this->any())->method('setData')->willReturnCallback($setDataCallback);
        $this->get->execute();
    }
}
