<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Company;

use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterfaceFactory;
use Magento\Company\Model\Company\GetList;
use Magento\Company\Model\ResourceModel\Company\Collection;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\Company\GetList class.
 */
class GetListTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $companyCollectionFactory;

    /**
     * @var CompanySearchResultsInterfaceFactory|MockObject
     */
    private $searchResultsFactory;

    /**
     * @var JoinProcessorInterface|MockObject
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessor;

    /**
     * @var GetList
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->searchResultsFactory = $this->getMockBuilder(
            CompanySearchResultsInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->extensionAttributesJoinProcessor = $this->getMockBuilder(
            JoinProcessorInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionProcessor = $this->getMockBuilder(
            CollectionProcessorInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            GetList::class,
            [
                'companyCollectionFactory' => $this->companyCollectionFactory,
                'searchResultsFactory' => $this->searchResultsFactory,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessor,
                'collectionProcessor' => $this->collectionProcessor,
            ]
        );
    }

    /**
     * Test getList method.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchResults = $this->getMockBuilder(
            CompanySearchResultsInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchResultsFactory->expects($this->once())->method('create')->willReturn($searchResults);
        $searchResults->expects($this->once())->method('setSearchCriteria')->with($searchCriteria)->willReturnSelf();
        $this->companyCollectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $this->extensionAttributesJoinProcessor->expects($this->once())->method('process')->with($collection);
        $this->collectionProcessor->expects($this->once())->method('process')->with($searchCriteria, $collection);
        $collection->expects($this->once())->method('getSize')->willReturn(1);
        $collection->expects($this->once())->method('getItems')->willReturn([$item]);
        $searchResults->expects($this->once())->method('setTotalCount')->with(1)->willReturnSelf();
        $searchResults->expects($this->once())->method('setItems')->with([$item])->willReturnSelf();

        $this->assertSame($searchResults, $this->model->getList($searchCriteria));
    }
}
