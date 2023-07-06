<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Html\Date;
use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\View\LayoutInterface;
use Magento\OrderHistorySearch\Block\Filters;
use Magento\OrderHistorySearch\Model\Config;
use Magento\OrderHistorySearch\Model\Order\Address\Service;
use Magento\OrderHistorySearch\Model\Order\Status\DataProvider;
use Magento\OrderHistorySearch\Model\OrderAddressProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class FiltersTest.
 *
 * Unit test for filters block.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FiltersTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var OrderAddressProvider|MockObject
     */
    private $orderAddressProviderMock;

    /**
     * @var Service|MockObject
     */
    private $orderAddressServiceMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDateMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var DataProvider|MockObject
     */
    private $statusDataProviderMock;

    /**
     * @var SessionFactory|MockObject
     */
    private $customerSessionFactoryMock;

    /**
     * @var LayoutInterface|MockObject
     */
    private $layoutMock;

    /**
     * @var Repository|MockObject
     */
    private $assetRepoMock;

    /**
     * @var Filters
     */
    private $filtersModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(TemplateContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'getAssetRepository'])
            ->getMock();

        $this->requestMock = $this
            ->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMock();

        $this->contextMock->method('getRequest')->willReturn($this->requestMock);

        $this->customerSessionFactoryMock = $this
            ->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this
            ->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressProviderMock = $this
            ->getMockBuilder(OrderAddressProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressServiceMock = $this
            ->getMockBuilder(Service::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDateMock = $this
            ->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDateFormat', 'getDateFormatWithLongYear'])
            ->getMockForAbstractClass();

        // this method's npn-nullable return value has to be set for PHP 8 compatibility
        $this->localeDateMock
            ->expects($this->any())
            ->method('getDateFormatWithLongYear')
            ->withAnyParameters()
            ->willReturn('mm/dd/yyyy');

        $this->statusDataProviderMock = $this->getMockBuilder(DataProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrderStatusOptions'])
            ->getMock();

        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['createBlock'])
            ->getMockForAbstractClass();

        $this->assetRepoMock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrlWithParams'])
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getAssetRepository')
            ->willReturn($this->assetRepoMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->filtersModel = $this->objectManagerHelper->getObject(
            Filters::class,
            [
                'context' => $this->contextMock,
                'config' => $this->configMock,
                'customerSessionFactory' => $this->customerSessionFactoryMock,
                'orderAddressProvider' => $this->orderAddressProviderMock,
                'orderAddressService' => $this->orderAddressServiceMock,
                'statusDataProvider' => $this->statusDataProviderMock,
                '_localeDate' => $this->localeDateMock,
                'timezone' => $this->localeDateMock,
            ]
        );
    }

    /**
     * Test prepareInputValue() method with DEFINED param
     *
     * @return void
     */
    public function testPrepareInputValueWithDefinedParam()
    {
        $productNameSku = 'product-name-sku';
        $expectedValue = '1234567890';

        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with($productNameSku, '')
            ->willReturn($expectedValue);

        /** Test name which occurs in request params */
        $this->assertEquals($expectedValue, $this->filtersModel->prepareInputValue($productNameSku));
    }

    /**
     * Test prepareInputValue() method with UNDEFINED param
     *
     * @return void
     */
    public function testPrepareInputValueWithUndefinedParam()
    {
        $undefinedName = 'undefined';

        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with($undefinedName, '')
            ->willReturn('');

        /** Test name which occurs in request params */
        $this->assertEquals('', $this->filtersModel->prepareInputValue($undefinedName));
    }

    /**
     * Test getOrderStatusSelectElement() method.
     *
     * @return void
     */
    public function testGetOrderStatusSelectElement()
    {
        $selectBlockMock = $this
            ->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setName',
                    'setId',
                    'setTitle',
                    'setValue',
                    'setOptions',
                    'setClass',
                    'getHtml',
                ]
            )
            ->getMock();

        $this->statusDataProviderMock->expects($this->once())->method('getOrderStatusOptions')->willReturn([]);

        $this->requestMock->expects($this->once())->method('getParam')->with('order-status', '')->willReturn(1);

        $this->filtersModel->setData('_select_block', $selectBlockMock);

        $this->prepareSelectBlockFunctions($selectBlockMock);

        $this->filtersModel->getOrderStatusSelectElementHtml();
    }

    /**
     * Test getDateElementToHtml() method.
     *
     * @return void
     */
    public function testGetDateElementToHtml()
    {
        $dateBlockMock = $this
            ->getMockBuilder(Date::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setName',
                    'setId',
                    'setTitle',
                    'setValue',
                    'setDateFormat',
                    'setExtraParams',
                    'setMaxDate',
                    'setClass',
                    'getHtml',
                ]
            )
            ->getMock();

        $this->filtersModel->setLayout($this->layoutMock);
        $this->layoutMock->expects($this->any())
            ->method('createBlock')
            ->with(Date::class)
            ->willReturn($dateBlockMock);

        $this->prepareDateBlockFunctions($dateBlockMock);

        $this->filtersModel->getDateElementToHtml(
            'order-date-to',
            'order-date-to',
            'Order date to',
            'placeholder="To"'
        );
    }

    /**
     * Perpare select block functions method.
     *
     * @param MockObject $selectBlock
     *
     * @return void
     */
    private function prepareSelectBlockFunctions($selectBlock)
    {
        $selectBlock->expects($this->once())->method('setName')->willReturnSelf();
        $selectBlock->expects($this->once())->method('setId')->willReturnSelf();
        $selectBlock->expects($this->once())->method('setTitle')->willReturnSelf();
        $selectBlock->expects($this->once())->method('setValue')->willReturnSelf();
        $selectBlock->expects($this->once())->method('setOptions')->willReturnSelf();
        $selectBlock->expects($this->once())->method('setClass')->willReturnSelf();
        $selectBlock->expects($this->once())->method('getHtml')->willReturn('');
    }

    /**
     * Perpare select block functions method.
     *
     * @param MockObject $dateBlock
     *
     * @return void
     */
    private function prepareDateBlockFunctions($dateBlock)
    {
        $dateBlock->expects($this->once())->method('setName')->willReturnSelf();
        $dateBlock->expects($this->once())->method('setId')->willReturnSelf();
        $dateBlock->expects($this->once())->method('setTitle')->willReturnSelf();
        $dateBlock->expects($this->once())->method('setValue')->willReturnSelf();
        $dateBlock->expects($this->once())->method('setDateFormat')->willReturnSelf();
        $dateBlock->expects($this->once())->method('setExtraParams')->willReturnSelf();
        $dateBlock->expects($this->once())->method('setMaxDate')->willReturnSelf();
        $dateBlock->expects($this->once())->method('setClass')->willReturnSelf();
        $dateBlock->expects($this->once())->method('getHtml')->willReturn('');
    }
}
