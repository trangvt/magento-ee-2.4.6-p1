<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Block\Requisition\View;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Block\Requisition\View\Details;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DetailsTest extends TestCase
{
    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var Details
     */
    private $details;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->requisitionListRepository =
            $this->getMockForAbstractClass(RequisitionListRepositoryInterface::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $objectManager = new ObjectManager($this);
        $this->details = $objectManager->getObject(
            Details::class,
            [
                '_request' => $this->request,
                '_urlBuilder' => $this->urlBuilder,
                'requisitionListRepository' => $this->requisitionListRepository,
                'data' => []
            ]
        );
    }

    /**
     * Test getRequisitionList
     *
     * @param int $requisitionId
     * @param \Magento\RequisitionList\Api\Data\RequisitionListInterface|null
     * @dataProvider dataProviderGetRequisitionList
     */
    public function testGetRequisitionList($requisitionId, $requisitionList)
    {
        $this->request->expects($this->any())->method('getParam')->willReturn($requisitionId);
        $this->requisitionListRepository->expects($this->any())->method('get')->willReturn($requisitionList);

        $this->assertEquals($requisitionList, $this->details->getRequisitionList());
    }

    /**
     * Test getRequisitionList
     *
     * @param int $requisitionId
     * @param \Magento\RequisitionList\Api\Data\RequisitionListInterface|null
     * @param int $itemCount
     * @dataProvider dataProviderGetItemCount
     */
    public function testGetItemCount($requisitionId, $requisitionList, $itemCount)
    {
        $this->request->expects($this->any())->method('getParam')->willReturn($requisitionId);
        $this->requisitionListRepository->expects($this->any())->method('get')->willReturn($requisitionList);

        $this->assertEquals($itemCount, $this->details->getItemCount());
    }

    /**
     * Test getPrintUrl
     */
    public function testGetPrintUrl()
    {
        $this->urlBuilder->expects($this->any())->method('getUrl')->willReturn('url');

        $this->assertEquals('url', $this->details->getPrintUrl());
    }

    /**
     * DataProvider getRequisitionList
     *
     * @return array
     */
    public function dataProviderGetRequisitionList()
    {
        $requisitionList = $this->getMockForAbstractClass(RequisitionListInterface::class);

        return [
            [null, null],
            [1, $requisitionList]
        ];
    }

    /**
     * DataProvider getItemCount
     *
     * @return array
     */
    public function dataProviderGetItemCount()
    {
        $requisitionList = $this->getMockForAbstractClass(RequisitionListInterface::class);
        $requisitionList->expects($this->any())->method('getItems')->willReturn([1, 2, 3, 4, 5]);

        return [
            [null, null, 0],
            [1, $requisitionList, 5]
        ];
    }
}
