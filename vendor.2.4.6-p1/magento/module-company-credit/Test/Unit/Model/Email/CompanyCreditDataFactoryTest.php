<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\Email;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\Email\CompanyCreditDataFactory;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\Sales\OrderLocator;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CompanyCreditDataFactory.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyCreditDataFactoryTest extends TestCase
{
    /**
     * @var DataObjectProcessor|MockObject
     */
    private $dataProcessor;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var CreditLimitRepositoryInterface|MockObject
     */
    private $creditLimitRepository;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceFormatter;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerViewHelper;

    /**
     * @var OrderLocator|MockObject
     */
    private $orderLocator;

    /**
     * @var CompanyCreditDataFactory
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->dataProcessor = $this->getMockBuilder(DataObjectProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->getMock();
        $this->creditLimitRepository = $this->getMockBuilder(CreditLimitRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->priceFormatter = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->getMockForAbstractClass();
        $this->customerViewHelper = $this->getMockBuilder(CustomerNameGenerationInterface::class)
            ->getMockForAbstractClass();
        $this->orderLocator = $this->getMockBuilder(OrderLocator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $serializer = $this->createMock(Json::class);
        $serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->model = $objectManager->getObject(
            CompanyCreditDataFactory::class,
            [
                'dataProcessor' => $this->dataProcessor,
                'companyRepository' => $this->companyRepository,
                'creditLimitRepository' => $this->creditLimitRepository,
                'priceFormatter' => $this->priceFormatter,
                'customerViewHelper' => $this->customerViewHelper,
                'orderLocator' => $this->orderLocator,
                'serializer' => $serializer
            ]
        );
    }

    /**
     * Test getCompanyCreditDataObject method.
     *
     * @param array $data
     * @return void
     * @dataProvider getCompanyCreditDataObjectDataProvider
     */
    public function testGetCompanyCreditDataObject(array $data)
    {
        $companyCreditId = 1;
        $companyId = 1;
        $history = $this->getMockForAbstractClass(
            HistoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            [
                'getCompanyCreditId',
                'getComment',
                'getCreditLimit',
                'getCurrencyCredit',
                'getBalance',
                'getAmount',
                'getCurrencyOperation'
            ]
        );
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $creditLimit = $this->createMock(
            CreditLimitInterface::class
        );
        $company = $this->createMock(
            CompanyInterface::class
        );
        $history->expects($this->once())->method('getCompanyCreditId')->willReturn($companyCreditId);
        $this->creditLimitRepository->expects($this->once())
            ->method('get')
            ->with($companyCreditId)
            ->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with($companyId)
            ->willReturn($company);
        $this->dataProcessor->expects($this->once())
            ->method('buildOutputDataArray')
            ->with($history, HistoryInterface::class)
            ->willReturn([]);
        $comment = json_encode(['system' => [
            'order' => 7,
            HistoryInterface::COMMENT_TYPE_UPDATE_EXCEED_LIMIT => ['value' => 1]
        ]]);
        $history->expects($this->any())->method('getComment')->willReturn($comment);
        $history->expects($this->once())->method('getCreditLimit')->willReturn(500);
        $history->expects($this->exactly(4))->method('getCurrencyCredit')->willReturn('USD');
        $history->expects($this->once())->method('getBalance')->willReturn(200);
        $history->expects($this->once())->method('getCurrencyOperation')->willReturn('EUR');
        $history->expects($this->once())->method('getAmount')->willReturn(100);
        $history->expects($this->once())->method('getRate')->willReturn(1);
        $order = $this->createMock(Order::class);
        $this->orderLocator->expects($this->once())->method('getOrderByIncrementId')->willReturn($order);
        $order->expects($this->once())->method('getStoreId')->willReturn(1);
        $this->priceFormatter->expects($this->exactly(3))
            ->method('format')
            ->withConsecutive(
                [500, false, PriceCurrencyInterface::DEFAULT_PRECISION, 1, 'USD'],
                [200, false, PriceCurrencyInterface::DEFAULT_PRECISION, 1, 'USD'],
                [100, false, PriceCurrencyInterface::DEFAULT_PRECISION, 1, 'USD']
            )
            ->willReturnOnConsecutiveCalls(
                ['$500'],
                ['$200'],
                ['$100']
            );
        $company->expects($this->once())->method('getCompanyName')->willReturn('Test Company');
        $this->customerViewHelper->expects($this->once())
            ->method('getCustomerName')->with($customer)->willReturn('Firstname Lastname');

        $companyCreditDataObject = new DataObject();
        $companyCreditDataObject->setData($data);
        $this->assertEquals(
            $companyCreditDataObject,
            $this->model->getCompanyCreditDataObject($history, $customer)
        );
    }

    /**
     * Data provider for getCompanyCreditDataObject method.
     *
     * @return array
     */
    public function getCompanyCreditDataObjectDataProvider()
    {
        $data = [
            'availableCredit' => ['$500'],
            'outStandingBalance' => ['$200'],
            'exceedLimit' => 'allowed',
            'operationAmount' => ['$100'],
            'orderId' => 7,
            'companyName' => 'Test Company',
            'customerName' => 'Firstname Lastname'
        ];

        return [[$data]];
    }
}
