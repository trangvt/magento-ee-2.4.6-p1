<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Plugin\Sales\Block\Order;

use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\OrderFilterBuilderService;
use Magento\OrderHistorySearch\Plugin\Sales\Block\Order\HistoryPlugin;
use Magento\Sales\Block\Order\History;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Theme\Block\Html\Pager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class HistoryPluginTest.
 *
 * Unit test for History plugin.
 */
class HistoryPluginTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OrderFilterBuilderService|MockObject
     */
    private $filterServiceMock;

    /**
     * @var Pager|MockObject
     */
    private $pagerMock;

    /**
     * @var HistoryPlugin
     */
    private $historyPluginModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->filterServiceMock = $this
            ->getMockBuilder(OrderFilterBuilderService::class)
            ->disableOriginalConstructor()
            ->setMethods(['applyOrderFilters'])
            ->getMock();

        $this->pagerMock = $this
            ->getMockBuilder(Pager::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getPageVarName',
                    'getCurrentPage',
                    'getLimitVarName',
                    'getLimit',
                ]
            )
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->historyPluginModel = $this->objectManagerHelper->getObject(
            HistoryPlugin::class,
            [
                'filterService' => $this->filterServiceMock,
                'pager' => $this->pagerMock,
            ]
        );
    }

    /**
     * Test afterGetOrders() method without advanced filtering.
     *
     * @return void
     */
    public function testAfterGetOrdersWithoutAdvancedFiltering()
    {
        $subjectMock = $this
            ->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getParam',
                    'getParams',
                ]
            )
            ->getMock();

        $collectionMock = $this
            ->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subjectMock->expects($this->once())->method('getRequest')->willReturn($requestMock);
        $this->filterServiceMock->expects($this->never())->method('applyOrderFilters');

        $this->historyPluginModel->afterGetOrders($subjectMock, $collectionMock);
    }

    /**
     * Test afterGetOrders() method with advanced filtering
     *
     * @return void
     */
    public function testAroundGetOrdersWithAdvancedFiltering()
    {
        $params = [
            'p' => '1',
            'limit' => 10,
            'advanced-filtering' => '',
            'order-number' => '00000001',
            'product-name-sku' => 'a',
            'order-status' => 'Canceled',
            'order-total-min' => '100',
            'order-total-max' => '333',
        ];

        $subjectMock = $this
            ->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();

        $requestMock = $this
            ->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getParams',
                    'getParam',
                ]
            )
            ->getMock();

        $collectionMock = $this
            ->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addFieldToFilter',
                    'setCurPage',
                    'setPageSize',
                ]
            )
            ->getMock();

        $subjectMock->expects($this->once())->method('getRequest')->willReturn($requestMock);
        $requestMock->expects($this->once())->method('getParam')->with('advanced-filtering')->willReturn('');
        $requestMock->expects($this->once())->method('getParams')->willReturn($params);
        $this->pagerMock->expects($this->any())->method('getPageVarName')->willReturn('p');
        $this->pagerMock->expects($this->any())->method('getLimitVarName')->willReturn('limit');
        $this->filterServiceMock->expects($this->once())->method('applyOrderFilters');

        $this->historyPluginModel->afterGetOrders($subjectMock, $collectionMock);
    }
}
