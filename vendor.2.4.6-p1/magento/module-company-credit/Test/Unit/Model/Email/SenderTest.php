<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\Email;

use Magento\CompanyCredit\Model\Config\EmailTemplate;
use Magento\CompanyCredit\Model\Email\CompanyCreditDataFactory;
use Magento\CompanyCredit\Model\Email\NotificationRecipientLocator;
use Magento\CompanyCredit\Model\Email\Sender;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SenderTest extends TestCase
{
    /**
     * @var TransportBuilder|MockObject
     */
    private $transportBuilder;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CompanyCreditDataFactory|MockObject
     */
    private $companyCreditDataFactory;

    /**
     * @var EmailTemplate|MockObject
     */
    private $emailTemplateConfig;

    /**
     * @var NotificationRecipientLocator|MockObject
     */
    private $notificationRecipient;

    /**
     * @var Sender
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->transportBuilder = $this->createPartialMock(
            TransportBuilder::class,
            [
                'setFromByScope',
                'addTo',
                'getTransport',
                'addBcc',
                'setTemplateIdentifier',
                'setTemplateVars',
                'setTemplateOptions'
            ]
        );
        $this->logger = $this->createMock(
            LoggerInterface::class
        );
        $this->companyCreditDataFactory = $this->createMock(
            CompanyCreditDataFactory::class
        );
        $this->emailTemplateConfig = $this->createMock(
            EmailTemplate::class
        );
        $this->notificationRecipient = $this->createMock(
            NotificationRecipientLocator::class
        );

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Sender::class,
            [
                'transportBuilder' => $this->transportBuilder,
                'logger' => $this->logger,
                'companyCreditDataFactory' => $this->companyCreditDataFactory,
                'emailTemplateConfig' => $this->emailTemplateConfig,
                'notificationRecipient' => $this->notificationRecipient,
            ]
        );
    }

    /**
     * Test sendCompanyCreditChangedNotificationEmail method.
     *
     * @return void
     */
    public function testSendCompanyCreditChangedNotificationEmail()
    {
        $storeId = 1;
        $templateId = 'company_email_credit_allocated_email_template';
        $copyTo = 'info@example.com';
        $companyCreditData = new DataObject();
        $history = $this->createMock(
            HistoryInterface::class
        );
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $transport = $this->createMock(
            TransportInterface::class
        );
        $this->notificationRecipient->expects($this->once())
            ->method('getByRecord')
            ->with($history)
            ->willReturn($customer);
        $customer->expects($this->once())->method('getStoreId')->willReturn(null);
        $this->emailTemplateConfig->expects($this->once())
            ->method('getDefaultStoreId')
            ->with($customer)
            ->willReturn($storeId);
        $history->expects($this->once())->method('getType')->willReturn(1);
        $this->emailTemplateConfig->expects($this->once())
            ->method('getTemplateId')
            ->with(1, $storeId)
            ->willReturn($templateId);
        $this->emailTemplateConfig->expects($this->once())
            ->method('canSendNotification')
            ->with($customer)
            ->willReturn(true);
        $this->emailTemplateConfig->expects($this->once())->method('getCreditChangeCopyTo')->willReturn($copyTo);
        $this->emailTemplateConfig->expects($this->once())->method('getCreditCreateCopyMethod')->willReturn('copy');
        $customer->expects($this->once())->method('getEmail')->willReturn('company_admin@example.com');
        $this->companyCreditDataFactory->expects($this->once())
            ->method('getCompanyCreditDataObject')
            ->with($history, $customer)
            ->willReturn($companyCreditData);
        $this->emailTemplateConfig->expects($this->exactly(2))
            ->method('getSenderByStoreId')
            ->with($storeId)
            ->willReturn('sales');
        $this->transportBuilder->expects($this->exactly(2))
            ->method('setTemplateIdentifier')
            ->with($templateId)
            ->willReturnSelf();
        $this->transportBuilder->expects($this->exactly(2))
            ->method('setTemplateOptions')
            ->with(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
            ->willReturnSelf();
        $this->transportBuilder->expects($this->exactly(2))
            ->method('setTemplateVars')
            ->with(['companyCredit' => $companyCreditData])
            ->willReturnSelf();
        $this->transportBuilder->expects($this->exactly(2))
            ->method('setFromByScope')
            ->with('sales', $storeId)
            ->willReturnSelf();
        $this->transportBuilder->expects($this->exactly(2))
            ->method('addTo')
            ->withConsecutive(['company_admin@example.com'], ['info@example.com'])
            ->willReturnSelf();
        $this->transportBuilder->expects($this->exactly(2))
            ->method('addBcc')
            ->with([])
            ->willReturnSelf();
        $this->transportBuilder->expects($this->exactly(2))
            ->method('getTransport')
            ->willReturn($transport);
        $transport->expects($this->exactly(2))->method('sendMessage')->willReturnSelf();

        $this->model->sendCompanyCreditChangedNotificationEmail($history);
    }

    /**
     * Test sendCompanyCreditChangedNotificationEmail method throws exception.
     *
     * @return void
     */
    public function testSendCompanyCreditChangedNotificationEmailWithException()
    {
        $phrase = new Phrase('Exception Message');
        $exception = new LocalizedException($phrase);
        $history = $this->createMock(
            HistoryInterface::class
        );
        $this->notificationRecipient->expects($this->once())
            ->method('getByRecord')
            ->with($history)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception)->willReturnSelf();

        $this->model->sendCompanyCreditChangedNotificationEmail($history);
    }
}
