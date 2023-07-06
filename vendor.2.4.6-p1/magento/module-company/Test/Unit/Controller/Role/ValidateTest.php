<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Role;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\Data\RoleSearchResultsInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Controller\Role\Validate;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\RoleRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateTest extends TestCase
{
    /**
     * @var CompanyUser|MockObject
     */
    private $companyUser;

    /**
     * @var RoleRepositoryInterface|MockObject
     */
    private $roleRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Validate
     */
    private $validate;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyUser = $this->createMock(CompanyUser::class);
        $this->roleRepository = $this->createMock(RoleRepository::class);
        $this->searchCriteriaBuilder = $this->createMock(
            SearchCriteriaBuilder::class
        );
        $this->request = $this->createMock(
            RequestInterface::class
        );
        $this->resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->validate = $objectManagerHelper->getObject(
            Validate::class,
            [
                'companyUser' => $this->companyUser,
                'roleRepository' => $this->roleRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                '_request' => $this->request,
                'resultFactory' => $this->resultFactory,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $roleName = 'Role 1';
        $companyId = 2;

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('company_role_name')
            ->willReturn($roleName);

        $this->companyUser->expects($this->once())
            ->method('getCurrentCompanyId')
            ->willReturn($companyId);

        $result = $this->createMock(Json::class);
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($result);
        $result->expects($this->once())
            ->method('setData')
            ->with(['company_role_name' => true])
            ->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')
            ->withConsecutive(
                [
                    RoleInterface::ROLE_NAME,
                    $roleName
                ],
                [
                    RoleInterface::COMPANY_ID,
                    $companyId
                ]
            )
            ->willReturnOnConsecutiveCalls($this->searchCriteriaBuilder, $this->searchCriteriaBuilder);

        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);

        $searchResults = $this->getMockForAbstractClass(RoleSearchResultsInterface::class);
        $this->roleRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(0);

        $this->assertEquals($result, $this->validate->execute());
    }
}
