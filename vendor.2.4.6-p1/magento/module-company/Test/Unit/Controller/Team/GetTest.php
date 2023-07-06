<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Team;

use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Controller\Team\Get;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetTest extends TestCase
{
    /**
     * @var Get
     */
    private $get;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJson;

    /**
     * @var TeamRepositoryInterface|MockObject
     */
    private $teamRepository;

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
        $this->structureManager = $this->createMock(
            Structure::class
        );
        $this->structureManager->expects($this->any())
            ->method('getAllowedIds')->willReturn(
                [
                    'teams' => [1, 2, 5, 7]
                ]
            );
        $this->teamRepository = $this->createMock(
            TeamRepositoryInterface::class
        );
        $this->request = $this->createMock(
            RequestInterface::class
        );
        $resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->resultJson = $this->createPartialMock(
            Json::class,
            ['setData']
        );
        $resultFactory->expects($this->any())
            ->method('create')->willReturn($this->resultJson);

        $logger = $this->createMock(
            LoggerInterface::class
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->get = $objectManagerHelper->getObject(
            Get::class,
            [
                'resultFactory' => $resultFactory,
                'structureManager' => $this->structureManager,
                'teamRepository' => $this->teamRepository,
                'logger' => $logger,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test execute.
     *
     * @param int $teamId
     * @param bool $isException
     * @param string $expect
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($teamId, $isException, $expect)
    {
        $this->request->expects($this->once())->method('getParam')->with('team_id')->willReturn($teamId);

        if ($isException) {
            $this->teamRepository->expects($this->any())
                ->method('get')->willThrowException(new \Exception());
        } else {
            $team = $this->getMockBuilder(TeamInterface::class)
                ->setMethods(['getData'])
                ->getMockForAbstractClass();

            $this->teamRepository->expects($this->any())
                ->method('get')->willReturn($team);
            $team->expects($this->any())->method('getData')->willReturn([]);
        }

        $result = '';
        $setDataCallback = function ($data) use (&$result) {
            $result = $data['status'];
        };

        $this->resultJson->expects($this->any())->method('setData')->willReturnCallback($setDataCallback);
        $this->get->execute();
        $this->assertEquals($expect, $result);
    }

    /**
     * Execute DataProvider.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [1, false, 'ok'],
            [2, true, 'error'],
            [2, true, 'error'],
            [4, true, 'error'],
        ];
    }
}
