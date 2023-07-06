<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Setup;

use ArrayIterator;
use Magento\Company\Setup\CompanySetup;
use Magento\Company\Api\Data\RoleSearchResultsInterface;
use Magento\Company\Model\PermissionManagementInterface;
use Magento\Company\Model\RoleRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class for test company setup
 */
class CompanySetupTest extends TestCase
{
    /** @var CompanySetup */
    private $setupModel;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var RoleRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $roleRepository;

    /**
     * @ingeritdoc
     */
    protected function setUp(): void
    {
        $this->roleRepository = $this->getMockBuilder(RoleRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $permissionManagement = $this->getMockBuilder(PermissionManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setupModel = new CompanySetup (
            $this->roleRepository,
            $this->searchCriteriaBuilder,
            $permissionManagement
        );
    }

    /**
     * Test apply permission with page size
     *
     * @return void
     */
    public function testApplyPermissions(): void
    {
        $roleSearchResult = $this->getMockBuilder(RoleSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTotalCount', 'getItems'])
            ->getMockForAbstractClass();
        $roleSearchResult->method('getTotalCount')
            ->willReturn(0);
        $this->roleRepository->method('getList')
            ->willReturn($roleSearchResult);
        $roleSearchResult->method('getItems')
            ->willReturn( new ArrayIterator([]));
        $searchResult = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setPageSize', 'setCurrentPage'])
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->method('create')
            ->willReturn($searchResult);
        $searchResult->expects($this->once())
            ->method('setPageSize');
        $searchResult->expects($this->once())
            ->method('setCurrentPage');
        $this->setupModel->applyPermissions();
    }
}
