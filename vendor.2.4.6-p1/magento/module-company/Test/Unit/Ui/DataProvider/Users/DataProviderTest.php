<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Ui\DataProvider\Users;

use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\Company\StructureFactory;
use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Ui\DataProvider\Users\DataProvider as CompanyUiDataProvider;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @var ReportingInterface|MockObject
     */
    private $reporting;

    /**
     * @var CompanyAdminPermission|MockObject
     */
    private $companyAdminPermission;

    /**
     * @var RoleManagementInterface|MockObject
     */
    private $roleManagement;

    /**
     * @var StructureFactory|MockObject
     */
    private $structureFactory;

    /**
     * @var \Magento\Company\Ui\DataProvider\Roles\DataProvider
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
        $this->reporting = $this->getMockForAbstractClass(
            ReportingInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['search']
        );
        $this->companyAdminPermission = $this->createPartialMock(
            CompanyAdminPermission::class,
            ['isGivenUserCompanyAdmin']
        );
        $this->roleManagement = $this->getMockForAbstractClass(
            RoleManagementInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCompanyAdminRoleId', 'getCompanyAdminRoleName']
        );
        $this->structureFactory = $this->createPartialMock(
            StructureFactory::class,
            ['create']
        );
        $objectManager = new ObjectManager($this);
        $this->dataProvider = $objectManager->getObject(
            CompanyUiDataProvider::class,
            [
                'filterBuilder' => $this->filterBuilder,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'companyUser' => $this->companyUser,
                'companyAdminPermission' => $this->companyAdminPermission,
                'roleManagement' => $this->roleManagement,
                'structureFactory' => $this->structureFactory,
                'reporting' => $this->reporting,
            ]
        );
    }

    /**
     * Test getData method.
     *
     * @param int $companyAdminRoleId
     * @param string $companyAdminRoleName
     * @param string $teamName
     * @param int $totalRecords
     * @param array $expectedResult
     * @return void
     * @dataProvider getDataDataProvider
     */
    public function testGetData(
        $companyAdminRoleId,
        $companyAdminRoleName,
        $teamName,
        $totalRecords,
        array $expectedResult
    ) {
        $companyId = 1;
        $customerId = 1;
        $filter = $this->getMockForAbstractClass(
            Filter::class,
            [],
            '',
            false
        );
        $searchResult = $this->getMockForAbstractClass(
            SearchResultInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getItems', 'getTotalCount']
        );
        $item = $this->getMockForAbstractClass(
            DocumentInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getId', 'getCustomAttributes']
        );
        $customAttribute = $this->getMockForAbstractClass(
            AttributeInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getAttributeCode', 'getValue']
        );
        $structure = $this->createPartialMock(
            Structure::class,
            ['getTeamNameByCustomerId']
        );
        $this->companyUser->expects($this->once())->method('getCurrentCompanyId')->willReturn($companyId);
        $this->filterBuilder->expects($this->once())
            ->method('setField')
            ->with('company_customer.company_id')
            ->willReturnSelf();
        $this->filterBuilder->expects($this->once())
            ->method('setConditionType')
            ->with('eq')
            ->willReturnSelf();
        $this->filterBuilder->expects($this->once())
            ->method('setValue')
            ->with($companyId)
            ->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('create')->willReturn($filter);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with($filter)
            ->willReturnSelf();
        $searchCriteria = $this->createPartialMock(
            SearchCriteria::class,
            ['setRequestName']
        );
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchCriteria->expects($this->once())->method('setRequestName')->willReturnSelf();
        $this->reporting->expects($this->once())->method('search')->with($searchCriteria)->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$item]);
        $item->expects($this->once())->method('getCustomAttributes')->willReturn([$customAttribute]);
        $customAttribute->expects($this->once())->method('getAttributeCode')->willReturn('some_code');
        $customAttribute->expects($this->once())->method('getValue')->willReturn('some_value');
        $this->companyAdminPermission->expects($this->once())
            ->method('isGivenUserCompanyAdmin')
            ->with(1)
            ->willReturn(true);
        $this->roleManagement->expects($this->once())->method('getCompanyAdminRoleId')->willReturn($companyAdminRoleId);
        $this->roleManagement->expects($this->once())
            ->method('getCompanyAdminRoleName')
            ->willReturn($companyAdminRoleName);
        $item->expects($this->exactly(2))->method('getId')->willReturn($customerId);
        $this->structureFactory->expects($this->once())->method('create')->willReturn($structure);
        $structure->expects($this->once())->method('getTeamNameByCustomerId')->with($customerId)->willReturn($teamName);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalRecords);

        $this->assertEquals($expectedResult, $this->dataProvider->getData());
    }

    /**
     * Test getData method throws exception.
     *
     * @return void
     */
    public function testGetDataWithException()
    {
        $this->companyUser->expects($this->once())->method('getCurrentCompanyId')->willReturn(0);
        $this->filterBuilder->expects($this->once())
            ->method('setField')
            ->with('company_customer.company_id')
            ->willReturnSelf();

        $this->assertEquals(
            [
                'items' => [],
                'totalRecords' => 0,
            ],
            $this->dataProvider->getData()
        );
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
                'Role Name',
                'Team Name',
                1,
                [
                    'totalRecords' => 1,
                    'items' => [
                        [
                            'role_id' => 1,
                            'role_name' => new Phrase('Role Name'),
                            'team' => 'Team Name',
                            'some_code' => 'some_value'
                        ]
                    ]
                ],
            ],
            [
                15,
                'Role Name 15',
                'Custom Team Name',
                3,
                [
                    'totalRecords' => 3,
                    'items' => [
                        [
                            'role_id' => 15,
                            'role_name' => new Phrase('Role Name 15'),
                            'team' => 'Custom Team Name',
                            'some_code' => 'some_value'
                        ]
                    ]
                ],
            ],
        ];
    }
}
