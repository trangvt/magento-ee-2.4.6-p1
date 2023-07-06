<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Email;

use Magento\Company\Model\Email\Transporter;
use Magento\Framework\App\Area;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for @see \Magento\Company\Model\Email\Transporter model.
 */
class TransporterTest extends TestCase
{
    /**
     * @var TransportBuilder|MockObject
     */
    private $transportBuilder;

    /**
     * @var TransportInterface|MockObject
     */
    private $transport;

    /**
     * @var Escaper|MockObject
     */
    private $escaper;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Transporter
     */
    private $transporter;

    /**
     * setUp
     * @return void
     */
    protected function setUp(): void
    {
        $this->transportBuilder = $this
            ->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport = $this->getMockBuilder(TransportInterface::class)
            ->getMock();
        $this->transportBuilder->expects($this->any())->method('getTransport')->willReturn($this->transport);
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->transporter = $objectManagerHelper->getObject(
            Transporter::class,
            [
                'transportBuilder' => $this->transportBuilder,
                'escaper' => $this->escaper,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * @param string $customerEmail
     * @param string $customerName
     * @param string $from
     * @param string $templateId
     * @param array $templateParams
     * @param null $storeId
     * @param array $bcc
     * @dataProvider sendMessageDataProvider
     */
    public function testSendMessage(
        $customerEmail,
        $customerName,
        $from,
        $templateId,
        $templateParams,
        $storeId,
        $bcc
    ) {
        $this->transportBuilder
            ->expects($this->once())
            ->method('setTemplateIdentifier')
            ->with($templateId)
            ->willReturnSelf();
        $this->transportBuilder
            ->expects($this->once())
            ->method('setTemplateOptions')
            ->with(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
            ->willReturnSelf();
        $this->transportBuilder
            ->expects($this->once())
            ->method('setTemplateVars')
            ->with(['escaper' => $this->escaper])
            ->willReturnSelf();
        $this->transportBuilder
            ->expects($this->once())
            ->method('addTo')
            ->with($customerEmail, $customerName)
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())->method('setFromByScope')->with($from)->willReturnSelf();
        $this->transportBuilder->expects($this->once())->method('addBcc')->with($bcc)->willReturnSelf();
        $this->transportBuilder->expects($this->once())->method('getTransport')->with()->willReturn($this->transport);
        $this->transport->expects($this->once())->method('sendMessage');
        $this->transporter->sendMessage(
            $customerEmail,
            $customerName,
            $from,
            $templateId,
            $templateParams,
            $storeId,
            $bcc
        );
    }

    /**
     * @param string $customerEmail
     * @param string $customerName
     * @param string $from
     * @param string $templateId
     * @param array $templateParams
     * @param null $storeId
     * @param array $bcc
     * @dataProvider sendMessageDataProvider
     */
    public function testSendMessageWithException(
        $customerEmail,
        $customerName,
        $from,
        $templateId,
        $templateParams,
        $storeId,
        $bcc
    ) {
        $this->transportBuilder
            ->expects($this->once())
            ->method('setTemplateIdentifier')
            ->with($templateId)
            ->willReturnSelf();
        $this->transportBuilder
            ->expects($this->once())
            ->method('setTemplateOptions')
            ->with(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
            ->willReturnSelf();
        $this->transportBuilder
            ->expects($this->once())
            ->method('setTemplateVars')
            ->with(['escaper' => $this->escaper])
            ->willReturnSelf();
        $this->transportBuilder
            ->expects($this->once())
            ->method('addTo')
            ->with($customerEmail, $customerName)
            ->willReturnSelf();
        $this->transportBuilder->expects($this->once())->method('setFromByScope')->with($from)->willReturnSelf();
        $this->transportBuilder->expects($this->once())->method('addBcc')->with($bcc)->willReturnSelf();
        $this->transportBuilder
            ->expects($this->once())
            ->method('getTransport')
            ->with()
            ->willReturn($this->transport);
        $exception = new MailException(__('error message'));
        $this->transport->expects($this->once())->method('sendMessage')->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);
        $this->transporter->sendMessage(
            $customerEmail,
            $customerName,
            $from,
            $templateId,
            $templateParams,
            $storeId,
            $bcc
        );
    }

    /**
     * @return array
     */
    public function sendMessageDataProvider()
    {
        return [
            [
                'customer@email.com',
                'customer name',
                'from',
                'templateId',
                [],
                null,
                []
            ]
        ];
    }
}
