<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Link;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Block\Link\OrdersLink;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OrdersLink block.
 */
class OrdersLinkTest extends TestCase
{
    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var string
     */
    private $resource = 'view_link_resource';

    /**
     * @var OrdersLink
     */
    private $ordersLink;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getControllerName', 'getPathInfo'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request->method('getPathInfo')->willReturn('/info');
        $objectManagerHelper = new ObjectManager($this);
        $context = $this->getMockBuilder(Context::class)
            ->onlyMethods(['getUrlBuilder', 'getEventManager', 'getScopeConfig', 'getEscaper'])
            ->disableOriginalConstructor()
            ->getMock();
        $managerInterface = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeConfigInterfaceMagento =  $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->onlyMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $context->expects($this->once())->method('getEventManager')->willReturn($managerInterface);
        $context->expects($this->once())->method('getScopeConfig')->willReturn($scopeConfigInterfaceMagento);
        $context->expects($this->once())->method('getEscaper')->willReturn($escaper);
        $urlInterface->method('getUrl')->willReturn('');
        $context->expects($this->once())->method('getUrlBuilder')->willReturn($urlInterface);

        $this->ordersLink = $objectManagerHelper->getObject(
            OrdersLink::class,
            [
                'context' => $context,
                'companyContext' => $this->companyContext,
                'companyManagement' => $this->companyManagement,
                '_request' => $request,
                'data' => ['resource' => $this->resource],
            ]
        );
    }

    /**
     * Test for toHtml method.
     *
     * @param bool $isAllowed
     * @param string $expectedResult
     * @return void
     * @dataProvider toHtmlDataProvider
     */
    public function testToHtml($isAllowed, $expectedResult)
    {
        $customerId = 1;
        $this->companyContext->expects($this->atLeastOnce())->method('getCustomerId')->willReturn($customerId);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')->with($customerId)->willReturn($company);
        $this->companyContext->expects($this->once())
            ->method('isResourceAllowed')->with($this->resource)->willReturn($isAllowed);
        $this->assertEquals($expectedResult, $this->ordersLink->toHtml());
    }

    /**
     * Data provider for testToHtml.
     *
     * @return array
     */
    public function toHtmlDataProvider()
    {
        return [
            [true, '<li class="nav item current"><strong></strong></li>'],
            [false, ''],
        ];
    }
}
