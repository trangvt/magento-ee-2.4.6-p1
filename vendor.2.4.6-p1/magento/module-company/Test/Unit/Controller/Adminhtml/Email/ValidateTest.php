<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Email;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Company\Controller\Adminhtml\Email\Validate;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Validate.
 */
class ValidateTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

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
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->validate = $objectManagerHelper->getObject(
            Validate::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'companyRepository' => $this->companyRepository,
                '_request' => $this->request,
                'resultFactory' => $this->resultFactory,
            ]
        );
    }

    /**
     * Test execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->once())->method('setData')->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($resultJson);
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(1);
        $this->companyRepository->expects($this->once())->method('getList')->willReturn($searchResults);

        $this->assertInstanceOf(Json::class, $this->validate->execute());
    }
}
