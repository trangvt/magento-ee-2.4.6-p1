<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing\Column\Configure;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Ui\Component\Listing\Column\Configure\Websites;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for websites grid column.
 */
class WebsitesTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var Websites
     */
    private $websites;

    /**
     * @var ContextInterface|MockObject
     */
    private $contextMock;

    /**
     * @var UiComponentFactory|MockObject
     */
    private $uiComponentFactoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepositoryMock;

    /**
     * @var \Magento\Catalog\Ui\Component\Listing\Columns\Websites|MockObject
     */
    private $catalogWebsitesColumnMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->uiComponentFactoryMock = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRepositoryMock = $this->getMockBuilder(
            SharedCatalogRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogWebsitesColumnMock = $this->getMockBuilder(
            \Magento\Catalog\Ui\Component\Listing\Columns\Websites::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->websites = $this->objectManagerHelper->getObject(
            Websites::class,
            [
                'context' => $this->contextMock,
                'uiComponentFactory' => $this->uiComponentFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'sharedCatalogRepository' => $this->sharedCatalogRepositoryMock,
                'catalogWebsitesColumn' => $this->catalogWebsitesColumnMock
            ]
        );
    }

    /**
     * Test for prepareDataSource() method.
     *
     * @return void
     */
    public function testPrepareDataSource()
    {
        $this->contextMock->expects($this->never())->method('getProcessor');
        $dataSource = [
            'items' => [
                'website1'
            ],
        ];
        $this->catalogWebsitesColumnMock->expects($this->once())->method('setData');
        $this->catalogWebsitesColumnMock->expects($this->once())->method('prepareDataSource')->willReturn($dataSource);

        $this->assertEquals($dataSource, $this->websites->prepareDataSource($dataSource));
    }

    /**
     * Test for prepare() method when there single store mode is false in store manager.
     *
     * @param array $result
     * @param int|null $sharedCatalogStoreId
     * @dataProvider prepareForMultipleStoresDataProvider
     * @return void
     */
    public function testPrepareForMultipleStores(array $result, $sharedCatalogStoreId)
    {
        $sharedCatalogRequestId = 1;
        $this->storeManagerMock->expects($this->once())->method('isSingleStoreMode')->willReturn(false);
        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())->method('getProcessor')->willReturn($processorMock);
        $this->contextMock->expects($this->any())->method('getRequestParam')
            ->withConsecutive(
                ['sorting'],
                [SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM]
            )
            ->willReturnOnConsecutiveCalls(null, $sharedCatalogRequestId);
        $sharedCatalogMock = $this->getMockBuilder(
            SharedCatalogInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepositoryMock->expects($this->any())->method('get')->with($sharedCatalogRequestId)
            ->willReturn($sharedCatalogMock);
        $sharedCatalogMock->expects($this->any())->method('getStoreId')->willReturn($sharedCatalogStoreId);

        $this->websites->prepare();

        $configData = $this->websites->getData('config');
        $this->assertEquals($result, $configData);
    }

    /**
     * Test for prepare() method when there single store mode is true in store manager.
     *
     * @return void
     */
    public function testPrepareForSingleStore()
    {
        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())->method('getProcessor')->willReturn($processorMock);
        $this->storeManagerMock->expects($this->once())->method('isSingleStoreMode')
            ->willReturn(true);
        $this->websites->prepare();
        $configData = $this->websites->getData('config');
        $this->assertTrue($configData['componentDisabled']);
    }

    /**
     * Data provider for testPrepareForMultipleStores() test.
     *
     * @return array
     */
    public function prepareForMultipleStoresDataProvider()
    {
        return [
            [[], null],
            [['componentDisabled' => true], 1]
        ];
    }
}
