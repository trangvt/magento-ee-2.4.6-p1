<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Adminhtml\Sales\Order\View\Info;

use Magento\Company\Api\Data\CompanyOrderInterface;
use Magento\Company\Block\Adminhtml\Sales\Order\View\Info\OrderCompanyInfo;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OrderCompanyInfo object which is responsible for displaying company info on order view page.
 */
class OrderCompanyInfoTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OrderCompanyInfo
     */
    private $orderCompanyInfo;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var OrderInterface|MockObject
     */
    private $orderMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * @var int
     */
    private $orderId = 1;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock->expects($this->once())->method('getParam')->with('order_id')->willReturn($this->orderId);
        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderRepositoryMock->expects($this->once())->method('get')->with($this->orderId)
            ->willReturn($this->orderMock);
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->orderCompanyInfo = $this->objectManagerHelper->getObject(
            OrderCompanyInfo::class,
            [
                'orderRepository' => $this->orderRepositoryMock,
                '_request' => $this->requestMock,
                '_urlBuilder' => $this->urlBuilderMock
            ]
        );
    }

    /**
     * Test for canShow() method.
     *
     * @dataProvider canShowDataProvider
     * @param int|null $companyId
     * @param boolean $result
     * @return void
     */
    public function testCanShow($companyId, $result)
    {
        $companyOrderAttributesMock = $this->createCompanyOrderAttributesMock();
        $companyOrderAttributesMock->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);

        $this->assertEquals($result, $this->orderCompanyInfo->canShow());
    }

    /**
     * Data provider for canShow() method.
     *
     * @return array
     */
    public function canShowDataProvider()
    {
        return [
            [1, true],
            [null, false]
        ];
    }

    /**
     * Test for getCompanyName().
     *
     * @return void
     */
    public function testGetCompanyName()
    {
        $companyName = 'test';
        $companyOrderAttributesMock = $this->createCompanyOrderAttributesMock();
        $companyOrderAttributesMock->expects($this->once())->method('getCompanyName')->willReturn($companyName);

        $this->assertEquals($companyName, $this->orderCompanyInfo->getCompanyName());
    }

    /**
     * Test for getCompanyUrl().
     *
     * @return void
     */
    public function testGetCompanyUrl()
    {
        $companyId = 1;
        $companyUrl = 'test.com/' . $companyId;
        $companyOrderAttributesMock = $this->createCompanyOrderAttributesMock();
        $companyOrderAttributesMock->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->urlBuilderMock->expects($this->once())->method('getUrl')
            ->with(
                'company/index/edit',
                ['_secure' => true, 'id' => $companyId]
            )
            ->willReturn($companyUrl);

        $this->assertEquals($companyUrl, $this->orderCompanyInfo->getCompanyUrl());
    }

    /**
     * Create company order attributes mock.
     *
     * @return MockObject
     */
    private function createCompanyOrderAttributesMock()
    {
        $orderExtensionAttributesMock = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyOrderAttributes'])
            ->getMockForAbstractClass();
        $this->orderMock->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($orderExtensionAttributesMock);
        $companyOrderAttributesMock = $this->getMockBuilder(CompanyOrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderExtensionAttributesMock->expects($this->atLeastOnce())->method('getCompanyOrderAttributes')
            ->willReturn($companyOrderAttributesMock);

        return $companyOrderAttributesMock;
    }
}
