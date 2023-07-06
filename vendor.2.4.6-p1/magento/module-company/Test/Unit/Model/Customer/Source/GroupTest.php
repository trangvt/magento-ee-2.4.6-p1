<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Customer\Source;

use Magento\Company\Model\Customer\Source\Group;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\Data\GroupSearchResultsInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private $groupRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder|MockObject
     */
    private $sortOrderBuilder;

    /**
     * @var Group
     */
    private $customerGroupSource;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->groupRepository = $this->createMock(
            GroupRepositoryInterface::class
        );
        $this->searchCriteriaBuilder = $this->createMock(
            SearchCriteriaBuilder::class
        );
        $this->sortOrderBuilder = $this->createMock(
            SortOrderBuilder::class
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->customerGroupSource = $objectManagerHelper->getObject(
            Group::class,
            [
                'groupRepository' => $this->groupRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sortOrderBuilder' => $this->sortOrderBuilder,
            ]
        );
    }

    /**
     * Test for toOptionArray method.
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $groupId = 1;
        $groupName = 'Group Name';

        $customerGroup = $this->createMock(\Magento\Customer\Model\Group::class);
        $sortOrder = $this->createMock(SortOrder::class);
        $searchResults = $this->createMock(
            GroupSearchResultsInterface::class
        );
        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $this->sortOrderBuilder->expects($this->once())
            ->method('setField')->with(GroupInterface::CODE)->willReturnSelf();
        $this->sortOrderBuilder->expects($this->once())->method('setAscendingDirection')->willReturnSelf();
        $this->sortOrderBuilder->expects($this->once())->method('create')->willReturn($sortOrder);
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')
            ->with(GroupInterface::ID, GroupInterface::NOT_LOGGED_IN_ID, 'neq')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addSortOrder')->with($sortOrder)->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->groupRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $searchResults->expects($this->once())->method('getItems')->willReturn(new \ArrayIterator([$customerGroup]));
        $customerGroup->expects($this->once())->method('getId')->willReturn($groupId);
        $customerGroup->expects($this->once())->method('getCode')->willReturn($groupName);

        $this->assertEquals(
            [['label' => $groupName, 'value' => $groupId]],
            $this->customerGroupSource->toOptionArray()
        );
    }
}
