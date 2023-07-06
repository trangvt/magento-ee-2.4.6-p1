<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Company;

use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\Company\Save;
use Magento\Company\Model\ResourceModel\Company;
use Magento\Company\Model\SaveHandlerPool;
use Magento\Company\Model\SaveValidatorPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Model\ResourceModel\User\Collection;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\Company\Save class.
 */
class SaveTest extends TestCase
{
    /**
     * @var SaveHandlerPool|MockObject
     */
    private $saveHandlerPool;

    /**
     * @var Company|MockObject
     */
    private $companyResource;

    /**
     * @var CompanyInterfaceFactory|MockObject
     */
    private $companyFactory;

    /**
     * @var SaveValidatorPool|MockObject
     */
    private $saveValidatorPool;

    /**
     * @var CollectionFactory|MockObject
     */
    private $userCollectionFactory;

    /**
     * @var Save
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyResource = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->saveHandlerPool = $this->getMockBuilder(SaveHandlerPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyFactory = $this->getMockBuilder(CompanyInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->saveValidatorPool = $this->getMockBuilder(SaveValidatorPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCollectionFactory = $this
            ->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Save::class,
            [
                'saveHandlerPool' => $this->saveHandlerPool,
                'companyResource' => $this->companyResource,
                'companyFactory' => $this->companyFactory,
                'userCollectionFactory' => $this->userCollectionFactory,
                'saveValidatorPool' => $this->saveValidatorPool,
            ]
        );
    }

    /**
     * Test save method.
     *
     * @return void
     */
    public function testSave()
    {
        $companyId = 1;
        $regionId = 5;
        $company = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $initialCompany = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->atLeastOnce())->method('getRegionId')->willReturn($regionId);
        $company->expects($this->atLeastOnce())->method('setRegion')->with(null)->willReturnSelf();
        $company->expects($this->atLeastOnce())
            ->method('getSalesRepresentativeId')
            ->willReturn(null);
        $this->userCollectionFactory->expects($this->once())->method('create')->willReturn($userCollection);
        $userCollection->expects($this->once())->method('setPageSize')->with(1)->willReturnSelf();
        $userCollection->expects($this->once())->method('getFirstItem')->willReturn($user);
        $user->expects($this->once())->method('getId')->willReturn(1);
        $company->expects($this->atLeastOnce())->method('setSalesRepresentativeId')->with(1)->willReturnSelf();
        $company->expects($this->atLeastOnce())->method('getId')->willReturn($companyId);
        $this->companyFactory->expects($this->once())->method('create')->willReturn($initialCompany);
        $this->companyResource->expects($this->once())
            ->method('load')
            ->with($initialCompany, $companyId)
            ->willReturn($initialCompany);
        $this->saveValidatorPool->expects($this->once())->method('execute')->with($company, $initialCompany);
        $this->companyResource->expects($this->once())->method('save')->with($company)->willReturnSelf();
        $this->saveHandlerPool->expects($this->once())->method('execute')->with($company, $initialCompany);

        $this->assertSame($company, $this->model->save($company));
    }

    /**
     * Test save method with no region id.
     *
     * @return void
     */
    public function testSaveNoRegionId()
    {
        $companyId = 1;
        $company = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $initialCompany = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->atLeastOnce())->method('getRegionId')->willReturn(null);
        $company->expects($this->atLeastOnce())->method('setRegionId')->with(null)->willReturnSelf();
        $company->expects($this->atLeastOnce())
            ->method('getSalesRepresentativeId')
            ->willReturn(null);
        $this->userCollectionFactory->expects($this->once())->method('create')->willReturn($userCollection);
        $userCollection->expects($this->once())->method('setPageSize')->with(1)->willReturnSelf();
        $userCollection->expects($this->once())->method('getFirstItem')->willReturn($user);
        $user->expects($this->once())->method('getId')->willReturn(1);
        $company->expects($this->atLeastOnce())->method('setSalesRepresentativeId')->with(1)->willReturnSelf();
        $company->expects($this->atLeastOnce())->method('getId')->willReturn($companyId);
        $this->companyFactory->expects($this->once())->method('create')->willReturn($initialCompany);
        $this->companyResource->expects($this->once())
            ->method('load')
            ->with($initialCompany, $companyId)
            ->willReturn($initialCompany);
        $this->saveValidatorPool->expects($this->once())->method('execute')->with($company, $initialCompany);
        $this->companyResource->expects($this->once())->method('save')->with($company)->willReturnSelf();
        $this->saveHandlerPool->expects($this->once())->method('execute')->with($company, $initialCompany);

        $this->assertSame($company, $this->model->save($company));
    }

    /**
     * Test save method when CouldNotSaveException is thrown.
     *
     * @return void
     */
    public function testSaveCompanyWithCouldNotSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not save company');
        $companyId = 1;
        $regionId = 5;
        $exception = new \Exception();
        $company = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $initialCompany = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->atLeastOnce())->method('getRegionId')->willReturn($regionId);
        $company->expects($this->atLeastOnce())->method('setRegion')->with(null)->willReturnSelf();
        $company->expects($this->atLeastOnce())
            ->method('getSalesRepresentativeId')
            ->willReturn(null);
        $this->userCollectionFactory->expects($this->once())->method('create')->willReturn($userCollection);
        $userCollection->expects($this->once())->method('setPageSize')->with(1)->willReturnSelf();
        $userCollection->expects($this->once())->method('getFirstItem')->willReturn($user);
        $user->expects($this->once())->method('getId')->willReturn(1);
        $company->expects($this->atLeastOnce())->method('setSalesRepresentativeId')->with(1)->willReturnSelf();
        $company->expects($this->atLeastOnce())->method('getId')->willReturn($companyId);
        $this->companyFactory->expects($this->once())->method('create')->willReturn($initialCompany);
        $this->companyResource->expects($this->once())
            ->method('load')
            ->with($initialCompany, $companyId)
            ->willReturn($initialCompany);
        $this->saveValidatorPool->expects($this->once())->method('execute')->with($company, $initialCompany);
        $this->companyResource->expects($this->once())->method('save')->with($company)->willThrowException($exception);

        $this->model->save($company);
    }
}
