<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Ui\DataProvider\Roles;

use Magento\Company\Api\Data\RoleSearchResultsInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\RoleRepository;
use Magento\Company\Model\UserRoleManagement;
use Magento\Company\Ui\DataProvider\Roles\DataProvider as SystemUnderTest;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CompanyUser|MockObject
     */
    private $companyUser;

    /**
     * @var RoleRepository|MockObject
     */
    private $roleRepository;

    /**
     * @var UserRoleManagement|MockObject
     */
    private $userRoleManagement;

    /**
     * @var SystemUnderTest
     */
    private $dataProvider;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->filterBuilder = $this->createPartialMock(
            FilterBuilder::class,
            ['setField', 'setConditionType', 'setValue', 'create']
        );
        $this->searchCriteriaBuilder = $this->createPartialMock(
            SearchCriteriaBuilder::class,
            ['addSortOrder', 'addFilter', 'create']
        );
        $this->companyUser = $this->createPartialMock(
            CompanyUser::class,
            ['getCurrentCompanyId']
        );
        $this->roleRepository = $this->createPartialMock(
            RoleRepository::class,
            ['getList']
        );
        $this->userRoleManagement = $this->createPartialMock(
            UserRoleManagement::class,
            ['getUsersCountByRoleId']
        );
        $objectManager = new ObjectManager($this);
        $this->dataProvider = $objectManager->getObject(
            SystemUnderTest::class,
            [
                'filterBuilder' => $this->filterBuilder,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'companyUser' => $this->companyUser,
                'roleRepository' => $this->roleRepository,
                'userRoleManagement' => $this->userRoleManagement,
            ]
        );
    }

    /**
     * Test getData method.
     *
     * @param int $totalRecords
     * @param array $data
     * @param int $usersCount
     * @param array $expectedResult
     * @return void
     * @dataProvider getDataDataProvider
     */
    public function testGetData($totalRecords, array $data, $usersCount, array $expectedResult)
    {
        $currentCompanyId = 1;
        $filter = $this->getMockForAbstractClass(
            Filter::class,
            [],
            '',
            false
        );
        $searchCriteria = $this->createPartialMock(
            SearchCriteria::class,
            ['setRequestName']
        );
        $searchResult = $this->getMockForAbstractClass(
            RoleSearchResultsInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getItems', 'getTotalCount']
        );
        $item = $this->getMockForAbstractClass(
            ExtensibleDataInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getData', 'getRoleId']
        );
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addSortOrder')
            ->with('role_name', 'ASC')
            ->willReturnSelf();
        $this->companyUser->expects($this->once())->method('getCurrentCompanyId')->willReturn($currentCompanyId);
        $this->filterBuilder->expects($this->once())
            ->method('setField')
            ->with('main_table.company_id')
            ->willReturnSelf();
        $this->filterBuilder->expects($this->once())->method('setConditionType')->with('eq')->willReturnSelf();
        $this->filterBuilder->expects($this->once())->method('setValue')->with($currentCompanyId)->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('create')->willReturn($filter);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with($filter)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $searchCriteria->expects($this->once())->method('setRequestName')->willReturnSelf();
        $this->roleRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria, true)
            ->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalRecords);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$item]);
        $item->expects($this->once())->method('getData')->willReturn($data);
        $item->expects($this->once())->method('getRoleId')->willReturn(1);

        $this->userRoleManagement->expects($this->once())
            ->method('getUsersCountByRoleId')
            ->with(1)
            ->willReturn($usersCount);

        $this->assertEquals($expectedResult, $this->dataProvider->getData());
    }

    /**
     * Data provider for getData method.
     *
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            [
                1,
                ['some_key' => 'some_value'],
                3,
                [
                    'totalRecords' => 1,
                    'items' => [
                        [
                            'some_key' => 'some_value',
                            'users_count' => 3
                        ]
                    ]
                ],
            ],
            [
                4,
                ['some_key2' => 'some_value2'],
                15,
                [
                    'totalRecords' => 4,
                    'items' => [
                        [
                            'some_key2' => 'some_value2',
                            'users_count' => 15
                        ]
                    ]
                ],
            ],
        ];
    }
}
