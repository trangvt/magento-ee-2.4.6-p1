<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Requisition;

use Magento\Framework\App\Console\Request;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Controller\Requisition\Delete;
use Magento\RequisitionList\Model\Action\RequestValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for \Magento\RequisitionList\Controller\Requisition\Delete class.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteTest extends TestCase
{
    /**
     * @var RequestValidator|MockObject
     */
    private $requestValidator;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Delete
     */
    private $deleteMock;

    /**
     * @var Request|MockObject
     */
    private $request;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Redirect|MockObject
     */
    private $resultRedirect;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->resultFactory =
            $this->createPartialMock(ResultFactory::class, ['create']);
        $this->requestValidator = $this->createMock(RequestValidator::class);
        $this->requisitionListRepository =
            $this->getMockForAbstractClass(RequisitionListRepositoryInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->resultRedirect = $this->createPartialMock(
            Redirect::class,
            ['setPath', 'setRefererUrl']
        );
        $objectManager = new ObjectManager($this);
        $this->deleteMock = $objectManager->getObject(
            Delete::class,
            [
                'request' => $this->request,
                'resultFactory' => $this->resultFactory,
                'requestValidator' => $this->requestValidator,
                'requisitionListRepository' => $this->requisitionListRepository,
                'logger' => $this->logger,

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
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn(null);
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $this->prepareResultRedirect();

        $this->assertInstanceOf(ResultInterface::class, $this->deleteMock->execute());
    }

    /**
     * Test execute method not allowed action.
     *
     * @return void
     */
    public function testExecuteWithNotAllowedAction()
    {
        $this->resultRedirect->expects($this->any())->method('setPath')->willReturnSelf();
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn($this->resultRedirect);
        $this->requisitionListRepository->expects($this->never())->method('deleteById');

        $this->assertInstanceOf(Redirect::class, $this->deleteMock->execute());
    }

    /**
     * Test execute with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn(null);
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $this->prepareResultRedirect();
        $exception = new \Exception();
        $this->requisitionListRepository->expects($this->any())->method('deleteById')->willThrowException($exception);

        $this->assertInstanceOf(ResultInterface::class, $this->deleteMock->execute());
    }

    /**
     * Test execute with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn(null);
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $this->prepareResultRedirect();
        $phrase = new Phrase('exception');
        $localizedException = new LocalizedException($phrase);
        $this->requisitionListRepository->expects($this->any())->method('deleteById')
            ->willThrowException($localizedException);

        $this->assertInstanceOf(ResultInterface::class, $this->deleteMock->execute());
    }

    /**
     * Prepare result redirect.
     *
     * @return void
     */
    private function prepareResultRedirect()
    {
        $this->resultRedirect->expects($this->any())->method('setPath')->willReturnSelf();
        $this->resultRedirect->expects($this->any())->method('setRefererUrl')->willReturnSelf();
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->resultRedirect);
    }
}
