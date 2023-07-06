<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Configure;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ResourceModel\CategoryTree;
use Magento\SharedCatalog\Ui\DataProvider\Configure\StepDataProcessor;
use Magento\SharedCatalog\Ui\DataProvider\Configure\Structure;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Structure data provider.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StructureTest extends TestCase
{
    /**
     * @var StepDataProcessor|MockObject
     */
    private $stepDataProcessor;

    /**
     * @var Wizard|MockObject
     */
    private $storage;

    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CategoryTree|MockObject
     */
    private $categoryTree;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Structure
     */
    private $structureDataProvider;

    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * @var string
     */
    private $configureKey = 'configure_key_value';

    /**
     * @var int
     */
    private $categoryId = 1;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->stepDataProcessor = $this->getMockBuilder(
            StepDataProcessor::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryTree = $this->getMockBuilder(CategoryTree::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')->with(['key' => $this->configureKey])->willReturn($this->storage);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $objectManager = new ObjectManager($this);
        $this->structureDataProvider = $objectManager->getObject(
            Structure::class,
            [
                'request' => $this->request,
                'stepDataProcessor' => $this->stepDataProcessor,
                'categoryTree' => $this->categoryTree,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    /**
     * Test for getData() method when request websites filter is empty.
     *
     * @return void
     */
    public function testGetDataEmptyWebsitesFilter()
    {
        $expectedResult = ['totalRecords' => 1, 'items' => ['product_data_modified']];
        $requestParams = [
            'filters' => ['category_id' => $this->categoryId],
            UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY => $this->configureKey,
            SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => 1
        ];
        $websiteId = 1;

        $this->prepareGetDataMocks($requestParams, $expectedResult);
        $this->stepDataProcessor->expects($this->once())->method('retrieveSharedCatalogWebsiteIds')
            ->willReturn([$websiteId]);
        $this->collection->expects($this->once())->method('addWebsiteFilter')
            ->with([$websiteId])->willReturnSelf();

        $this->assertEquals($expectedResult, $this->structureDataProvider->getData());
    }

    /**
     * Test for getData() method.
     *
     * @return void
     */
    public function testGetData()
    {
        $expectedResult = ['totalRecords' => 1, 'items' => ['product_data_modified']];
        $requestParams = [
            'filters' => [
                'websites' => 1,
                'category_id' => $this->categoryId
            ],
            UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY => $this->configureKey
        ];

        $this->prepareGetDataMocks($requestParams, $expectedResult);
        $this->collection->expects($this->once())->method('addWebsiteFilter')
            ->with($requestParams['filters']['websites'])->willReturnSelf();

        $this->assertEquals($expectedResult, $this->structureDataProvider->getData());
    }

    /**
     * Prepare mocks for testGetData() method.
     *
     * @param array $requestParams
     * @param array $expectedResult
     * @return void
     */
    private function prepareGetDataMocks(array $requestParams, array $expectedResult)
    {
        $productSku = 'sku_1';
        $productData = ['product_data'];
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryTree->expects($this->once())
            ->method('getCategoryProductsCollectionById')->with($this->categoryId)->willReturn($this->collection);
        $this->collection->expects($this->once())->method('getSize')->willReturn(1);
        $product = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getSku', 'setIsAssign', 'toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->expects($this->once())->method('getItems')->willReturn([$product]);
        $product->expects($this->once())->method('getSku')->willReturn($productSku);
        $this->storage->expects($this->once())->method('isProductAssigned')->with($productSku)->willReturn(true);
        $product->expects($this->once())->method('setIsAssign')->with(true)->willReturnSelf();
        $product->expects($this->once())->method('toArray')->willReturn($productData);
        $this->stepDataProcessor->expects($this->once())->method('modifyData')
            ->with(['totalRecords' => $expectedResult['totalRecords'], 'items' => [$productData]])
            ->willReturn($expectedResult);
        $this->request->expects($this->once())->method('getParams')
            ->willReturn($requestParams);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->withConsecutive(
                ['filters'],
                [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY]
            )
            ->willReturnOnConsecutiveCalls(
                $requestParams['filters'],
                $this->configureKey
            );
    }
}
