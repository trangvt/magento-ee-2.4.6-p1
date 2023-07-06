<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Source;

use Magento\Framework\Api\Search\SearchCriteriaFactory;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Plugin\Source\SharedCatalogGroupsProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for SharedCatalogGroupsProcessor plugin.
 */
class SharedCatalogGroupsProcessorTest extends TestCase
{
    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SearchCriteriaFactory|MockObject
     */
    private $searchCriteriaFactory;

    /**
     * @var SharedCatalogGroupsProcessor
     */
    private $groupsProcessorPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaFactory = $this
            ->getMockBuilder(SearchCriteriaFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->groupsProcessorPlugin = $objectManager->getObject(
            SharedCatalogGroupsProcessor::class,
            [
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'searchCriteriaFactory' => $this->searchCriteriaFactory,
            ]
        );
    }

    /**
     * Test for prepareGroups method.
     *
     * @return void
     */
    public function testPrepareGroups()
    {
        $groups = [
            [
                'label' => 'Customer Group 1',
                'value' => 1,
            ],
            [
                'label' => 'Customer Group 2',
                'value' => 2,
            ],
        ];
        $customerGroupId = 1;
        $sharedCatalogName = 'Shared Catalog 1';
        $searchCriteria = $this
            ->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaFactory->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this
            ->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $sharedCatalog = $this
            ->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())->method('getItems')->willReturn([$sharedCatalog]);
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalog->expects($this->atLeastOnce())->method('getName')->willReturn($sharedCatalogName);

        $this->assertEquals(
            [
                [
                    'label' => __('Customer Groups'),
                    'value' => [
                        [
                            'label' => 'Customer Group 2',
                            'value' => 2,
                        ]
                    ]
                ],
                [
                    'label' => __('Shared Catalogs'),
                    'value' => [
                        [
                            'label' => $sharedCatalogName,
                            'value' => 1
                        ]
                    ],
                ],
            ],
            $this->groupsProcessorPlugin->prepareGroups($groups)
        );
    }

    /**
     * Test for prepareGroups method with empty groups list.
     *
     * @return void
     */
    public function testPrepareGroupsWithEmptyList()
    {
        $groups = [];
        $this->assertEquals([], $this->groupsProcessorPlugin->prepareGroups($groups));
    }
}
