<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterfaceFactory;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Controller\Item\Copy;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionList\Items;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CopyTest extends TestCase
{
    /**
     * @var RequestValidator|MockObject
     */
    private $requestValidator;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var RequisitionListManagementInterface|MockObject
     */
    private $requisitionListManagement;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var Items|MockObject
     */
    private $requisitionListItemRepository;

    /**
     * @var RequisitionListItemInterfaceFactory|MockObject
     */
    private $requisitionListItemFactory;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Copy
     */
    private $copy;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->requestValidator = $this->createMock(RequestValidator::class);
        $this->requisitionListRepository =
            $this->getMockForAbstractClass(RequisitionListRepositoryInterface::class);
        $this->requisitionListManagement =
            $this->getMockForAbstractClass(RequisitionListManagementInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->requisitionListItemRepository =
            $this->createMock(Items::class);
        $this->requisitionListItemFactory = $this->createPartialMock(
            RequisitionListItemInterfaceFactory::class,
            ['create']
        );
        $this->resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $objectManager = new ObjectManager($this);
        $this->copy = $objectManager->getObject(
            Copy::class,
            [
                '_request' => $this->request,
                'resultFactory' => $this->resultFactory,
                'requestValidator' => $this->requestValidator,
                'requisitionListRepository' => $this->requisitionListRepository,
                'requisitionListManagement' => $this->requisitionListManagement,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'requisitionListItemRepository' => $this->requisitionListItemRepository,
                'requisitionListItemFactory' => $this->requisitionListItemFactory
            ]
        );
    }

    /**
     * Test execute
     *
     * @param ResultInterface|null $result
     * @dataProvider dataProviderExecute
     */
    public function testExecute($result)
    {
        $this->prepareMocks($result);

        $this->assertInstanceOf(ResultInterface::class, $this->copy->execute());
    }

    /**
     * Test execute with Exception
     */
    public function testExecuteWithException()
    {
        /**
         * @var ResultInterface|MockObject $result
         */
        $result = $this->getMockForAbstractClass(ResultInterface::class);
        $this->prepareMocks($result);
        $exception = new \Exception();
        $this->requisitionListRepository->expects($this->any())->method('save')->willThrowException($exception);

        $this->assertInstanceOf(ResultInterface::class, $this->copy->execute());
    }

    /**
     * Test execute with NoSuchEntityException
     */
    public function testExecuteWithNoSuchEntityException()
    {
        /**
         * @var ResultInterface|MockObject $result
         */
        $result = $this->getMockForAbstractClass(ResultInterface::class);
        $this->prepareMocks($result);
        $phrase = new Phrase(__('Exception'));
        $exception = new NoSuchEntityException($phrase);
        $this->requisitionListRepository->expects($this->any())->method('save')->willThrowException($exception);

        $this->assertInstanceOf(ResultInterface::class, $this->copy->execute());
    }

    /**
     * DataProvider execute
     *
     * @return array
     */
    public function dataProviderExecute()
    {
        $result = $this->getMockForAbstractClass(ResultInterface::class);

        return [
            [$result],
            [null]
        ];
    }

    /**
     * Prepare mocks
     *
     * @param ResultInterface|null $result
     */
    private function prepareMocks($result)
    {
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn($result);
        $resultRedirect = $this->createMock(Redirect::class);
        $resultRedirect->expects($this->any())->method('setRefererUrl')->willReturnSelf();
        $this->resultFactory->expects($this->any())->method('create')->willReturn($resultRedirect);
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $item = $this->getMockBuilder(ExtensibleDataInterface::class)
            ->addMethods(['getQty', 'getOptions', 'getSku'])
            ->getMockForAbstractClass();
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $searchResults->expects($this->any())->method('getItems')->willReturn([$item]);
        $this->requisitionListItemRepository->expects($this->any())->method('getList')->willReturn($searchResults);
        $this->searchCriteriaBuilder->expects($this->any())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($searchCriteria);

        $requisitionList = $this->getMockForAbstractClass(RequisitionListInterface::class);
        $this->requisitionListRepository->expects($this->any())->method('get')->willReturn($requisitionList);
        $requisitionListItem = $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $this->requisitionListItemFactory->expects($this->any())->method('create')->willReturn($requisitionListItem);
    }
}
