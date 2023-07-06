<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitSearchResultsInterface;
use Magento\CompanyCredit\Model\CreditLimit\SearchProvider;
use Magento\CompanyCredit\Model\CreditLimitFactory;
use Magento\CompanyCredit\Model\CreditLimitRepository;
use Magento\CompanyCredit\Model\ResourceModel\CreditLimit;
use Magento\CompanyCredit\Model\SaveHandler;
use Magento\CompanyCredit\Model\Validator;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreditLimitRepository model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditLimitRepositoryTest extends TestCase
{
    /**
     * @var CreditLimitFactory|MockObject
     */
    private $creditLimitFactory;

    /**
     * @var CreditLimit|MockObject
     */
    private $creditLimitResource;

    /**
     * @var Validator|MockObject
     */
    private $validatorMock;

    /**
     * @var SaveHandler|MockObject
     */
    private $saveHandlerMock;

    /**
     * @var SearchProvider
     */
    private $searchProvider;

    /**
     * @var CreditLimitRepository
     */
    private $creditLimitRepository;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimitFactory = $this->getMockBuilder(CreditLimitFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->creditLimitResource =
            $this->getMockBuilder(CreditLimit::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->validatorMock = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->saveHandlerMock = $this->getMockBuilder(SaveHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchProvider = $this->getMockBuilder(SearchProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->creditLimitRepository = $objectManager->getObject(
            CreditLimitRepository::class,
            [
                'creditLimitFactory'  => $this->creditLimitFactory,
                'creditLimitResource' => $this->creditLimitResource,
                'validator'           => $this->validatorMock,
                'saveHandler'         => $this->saveHandlerMock,
                'searchProvider'      => $this->searchProvider,
            ]
        );
    }

    /**
     * Test for method save.
     *
     * @return void
     */
    public function testSave()
    {
        $creditLimitId = 1;
        $creditLimitMock = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'getId', 'getCompanyId', 'getCurrencyCode'])
            ->getMockForAbstractClass();
        $creditLimitData = [CreditLimitInterface::CURRENCY_CODE => 'USD'];
        $creditLimitMock->expects($this->once())->method('getData')->willReturn($creditLimitData);
        $creditLimitMock->expects($this->any())->method('getId')->willReturn($creditLimitId);
        $originalCreditLimitMock = $this->prepareGetCreditLimitMocks($creditLimitId);
        $originalCreditLimitMock->expects($this->any())->method('getId')->willReturn(null);
        $this->validatorMock->expects($this->once())->method('validateCreditData')->with($creditLimitData);
        $originalCreditLimitMock->expects($this->once())->method('getCurrencyCode')->willReturn('EUR');
        $this->saveHandlerMock->expects($this->once())->method('execute')->with($creditLimitMock)->willReturnSelf();
        $this->creditLimitResource->expects($this->once())->method('delete')->with($originalCreditLimitMock)
            ->willReturnSelf();
        $this->assertEquals($creditLimitMock, $this->creditLimitRepository->save($creditLimitMock));
    }

    /**
     * Prepare mocks for credit limit repository get() method.
     *
     * @param int $creditLimitId
     * @return MockObject
     */
    private function prepareGetCreditLimitMocks($creditLimitId)
    {
        $companyId = 2;
        $originalCreditLimitMock = $this->getMockBuilder(\Magento\CompanyCredit\Model\CreditLimit::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'getId', 'getCompanyId', 'getCurrencyCode'])
            ->getMockForAbstractClass();
        $this->creditLimitFactory->expects($this->once())->method('create')->willReturn($originalCreditLimitMock);
        $this->creditLimitResource->expects($this->once())
            ->method('load')->with($originalCreditLimitMock, $creditLimitId)->willReturnSelf();
        $this->validatorMock->expects($this->once())->method('checkCompanyCreditExist')
            ->with($originalCreditLimitMock);
        $originalCreditLimitMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);

        return $originalCreditLimitMock;
    }

    /**
     * Test for method get.
     *
     * @return void
     */
    public function testGet()
    {
        $creditLimitId = 1;
        $creditLimit = $this->prepareGetCreditLimitMocks($creditLimitId);

        $this->assertEquals($creditLimit, $this->creditLimitRepository->get($creditLimitId));
    }

    /**
     * Test for method delete.
     *
     * @return void
     */
    public function testDelete()
    {
        $creditLimit = $this->getMockBuilder(\Magento\CompanyCredit\Model\CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $creditLimit->expects($this->once())->method('getId')->willReturn(1);
        $this->creditLimitResource->expects($this->once())->method('delete')->with($creditLimit)->willReturnSelf();
        $this->assertTrue($this->creditLimitRepository->delete($creditLimit));
    }

    /**
     * Test for method delete with exception.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->expectExceptionMessage('Cannot delete credit limit with id 1');
        $creditLimit = $this->getMockBuilder(\Magento\CompanyCredit\Model\CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $creditLimit->expects($this->exactly(2))->method('getId')->willReturn(1);
        $this->creditLimitResource->expects($this->once())->method('delete')->with($creditLimit)
            ->willThrowException(new \Exception('Exception message'));
        $this->creditLimitRepository->delete($creditLimit);
    }

    /**
     * Test for method getList.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults = $this->getMockBuilder(CreditLimitSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchProvider->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $this->assertEquals($searchResults, $this->creditLimitRepository->getList($searchCriteria));
    }
}
