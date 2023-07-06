<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuoteSharedCatalog\Model\QuoteManagement;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for QuoteManagement model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteManagementTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepository;

    /**
     * @var CartItemRepositoryInterface|MockObject
     */
    private $cartItemRepository;

    /**
     * @var CartInterfaceFactory|MockObject
     */
    private $cartFactory;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var CollectionFactory|MockObject
     */
    private $itemCollectionFactory;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cartFactory = $this->getMockBuilder(CartInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteRepository = $this
            ->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemCollectionFactory = $this
            ->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartItemRepository = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->quoteManagement = $objectManager->getObject(
            QuoteManagement::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'cartRepository' => $this->cartRepository,
                'cartItemRepository' => $this->cartItemRepository,
                'cartFactory' => $this->cartFactory,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'itemCollectionFactory' => $this->itemCollectionFactory,
            ]
        );
    }

    /**
     * Test for deleteItems method.
     *
     * @return void
     */
    public function testDeleteItems()
    {
        $customerGroupId = 1;
        $cartId = 2;
        $cartItemId = 3;
        $productIds = ['1', '2'];
        $storeIds = [1, 2];
        $cartItem = $this->prepareQuoteItem($customerGroupId, $storeIds, $cartId, $productIds);
        $cartItem->expects($this->atLeastOnce())->method('getOrigData')->willReturn($cartId);
        $cartItem->expects($this->atLeastOnce())->method('getItemId')->willReturn($cartItemId);
        $this->cartItemRepository->expects($this->atLeastOnce())
            ->method('deleteById')->with($cartId, $cartItemId)->willReturn(true);
        $this->quoteManagement->deleteItems($productIds, $customerGroupId, $storeIds);
    }

    /**
     * Test for retrieveQuoteItems method.
     *
     * @return void
     */
    public function testRetrieveQuoteItems()
    {
        $customerGroupId = 1;
        $quoteId = 2;
        $productIds = [3];
        $storeIds = [1, 2];
        $quoteItem = $this->prepareQuoteItem($customerGroupId, $storeIds, $quoteId, $productIds);
        $this->assertEquals(
            [$quoteItem],
            $this->quoteManagement->retrieveQuoteItems($customerGroupId, $productIds, $storeIds)
        );
    }

    /**
     * @param int $customerGroupId
     * @param array $storeIds
     * @param int|array $quoteId
     * @param array $productIds
     * @return MockObject
     */
    private function prepareQuoteItem($customerGroupId, $storeIds, $quoteId, $productIds)
    {
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('addFilter')
            ->withConsecutive(['customer_group_id', $customerGroupId], ['store_id', $storeIds, 'in'])
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->atLeastOnce())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $quoteItemCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItemCollection->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->withConsecutive(
                ['product_id', ['in' => $productIds]],
                ['quote_id', ['in' => [$quoteId]]],
                ['parent_item_id', ['null' => true]]
            )
            ->willReturnSelf();
        $this->cartFactory->expects($this->atLeastOnce())->method('create')->willReturn($quote);
        $quoteItemCollection->expects($this->atLeastOnce())->method('setQuote')->with($quote)->willReturnSelf();
        $quoteItemCollection->expects($this->atLeastOnce())->method('clear')->willReturnSelf();
        $searchResults->expects($this->atLeastOnce())->method('getItems')->willReturn([$quote]);
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItemCollection->expects($this->atLeastOnce())->method('getItems')->willReturn([$quoteItem]);
        $this->itemCollectionFactory->expects($this->atLeastOnce())->method('create')->willReturn($quoteItemCollection);

        return $quoteItem;
    }

    /**
     * Test for retrieveQuoteItemsForCustomers method.
     *
     * @return void
     */
    public function testRetrieveQuoteItemsForCustomers()
    {
        $customerIds = [1];
        $quoteId = 2;
        $productId = 3;
        $storeIds = [1, 2];
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('addFilter')
            ->withConsecutive(['customer_id', $customerIds, 'in'], ['store_id', $storeIds, 'in'])
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->atLeastOnce())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $quoteItemCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItemCollection->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->withConsecutive(
                ['product_id', ['in' => [$productId]]],
                ['quote_id', ['in' => [$quoteId]]]
            )
            ->willReturnSelf();
        $this->cartFactory->expects($this->atLeastOnce())->method('create')->willReturn($quote);
        $quoteItemCollection->expects($this->atLeastOnce())->method('setQuote')->with($quote)->willReturnSelf();
        $quoteItemCollection->expects($this->atLeastOnce())->method('clear')->willReturnSelf();
        $searchResults->expects($this->atLeastOnce())->method('getItems')->willReturn([$quote]);
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItemCollection->expects($this->atLeastOnce())->method('getItems')->willReturn([$quoteItem]);
        $this->itemCollectionFactory->expects($this->atLeastOnce())->method('create')->willReturn($quoteItemCollection);
        $this->assertEquals(
            [$quoteItem],
            $this->quoteManagement->retrieveQuoteItemsForCustomers($customerIds, [$productId], $storeIds)
        );
    }
}
