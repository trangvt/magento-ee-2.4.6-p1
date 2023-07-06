<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Exception;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Controller\Adminhtml\Index\InlineEdit;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\Collection;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InlineEditTest extends TestCase
{
    /**
     * @var InlineEdit
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var CompanyInterface|MockObject
     */
    private $companyData;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var Json|MockObject
     */
    private $resultJson;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $dataObjectHelper;

    /**
     * @var Collection|MockObject
     */
    private $messageCollection;

    /**
     * @var MessageInterface|MockObject
     */
    private $message;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var array
     */
    private $items;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->request = $this->getMockForAbstractClass(RequestInterface::class, [], '', false);
        $this->messageManager = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false
        );
        $this->companyData = $this->getMockForAbstractClass(
            CompanyInterface::class,
            [],
            '',
            false
        );
        $this->resultJsonFactory = $this->createPartialMock(
            JsonFactory::class,
            ['create']
        );
        $this->resultJson = $this->createMock(Json::class);
        $this->companyRepository = $this->getMockForAbstractClass(
            CompanyRepositoryInterface::class,
            [],
            '',
            false
        );
        $this->dataObjectHelper = $this->createMock(DataObjectHelper::class);
        $this->messageCollection = $this->createMock(Collection::class);
        $this->message = $this->getMockForAbstractClass(
            MessageInterface::class,
            [],
            '',
            false
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class, [], '', false);
        $this->controller = $objectManager->getObject(
            InlineEdit::class,
            [
                'companyRepository' => $this->companyRepository,
                'resultJsonFactory' => $this->resultJsonFactory,
                'dataObjectHelper' => $this->dataObjectHelper,
                'logger' => $this->logger,
                '_request' => $this->request,
                'messageManager' => $this->messageManager
            ]
        );

        $this->items = [
            14 => [
                'email' => 'test@test.ua'
            ]
        ];
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->prepareMocksForTesting();
        $this->companyRepository->expects($this->once())
            ->method('save')
            ->with($this->companyData);
        $this->prepareMocksForErrorMessagesProcessing();
        $this->assertSame($this->resultJson, $this->controller->execute());
    }

    /**
     * Test for method execute without items.
     *
     * @return void
     */
    public function testExecuteWithoutItems(): void
    {
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJson);
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['items', []], ['isAjax'])
            ->willReturnOnConsecutiveCalls([], false);
        $this->resultJson
            ->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error' => true
                ]
            )
            ->willReturnSelf();
        $this->assertSame($this->resultJson, $this->controller->execute());
    }

    /**
     * Test for method execute with localized exception.
     *
     * @return void
     */
    public function testExecuteLocalizedException(): void
    {
        $exception = new LocalizedException(__('Exception message'));
        $this->prepareMocksForTesting();
        $this->companyRepository->expects($this->once())
            ->method('save')
            ->with($this->companyData)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('[Company ID: 12] can not be saved');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->prepareMocksForErrorMessagesProcessing();
        $this->assertSame($this->resultJson, $this->controller->execute());
    }

    /**
     * Test for method execute with input exception.
     *
     * @return void
     */
    public function testExecuteInputException(): void
    {
        $exception = new InputException(__('Exception message'));
        $this->prepareMocksForTesting();
        $this->companyRepository->expects($this->once())
            ->method('save')
            ->with($this->companyData)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('[Company ID: 12] can not be saved');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->prepareMocksForErrorMessagesProcessing();
        $this->assertSame($this->resultJson, $this->controller->execute());
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteException(): void
    {
        $exception = new Exception('Exception message');
        $this->prepareMocksForTesting();
        $this->companyRepository->expects($this->once())
            ->method('save')
            ->with($this->companyData)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('[Company ID: 12] can not be saved');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->prepareMocksForErrorMessagesProcessing();
        $this->assertSame($this->resultJson, $this->controller->execute());
    }

    /**
     * Prepare mocks for testing.
     *
     * @return void
     */
    private function prepareMocksForTesting(): void
    {
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJson);
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['items', []], ['isAjax'])
            ->willReturnOnConsecutiveCalls($this->items, true);
        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with(14)
            ->willReturn($this->companyData);
        $this->dataObjectHelper->expects($this->any())
            ->method('populateWithArray')
            ->with(
                $this->companyData,
                [
                    'email' => 'test@test.ua'
                ],
                CompanyInterface::class
            );
        $this->companyData->expects($this->any())
            ->method('getId')
            ->willReturn(12);
    }

    /**
     * Prepare mocks for error messages processing.
     *
     * @return void
     */
    private function prepareMocksForErrorMessagesProcessing(): void
    {
        $this->messageManager->expects($this->atLeastOnce())
            ->method('getMessages')
            ->willReturn($this->messageCollection);
        $this->messageCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->message]);
        $this->messageCollection->expects($this->once())
            ->method('getCount')
            ->willReturn(1);
        $this->message->expects($this->once())
            ->method('getText')
            ->willReturn('Error text');
        $this->resultJson->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'messages' => ['Error text'],
                    'error' => true
                ]
            )
            ->willReturnSelf();
    }
}
