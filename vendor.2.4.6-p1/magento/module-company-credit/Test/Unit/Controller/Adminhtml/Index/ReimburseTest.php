<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Controller\Adminhtml\Index;

use Magento\CompanyCredit\Action\ReimburseFacade;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Controller\Adminhtml\Index\Reimburse;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Reimburse controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReimburseTest extends TestCase
{
    /**
     * @var JsonFactory|MockObject
     */
    private $jsonFactory;

    /**
     * @var ReimburseFacade|MockObject
     */
    private $reimburseFacade;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceFormatter;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Reimburse
     */
    private $reimburse;

    /**
     * @var MockObject
     */
    private $request;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reimburseFacade = $this->getMockBuilder(ReimburseFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceFormatter = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->reimburse = $objectManager->getObject(
            Reimburse::class,
            [
                'jsonFactory' => $this->jsonFactory,
                'reimburseFacade' => $this->reimburseFacade,
                'priceFormatter' => $this->priceFormatter,
                'logger' => $this->logger,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $companyId = 1;
        $currencyCode = 'USD';
        $creditBalance = -10;
        $creditLimit = 50;
        $availableLimit = 40;
        $reimburseBalance = [
            'amount' => 100,
            'purchase_order' => 'O123',
            'credit_comment' => 'Some Comment',
        ];

        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['id'], ['reimburse_balance'])
            ->willReturnOnConsecutiveCalls($companyId, $reimburseBalance);
        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($result);

        $credit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->reimburseFacade->expects($this->once())
            ->method('execute')
            ->with(
                $companyId,
                $reimburseBalance['amount'],
                $reimburseBalance['credit_comment'],
                $reimburseBalance['purchase_order']
            )->willReturn($credit);

        $credit->expects($this->exactly(1))
            ->method('getCurrencyCode')
            ->willReturn($currencyCode);
        $credit->expects($this->exactly(2))
            ->method('getBalance')
            ->willReturn($creditBalance);
        $credit->expects($this->once())
            ->method('getCreditLimit')
            ->willReturn($creditLimit);
        $credit->expects($this->once())
            ->method('getAvailableLimit')
            ->willReturn($availableLimit);

        $this->priceFormatter->expects($this->any())
            ->method('format')
            ->withConsecutive(
                [$creditBalance, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currencyCode],
                [$creditLimit, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currencyCode],
                [$availableLimit, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currencyCode]
            )
            ->willReturnOnConsecutiveCalls(
                '$' . $creditBalance,
                '$' . $creditLimit,
                '$' . $availableLimit
            );

        $result->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'success',
                    'balance' => [
                        'outstanding_balance' => '$' . $creditBalance,
                        'is_negative' => true,
                        'credit_limit' => '$' . $creditLimit,
                        'available_credit' => '$' . $availableLimit
                    ]
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($result, $this->reimburse->execute());
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $message = __('No such entity');
        $exception = new NoSuchEntityException($message);

        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['id'], ['reimburse_balance'])
            ->willReturnOnConsecutiveCalls(1, ['amount' => null, 'purchase_order' => null, 'credit_comment' => null]);

        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($result);
        $this->reimburseFacade->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);
        $result->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'error' => $message
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($result, $this->reimburse->execute());
    }
}
