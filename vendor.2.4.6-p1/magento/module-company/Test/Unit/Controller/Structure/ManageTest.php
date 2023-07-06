<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Structure;

use Exception;
use Magento\Company\Controller\Structure\Manage;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ManageTest extends TestCase
{
    /**
     * @var  Manage
     */
    private $manage;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJson;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->resultJson = $this->createPartialMock(
            Json::class,
            ['setData']
        );
        $resultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->resultJson);

        $this->structureManager = $this->createMock(Structure::class);
        $this->structureManager->expects($this->any())
            ->method('getAllowedIds')
            ->willReturn(['structures' => [1, 2, 5, 7]]);

        $this->request = $this->createMock(RequestInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->manage = $objectManagerHelper->getObject(
            Manage::class,
            [
                'resultFactory' => $resultFactory,
                'structureManager' => $this->structureManager,
                'logger' => $logger,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test execute.
     *
     * @param int $structureId
     * @param bool $isException
     * @param string $expected
     *
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(int $structureId, bool $isException, string $expected): void
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['structure_id'], ['target_id'])
            ->willReturnOnConsecutiveCalls($structureId, 1);

        if ($isException) {
            $this->structureManager->expects($this->any())
                ->method('moveNode')
                ->with(13, 1)
                ->willThrowException(new Exception());
        }

        $result = '';
        $setDataCallback = function ($data) use (&$result) {
            $result = $data['status'];
        };
        $this->resultJson->expects($this->any())
            ->method('setData')
            ->willReturnCallback($setDataCallback);

        $this->manage->execute();
        $this->assertEquals($expected, $result);
    }

    /**
     * Execute DataProvider.
     *
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [1, false, 'ok'],
            [13, false, 'error'],
            [7, true, 'error']
        ];
    }
}
