<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Team;

use Magento\Company\Api\Data\TeamSearchResultsInterface;
use Magento\Company\Api\Data\TeamSearchResultsInterfaceFactory;
use Magento\Company\Model\ResourceModel\Team\Collection;
use Magento\Company\Model\ResourceModel\Team\CollectionFactory;
use Magento\Company\Model\Team\GetList;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Company\Model\Team\GetList class.
 */
class GetListTest extends TestCase
{
    /**
     * @var GetList
     */
    private $getListCommand;

    /**
     * @var CollectionFactory|MockObject
     */
    private $teamCollectionFactoryMock;

    /**
     * @var TeamSearchResultsInterfaceFactory|MockObject
     */
    private $searchResultsFactoryMock;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessorMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->teamCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->searchResultsFactoryMock = $this->getMockBuilder(TeamSearchResultsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collectionProcessorMock = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->getMockForAbstractClass();

        $this->getListCommand = (new ObjectManager($this))->getObject(
            GetList::class,
            [
                'teamCollectionFactory' => $this->teamCollectionFactoryMock,
                'searchResultsFactory' => $this->searchResultsFactoryMock,
                'collectionProcessor' => $this->collectionProcessorMock
            ]
        );
    }

    /**
     * Test for `getList` method.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchResults = $this->getMockBuilder(TeamSearchResultsInterface::class)
            ->getMockForAbstractClass();
        $this->searchResultsFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($searchResults);

        $teamCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $teamCollection->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([]);
        $this->teamCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($teamCollection);

        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->getMockForAbstractClass();

        $this->collectionProcessorMock->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $teamCollection);

        $this->assertEquals(
            $searchResults,
            $this->getListCommand->getList($searchCriteria)
        );
    }
}
