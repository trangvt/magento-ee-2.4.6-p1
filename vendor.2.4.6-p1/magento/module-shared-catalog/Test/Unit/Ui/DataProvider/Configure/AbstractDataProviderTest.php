<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Configure;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ResourceModel\CategoryTree;
use Magento\SharedCatalog\Ui\DataProvider\Configure\AbstractDataProvider;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for configure abstract data provider.
 */
class AbstractDataProviderTest extends TestCase
{
    /**
     * @var CategoryTree|MockObject
     */
    private $categoryTree;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var AbstractDataProvider
     */
    private $dataProvider;

    /**
     * Set up for test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->categoryTree = $this->getMockBuilder(CategoryTree::class)
            ->disableOriginalConstructor()
            ->getMock();
        $wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->dataProvider = $this->getMockForAbstractClass(
            AbstractDataProvider::class,
            [
                'name' => 'test_name',
                'primaryFieldName' => 'primary_field_name',
                'requestFieldName' => 'request_field_name',
                'request' => $this->request,
                'wizardStorageFactory' => $wizardStorageFactory,
                'categoryTree' => $this->categoryTree,
                'storeManager' => $this->storeManager,
                'meta' => [],
                'data' => [],
            ],
            '',
            true,
            false,
            true,
            []
        );
    }

    /**
     * Test addFilter method with "name" field.
     *
     * @return void
     */
    public function testAddFilterNotFulltext()
    {
        $filter = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(
                ['filters'],
                [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY]
            )
            ->willReturnOnConsecutiveCalls(
                ['category_id' => 12],
                'configure_key'
            );
        $filter->expects($this->exactly(2))->method('getField')->willReturn('name');
        $filter->expects($this->once())->method('getConditionType')->willReturn('eq');
        $filter->expects($this->once())->method('getValue')->willReturn('test_name');
        $this->categoryTree->expects($this->once())
            ->method('getCategoryProductsCollectionById')
            ->with(12)
            ->willReturn($productCollection);
        $productCollection->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('name', ['eq' => 'test_name'])
            ->willReturnSelf();
        $productCollection->expects($this->once())->method('addWebsiteNamesToResult')->willReturnSelf();

        $this->dataProvider->addFilter($filter);
    }

    /**
     * Test addFilter method fulltext.
     *
     * @return void
     */
    public function testAddFilterFulltext()
    {
        $filter = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(
                ['filters'],
                [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY]
            )
            ->willReturnOnConsecutiveCalls(
                ['category_id' => 12],
                'configure_key'
            );
        $filter->expects($this->once())->method('getField')->willReturn('fulltext');
        $filter->expects($this->exactly(2))->method('getValue')->willReturnOnConsecutiveCalls('test_name', 'test_sku');
        $this->categoryTree->expects($this->once())
            ->method('getCategoryProductsCollectionById')
            ->with(12)
            ->willReturn($productCollection);
        $productCollection->expects($this->once())
            ->method('addAttributeToFilter')
            ->with(
                [
                    ['attribute' => 'name', 'like' => "%test_name%"],
                    ['attribute' => 'sku', 'like' => "%test_sku%"]
                ]
            )
            ->willReturnSelf();
        $productCollection->expects($this->once())->method('addWebsiteNamesToResult')->willReturnSelf();

        $this->dataProvider->addFilter($filter);
    }

    /**
     * Test addFilter method with "store_id" field.
     *
     * @return void
     * @dataProvider getDataAddFilterStoreId
     */
    public function testAddFilterStoreId(int $storeGroupId, int $storeId)
    {

        $filter = $this->createMock(Filter::class);
        $filter->expects($this->once())
            ->method('getField')
            ->willReturn('store_id');
        $filter->expects($this->once())
            ->method('getValue')
            ->willReturn($storeGroupId);

        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->withConsecutive(
                ['filters'],
                [UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY]
            )
            ->willReturnOnConsecutiveCalls(
                ['category_id' => 12, 'store_id' => $storeGroupId],
                'configure_key'
            );

        $storeGroup = $this->getMockBuilder(\Magento\Store\Api\Data\GroupInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefaultStoreId'])
            ->getMockForAbstractClass();

        $storeGroup->expects($this->atLeastOnce())
            ->method('getDefaultStoreId')
            ->willReturn($storeId);

        $this->storeManager->expects($this->atLeastOnce())
            ->method('getGroup')
            ->with($storeGroupId)
            ->willReturn($storeGroup);

        $productCollection = $this->createMock(Collection::class);
        $productCollection->expects($this->once())
            ->method('addWebsiteNamesToResult')
            ->willReturnSelf();
        $this->categoryTree->expects($this->once())
            ->method('getCategoryProductsCollectionById')
            ->with(12)
            ->willReturn($productCollection);
        $productCollection->expects($this->once())
            ->method('addStoreFilter')
            ->with($storeId)
            ->willReturnSelf();

        $this->dataProvider->addFilter($filter);
    }

    /**
     * Data provider for addFilterStoreId method.
     *
     * @return array
     */
    public function getDataAddFilterStoreId()
    {
        return [
            [
                0, 0
            ],
            [
                2, 3
            ],
        ];
    }
}
