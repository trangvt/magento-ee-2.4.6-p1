<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Profile;

use Magento\Company\Api\StatusServiceInterface;
use Magento\Company\Controller\Profile\Index;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IndexTest extends TestCase
{
    /**
     * @var StatusServiceInterface|MockObject
     */
    private $moduleConfig;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Index|MockObject
     */
    private $index;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->resultFactory =
            $this->createPartialMock(ResultFactory::class, ['create']);
        $this->moduleConfig = $this->getMockBuilder(StatusServiceInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isActive'])
            ->getMockForAbstractClass();
        $this->resultJsonFactory = $this
            ->createMock(JsonFactory::class);
        $this->structureManager = $this->createMock(Structure::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $objectManager = new ObjectManager($this);
        $this->index = $objectManager->getObject(
            Index::class,
            [
                'resultFactory' => $this->resultFactory,
                'moduleConfig' => $this->moduleConfig,
                'resultJsonFactory' => $this->resultJsonFactory,
                'structureManager' => $this->structureManager,
                'logger' => $this->logger,

            ]
        );
    }

    /**
     * Test execute
     */
    public function testExecute()
    {
        $resultPage = $this->getMockBuilder(Page::class)
            ->addMethods(['getTitle', 'set'])
            ->onlyMethods(['getConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->any())->method('create')->willReturn($resultPage);
        $resultPage->expects($this->any())->method('getConfig')->willReturnSelf();
        $resultPage->expects($this->any())->method('getTitle')->willReturnSelf();
        $resultPage->expects($this->any())->method('set')->willReturnSelf();

        $this->assertInstanceOf(Page::class, $this->index->execute());
    }
}
