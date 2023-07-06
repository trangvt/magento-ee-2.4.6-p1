<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Block\Adminhtml\Order;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Block\Adminhtml\Order\CancelButton;
use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Model\CompanyOrder;
use Magento\CompanyCredit\Model\CompanyStatus;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Cancel button.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CancelButtonTest extends TestCase
{
    /**
     * @var CompanyStatus|MockObject
     */
    private $companyStatus;

    /**
     * @var CompanyOrder|MockObject
     */
    private $companyOrder;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var ButtonList|MockObject
     */
    private $buttonList;

    /**
     * @var Registry|MockObject
     */
    private $coreRegistry;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var CancelButton
     */
    private $cancelButton;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyStatus = $this->createMock(
            CompanyStatus::class
        );
        $this->companyOrder = $this->createMock(
            CompanyOrder::class
        );
        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );
        $this->buttonList = $this->createMock(
            ButtonList::class
        );
        $this->coreRegistry = $this->createMock(
            Registry::class
        );
        $this->urlBuilder = $this->createMock(
            UrlInterface::class
        );

        $objectManager = new ObjectManager($this);
        $this->cancelButton = $objectManager->getObject(
            CancelButton::class,
            [
                'companyStatus' => $this->companyStatus,
                'companyOrder' => $this->companyOrder,
                'companyRepository' => $this->companyRepository,
                'buttonList' => $this->buttonList,
                '_coreRegistry' => $this->coreRegistry,
                '_urlBuilder' => $this->urlBuilder,
            ]
        );
    }

    /**
     * Test for method checkCompanyStatus.
     *
     * @return void
     */
    public function testCheckCompanyStatus()
    {
        $companyId = 1;
        $orderId = 2;
        $companyName = 'Company Name';
        $confirmationMessage = __(
            'Are you sure you want to cancel this order? '
            . 'The order amount will not be reverted to %1 because the company is not active.',
            'Company Name'
        );
        $url = '/sales/order/cancel/order_id/' . $orderId;
        $order = $this->getMockForAbstractClass(
            OrderInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getId']
        );
        $this->coreRegistry->expects($this->atLeastOnce())->method('registry')->with('sales_order')->willReturn($order);
        $order->expects($this->atLeastOnce())->method('getId')->willReturn($orderId);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $order->expects($this->once())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $this->companyOrder->expects($this->once())
            ->method('getCompanyIdByOrder')->with($order)->willReturn($companyId);
        $this->companyStatus->expects($this->once())->method('isRevertAvailable')->with($companyId)->willReturn(false);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())->method('getCompanyName')->willReturn($companyName);
        $this->buttonList->expects($this->once())->method('update')->with(
            'order_cancel',
            'data_attribute',
            [
                'mage-init' => '{"Magento_CompanyCredit/js/cancel-order-button": '
                    . '{"message": "' . $confirmationMessage . '", "url": "' . $url . '"}}',
            ]
        );
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')->with('sales/*/cancel', ['order_id' => $orderId])->willReturn($url);
        $this->cancelButton->checkCompanyStatus();
    }

    /**
     * Test for method checkCompanyStatus with deleted company.
     *
     * @return void
     */
    public function testCheckCompanyStatusWithDeletedCompany()
    {
        $companyId = 1;
        $orderId = 2;
        $companyName = 'Company Name';
        $confirmationMessage = __(
            'Are you sure you want to cancel this order? The order amount will not be reverted '
            . 'to %1 because the company associated with this customer does not exist.',
            'Company Name'
        );
        $url = '/sales/order/cancel/order_id/' . $orderId;
        $order = $this->getMockForAbstractClass(
            OrderInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getId']
        );
        $this->coreRegistry->expects($this->atLeastOnce())->method('registry')->with('sales_order')->willReturn($order);
        $order->expects($this->atLeastOnce())->method('getId')->willReturn($orderId);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $order->expects($this->atLeastOnce())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $orderPayment->expects($this->once())
            ->method('getAdditionalInformation')->with('company_name')->willReturn($companyName);
        $this->companyOrder->expects($this->once())
            ->method('getCompanyIdByOrder')->with($order)->willReturn($companyId);
        $this->companyStatus->expects($this->once())->method('isRevertAvailable')->with($companyId)->willReturn(false);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)
            ->willThrowException(new NoSuchEntityException(__('Exception Message')));
        $this->buttonList->expects($this->once())->method('update')->with(
            'order_cancel',
            'data_attribute',
            [
                'mage-init' => '{"Magento_CompanyCredit/js/cancel-order-button": '
                    . '{"message": "' . $confirmationMessage . '", "url": "' . $url . '"}}',
            ]
        );
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')->with('sales/*/cancel', ['order_id' => $orderId])->willReturn($url);
        $this->cancelButton->checkCompanyStatus();
    }
}
