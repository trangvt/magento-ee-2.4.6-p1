<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\CompanyCredit\Model\PaymentMethodStatus;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Payment\Model\Checks\Composite;
use Magento\Payment\Model\Checks\SpecificationFactory;
use Magento\Payment\Model\Method\InstanceFactory;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentMethodStatusTest extends TestCase
{
    /**
     * @var PaymentMethodListInterface|MockObject
     */
    private $paymentMethodList;

    /**
     * @var StoreResolverInterface|MockObject
     */
    private $storeResolver;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var SpecificationFactory|MockObject
     */
    private $methodSpecificationFactory;

    /**
     * @var InstanceFactory|MockObject
     */
    private $paymentMethodInstanceFactory;

    /**
     * @var QuoteFactory|MockObject
     */
    private $quoteFactory;

    /**
     * @var PaymentMethodStatus
     */
    private $paymentMethodStatus;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->paymentMethodList = $this->createMock(
            PaymentMethodListInterface::class
        );
        $this->storeResolver = $this->createMock(
            StoreResolverInterface::class
        );
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();

        $this->userContext = $this->createMock(
            UserContextInterface::class
        );
        $this->quoteRepository = $this->createMock(
            CartRepositoryInterface::class
        );
        $this->methodSpecificationFactory = $this->createPartialMock(
            SpecificationFactory::class,
            ['create']
        );
        $this->paymentMethodInstanceFactory = $this->createPartialMock(
            InstanceFactory::class,
            ['create']
        );
        $this->quoteFactory = $this->createPartialMock(
            QuoteFactory::class,
            ['create']
        );

        $objectManager = new ObjectManager($this);
        $this->paymentMethodStatus = $objectManager->getObject(
            PaymentMethodStatus::class,
            [
                'paymentMethodList' => $this->paymentMethodList,
                'storeResolver' => $this->storeResolver,
                'userContext' => $this->userContext,
                'quoteRepository' => $this->quoteRepository,
                'methodSpecificationFactory' => $this->methodSpecificationFactory,
                'paymentMethodInstanceFactory' => $this->paymentMethodInstanceFactory,
                'quoteFactory' => $this->quoteFactory,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    /**
     * Test for method isEnabled.
     *
     * @return void
     */
    public function testIsEnabled()
    {
        $storeId = 1;
        $userId = 2;
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMock();
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);
        $paymentMethod = $this->getMockForAbstractClass(PaymentMethodInterface::class);
        $this->paymentMethodList->expects($this->once())
            ->method('getActiveList')->with($storeId)->willReturn([$paymentMethod]);
        $paymentMethod->expects($this->once())->method('getCode')->willReturn('companycredit');
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $quote = $this->createMock(Quote::class);
        $this->quoteRepository->expects($this->once())
            ->method('getActiveForCustomer')->with($userId)->willReturn($quote);
        $methodInstance = $this->getMockForAbstractClass(MethodInterface::class);
        $this->paymentMethodInstanceFactory->expects($this->once())
            ->method('create')->with($paymentMethod)->willReturn($methodInstance);
        $check = $this->createMock(Composite::class);
        $this->methodSpecificationFactory->expects($this->once())
            ->method('create')->with(['company'])->willReturn($check);
        $check->expects($this->once())->method('isApplicable')->with($methodInstance, $quote)->willReturn(true);
        $this->assertTrue($this->paymentMethodStatus->isEnabled());
    }

    /**
     * Test for method isEnabled with exception.
     *
     * @return void
     */
    public function testIsEnabledWithException()
    {
        $storeId = 1;
        $userId = 2;
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMock();
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);
        $paymentMethod = $this->getMockForAbstractClass(PaymentMethodInterface::class);
        $this->paymentMethodList->expects($this->once())
            ->method('getActiveList')->with($storeId)->willReturn([$paymentMethod]);
        $paymentMethod->expects($this->once())->method('getCode')->willReturn('companycredit');
        $this->userContext->expects($this->exactly(2))->method('getUserId')->willReturn($userId);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['setCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository->expects($this->once())->method('getActiveForCustomer')->with($userId)
            ->willThrowException(new NoSuchEntityException());
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($quote);
        $quote->expects($this->once())->method('setCustomerId')->with($userId)->willReturnSelf();
        $methodInstance = $this->getMockForAbstractClass(MethodInterface::class);
        $this->paymentMethodInstanceFactory->expects($this->once())
            ->method('create')->with($paymentMethod)->willReturn($methodInstance);
        $check = $this->createMock(Composite::class);
        $this->methodSpecificationFactory->expects($this->once())
            ->method('create')->with(['company'])->willReturn($check);
        $check->expects($this->once())->method('isApplicable')->with($methodInstance, $quote)->willReturn(true);
        $this->assertTrue($this->paymentMethodStatus->isEnabled());
    }
}
