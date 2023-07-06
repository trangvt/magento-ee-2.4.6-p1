<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Team;

use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Controller\Team\Delete;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test Magento\Company\Controller\Team\Delete class.
 */
class DeleteTest extends TestCase
{
    /**
     * @var Delete
     */
    private $delete;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Json|MockObject
     */
    private $resultJson;

    /**
     * @var TeamRepositoryInterface|MockObject
     */
    private $teamRepository;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->structureManager = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->teamRepository = $this->getMockBuilder(TeamRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->delete = $objectManagerHelper->getObject(
            Delete::class,
            [
                'resultFactory' => $this->resultFactory,
                'structureManager' => $this->structureManager,
                'teamRepository' => $this->teamRepository,
                'logger' => $this->logger,
                '_request' => $this->request,
                'companyContext' => $this->companyContext,
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
        $customerId = 1;
        $teamId = 1;
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($customerId)
            ->willReturn(['teams' => [1, 5, 6]]);
        $this->request->expects($this->once())->method('getParam')->with('team_id')->willReturn($teamId);
        $this->teamRepository->expects($this->once())->method('deleteById')->with($teamId);
        $this->resultJson->expects($this->once())->method('setData')->with(
            [
                'status' => 'ok',
                'message' => __('The team was successfully deleted.'),
                'data' => [],
            ]
        )->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->resultJson);

        $this->assertEquals($this->resultJson, $this->delete->execute());
    }

    /**
     * Test execute method with invalid team id.
     *
     * @return void
     */
    public function testExecuteWithInvalidTeamId()
    {
        $customerId = 1;
        $teamId = 1;
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($customerId)
            ->willReturn(['teams' => [5, 6]]);
        $this->request->expects($this->once())->method('getParam')->with('team_id')->willReturn($teamId);
        $this->resultJson->expects($this->once())->method('setData')->with(
            [
                'status' => 'error',
                'message' => __('You are not allowed to do this.'),
                'payload' => [],
            ]
        )->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->resultJson);

        $this->assertEquals($this->resultJson, $this->delete->execute());
    }

    /**
     * Test execute method with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $customerId = 1;
        $teamId = 1;
        $exception = new LocalizedException(__('Localized Exception.'));
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($customerId)
            ->willReturn(['teams' => [1, 5, 6]]);
        $this->request->expects($this->once())->method('getParam')->with('team_id')->willReturn($teamId);
        $this->teamRepository->expects($this->once())->method('deleteById')->willThrowException($exception);
        $this->resultJson->expects($this->once())->method('setData')->with(
            [
                'status' => 'error',
                'message' => __('Localized Exception.'),
                'payload' => [],
            ]
        )->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->resultJson);

        $this->assertEquals($this->resultJson, $this->delete->execute());
    }

    /**
     * Test execute method with generic exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $customerId = 1;
        $teamId = 1;
        $exception = new \Exception();
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($customerId)
            ->willReturn(['teams' => [1, 5, 6]]);
        $this->request->expects($this->once())->method('getParam')->with('team_id')->willReturn($teamId);
        $this->teamRepository->expects($this->once())->method('deleteById')->willThrowException($exception);
        $this->resultJson->expects($this->once())->method('setData')->with(
            [
                'status' => 'error',
                'message' => __('Something went wrong.'),
                'payload' => [],
            ]
        )->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->resultJson);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->assertEquals($this->resultJson, $this->delete->execute());
    }
}
