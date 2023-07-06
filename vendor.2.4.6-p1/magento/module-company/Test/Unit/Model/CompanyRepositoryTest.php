<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\Company;
use Magento\Company\Model\Company\Delete;
use Magento\Company\Model\Company\GetList;
use Magento\Company\Model\Company\Save;
use Magento\Company\Model\CompanyRepository;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for CompanyRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyRepositoryTest extends TestCase
{
    /**
     * @var CompanyRepository
     */
    private $companyRepository;

    /**
     * @var CompanyInterfaceFactory|MockObject
     */
    private $companyFactory;

    /**
     * @var Company|MockObject
     */
    private $company;

    /**
     * @var \Magento\Company\Model\ResourceModel\Company|MockObject
     */
    private $companyResource;

    /**
     * @var GetList|MockObject
     */
    private $companyListGetter;

    /**
     * @var Delete|MockObject
     */
    private $companyDeleter;

    /**
     * @var Save|MockObject
     */
    private $companySaver;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->company = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyFactory = $this->getMockBuilder(CompanyInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->companyResource = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyListGetter = $this->getMockBuilder(GetList::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyDeleter = $this->getMockBuilder(Delete::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companySaver = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->companyRepository = $objectManagerHelper->getObject(
            CompanyRepository::class,
            [
                'companyFactory' => $this->companyFactory,
                'companyDeleter' => $this->companyDeleter,
                'companyListGetter' => $this->companyListGetter,
                'companySaver' => $this->companySaver
            ]
        );
    }

    /**
     * Test get method.
     *
     * @return void
     */
    public function testGet()
    {
        $this->companyFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->company);
        $this->company->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $this->company->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->assertEquals($this->company, $this->companyRepository->get(1));
    }

    /**
     * Test get method throws NoSuchEntityException.
     *
     * @return void
     */
    public function testGetWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with id = 1');
        $this->companyFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->company);
        $this->company->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $this->company->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $this->assertEquals($this->company, $this->companyRepository->get(1));
    }

    /**
     * Test getList.
     *
     * @param int $count
     * @param int $expectedResult
     * @return void
     * @dataProvider getListDataProvider
     */
    public function testGetList($count, $expectedResult)
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);

        $this->companyListGetter->expects($this->once())->method('getList')->willReturn($count);
        $result = $this->companyRepository->getList($searchCriteria);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider fot testGetList.
     *
     * @return array
     */
    public function getListDataProvider()
    {
        return [
            [0, 0],
            [1, 1]
        ];
    }

    /**
     * Test for method save.
     *
     * @return void
     */
    public function testSave()
    {
        $this->assertEquals($this->company, $this->companyRepository->save($this->company));
    }

    /**
     * Test save method with exception.
     *
     * @return void
     */
    public function testSaveWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not save company');
        $exception = new CouldNotSaveException(__('Could not save company'));
        $this->companySaver->expects($this->once())->method('save')->willThrowException($exception);
        $this->companyRepository->save($this->company);
    }

    /**
     * Test for method delete.
     *
     * @return void
     */
    public function testDelete()
    {
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyAttributes->expects($this->any())->method('getCompanyId')
            ->willReturn(1);

        $this->company->expects($this->any())->method('getId')->willReturn(1);
        $this->assertTrue($this->companyRepository->delete($this->company));
    }

    /**
     * Test delete method with exception.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->expectExceptionMessage('Cannot delete company with id 1');
        $exception = new \Exception();
        $this->company->expects($this->any())->method('getId')->willReturn(1);
        $this->setUpCustomerDelete();
        $this->companyDeleter->expects($this->once())->method('delete')->willThrowException($exception);
        $this->companyRepository->delete($this->company);
    }

    /**
     * Test for method deleteById.
     *
     * @return void
     */
    public function testDeleteById()
    {
        $this->companyFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->company);
        $this->company->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $this->company->expects($this->any())->method('getId')->willReturn(1);
        $this->setUpCustomerDelete();
        $this->assertTrue($this->companyRepository->deleteById(1));
    }

    /**
     * Processes attached customers upon company deletion.
     *
     * @return void
     */
    private function setUpCustomerDelete()
    {
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyAttributes->expects($this->any())->method('getCompanyId')
            ->willReturn(1);
        $customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyAttributes', 'getCompanyAttributes'])
            ->getMockForAbstractClass();
        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
    }
}
