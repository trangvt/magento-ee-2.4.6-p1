<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Response;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Controller\Item\CartValidation;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionListProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for CartValidation
 *
 * @see CartValidation
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CartValidationTest extends TestCase
{
    /**
     * @var RequisitionListProduct|MockObject
     */
    private $requisitionListProductMock;

    /**
     * @var RequestValidator|MockObject
     */
    private $requestValidatorMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var JsonFactory|MockObject
     */
    private $jsonFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepositoryMock;

    /**
     * @var ConfigurableProduct|MockObject
     */
    private $configurableProductMock;

    /**
     * @var JsonResult|MockObject
     */
    private $jsonResultMock;

    /**
     * @var MessageManagerInterface|MockObject
     */
    private $messageManagerMock;

    /**
     * @var CartValidation
     */
    private $controller;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->requisitionListProductMock = $this->createMock(RequisitionListProduct::class);
        $this->requestValidatorMock = $this->createMock(RequestValidator::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListRepositoryMock = $this->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManagerMock = $this->getMockBuilder(MessageManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configurableProductMock = $this->createMock(ConfigurableProduct::class);

        $this->controller = new CartValidation(
            $this->requisitionListProductMock,
            $this->requestValidatorMock,
            $this->requestMock,
            $this->jsonFactoryMock,
            $this->loggerMock,
            $this->requisitionListRepositoryMock,
            $this->configurableProductMock,
            $this->messageManagerMock
        );

        $this->jsonResultMock = $this->createMock(JsonResult::class);
        $this->jsonFactoryMock->expects($this->once())->method('create')->willReturn($this->jsonResultMock);
    }

    /**
     * Executing this controller without list_id request parameter will raise a 400 Bad Request response.
     *
     * @return void
     */
    public function testExecuteWithoutListId(): void
    {
        $productData = '{"sku": "someExistentProduct", "qty": 2}';
        $productDataDecoded = new DataObject(json_decode($productData, true));

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                [
                    'list_id', null, null
                ],
                [
                    'product_data', null, $productData
                ]
            ]);

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('prepareProductData')
            ->with($productData)
            ->willReturn($productDataDecoded);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('getProduct')
            ->with($productDataDecoded['sku'])
            ->willReturn($productMock);

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_BAD_REQUEST)
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())->method('setData')->with([
            'success' => false,
            'message' => 'Invalid request, missing parameter list_id'
        ]);

        $this->controller->execute();
    }

    /**
     * Executing this controller with other invalid request/session prerequisites will raise a 400 Bad Request response.
     *
     * @return void
     */
    public function testExecuteWithInvalidRequestPreconditions(): void
    {
        $productData = '{"sku": "someExistentProduct", "qty": 2}';
        $productDataDecoded = new DataObject(json_decode($productData, true));

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                [
                    'list_id', null, 1
                ],
                [
                    'list_name', null, 'Existent Requisition List'
                ],
                [
                    'product_data', null, $productData
                ]
            ]);

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('prepareProductData')
            ->with($productData)
            ->willReturn($productDataDecoded);

        $resultMock = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestValidatorMock
            ->expects($this->once())
            ->method('getResult')
            ->with($this->requestMock)
            ->willReturn($resultMock);

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_BAD_REQUEST)
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())->method('setData')->with([
            'success' => false,
            'message' => 'Invalid request, please try again.'
        ]);

        $this->controller->execute();
    }

    /**
     * Executing this controller with non-existent product SKU will raise a 404 Not Found response.
     *
     * @return void
     */
    public function testExecuteWithNonExistentProduct(): void
    {
        $productData = '{"sku": "nowhereToBeFound", "qty": 2}';

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                [
                    'list_id', null, 1
                ],
                [
                    'product_data', null, $productData
                ]
            ]);

        $this->requestValidatorMock
            ->expects($this->once())
            ->method('getResult')
            ->with($this->requestMock)
            ->willReturn(null);

        $productDataDecoded = new DataObject(json_decode($productData, true));

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('prepareProductData')
            ->with($productData)
            ->willReturn($productDataDecoded);

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('getProduct')
            ->with($productDataDecoded['sku'])
            ->willReturn(false);

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_NOT_FOUND)
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())->method('setData')->with([
            'success' => false,
            'message' => 'Product with requested SKU could not be found.'
        ]);

        $this->controller->execute();
    }

    /**
     * Executing this controller with non-existent requisition list will raise a NoSuchEntityException/404 response.
     *
     * @return void
     */
    public function testExecuteWithNonExistentRequisitionList(): void
    {
        $productData = '{"sku": "somethingExistent", "qty": 2}';

        $listId = 1;

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                [
                    'list_id', null, $listId
                ],
                [
                    'product_data', null, $productData
                ]
            ]);

        $this->requestValidatorMock
            ->expects($this->once())
            ->method('getResult')
            ->with($this->requestMock)
            ->willReturn(null);

        $productDataDecoded = new DataObject(json_decode($productData, true));

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('prepareProductData')
            ->with($productData)
            ->willReturn($productDataDecoded);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('getProduct')
            ->with($productDataDecoded['sku'])
            ->willReturn($productMock);

        $this->requisitionListRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($listId)
            ->willThrowException(new NoSuchEntityException(__('Not found')));

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_NOT_FOUND)
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())->method('setData')->with([
            'success' => false,
            'message' => 'Not found'
        ]);

        $this->controller->execute();
    }

    /**
     * Executing this controller with simple existent product will return 200 success response.
     *
     * @return void
     */
    public function testExecuteWithSimpleExistentProduct(): void
    {
        $productData = '{"sku": "somethingExistent", "qty": 2}';

        $listId = 1;

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                [
                    'list_id', null, $listId
                ],
                [
                    'product_data', null, $productData
                ]
            ]);

        $this->requestValidatorMock
            ->expects($this->once())
            ->method('getResult')
            ->with($this->requestMock)
            ->willReturn(null);

        $productDataDecoded = new DataObject(json_decode($productData, true));

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('prepareProductData')
            ->with($productData)
            ->willReturn($productDataDecoded);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('getProduct')
            ->with($productDataDecoded['sku'])
            ->willReturn($productMock);

        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($listId)
            ->willReturn($requisitionList);

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('isProductExistsInRequisitionList')
            ->willReturn(true);

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Response::HTTP_OK)
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())->method('setData')->with([
            'success' => true,
            'data' => [
                'productExists' => true
            ]
        ]);

        $this->controller->execute();
    }

    /**
     * Executing this controller with requested product existent in requisition list will return 200 success response.
     *
     * @return void
     */
    public function testExecuteWithRequestedProductExistentInRequisitionList(): void
    {
        $productData = '{"sku": "somethingExistent", "qty": 2}';

        $listId = 1;

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                [
                    'list_id', null, $listId
                ],
                [
                    'product_data', null, $productData
                ]
            ]);

        $this->requestValidatorMock
            ->expects($this->once())
            ->method('getResult')
            ->with($this->requestMock)
            ->willReturn(null);

        $productDataDecoded = new DataObject(json_decode($productData, true));

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('prepareProductData')
            ->with($productData)
            ->willReturn($productDataDecoded);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('getProduct')
            ->with($productDataDecoded['sku'])
            ->willReturn($productMock);

        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($listId)
            ->willReturn($requisitionList);

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('isProductExistsInRequisitionList')
            ->willReturn(true);

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Response::HTTP_OK)
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())->method('setData')->with([
            'success' => true,
            'data' => [
                'productExists' => true,
            ]
        ]);

        $this->controller->execute();
    }

    /**
     * Executing this controller with requested product absent in requisition list will return 200 success response.
     *
     * @return void
     */
    public function testExecuteWithRequestedProductAbsentInRequisitionList(): void
    {
        $productData = '{"sku": "somethingExistent", "qty": 2}';

        $listId = 1;

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                [
                    'list_id', null, $listId
                ],
                [
                    'product_data', null, $productData
                ]
            ]);

        $this->requestValidatorMock
            ->expects($this->once())
            ->method('getResult')
            ->with($this->requestMock)
            ->willReturn(null);

        $productDataDecoded = new DataObject(json_decode($productData, true));

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('prepareProductData')
            ->with($productData)
            ->willReturn($productDataDecoded);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('getProduct')
            ->with($productDataDecoded['sku'])
            ->willReturn($productMock);

        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($listId)
            ->willReturn($requisitionList);

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('isProductExistsInRequisitionList')
            ->willReturn(false);

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Response::HTTP_OK)
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())->method('setData')->with([
            'success' => true,
            'data' => [
                'productExists' => false
            ]
        ]);

        $this->controller->execute();
    }

    /**
     * Test user friendly error message output is correct.
     *
     * @param string|null $listName
     * @param string|null $productName
     * @param array $expectedMessages
     *
     * @return void
     * @dataProvider userFriendlyErrorMessageOutputDataProvider
     */
    public function testUserFriendlyErrorMessageOutput(
        ?string $listName,
        ?string $productName,
        array $expectedMessages
    ): void {
        $productData = '{"sku": "someExistentProduct", "qty": 2}';
        $productDataDecoded = new DataObject(json_decode($productData, true));

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                [
                    'list_id', null, null
                ],
                [
                    'list_name', null, $listName
                ],
                [
                    'product_data', null, $productData
                ]
            ]);

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('prepareProductData')
            ->with($productData)
            ->willReturn($productDataDecoded);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $productMock
            ->expects($this->once())
            ->method('getName')
            ->willReturn($productName);

        $this->requisitionListProductMock
            ->expects($this->once())
            ->method('getProduct')
            ->with($productDataDecoded['sku'])
            ->willReturn($productMock);

        $this->jsonResultMock
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->willReturn($this->jsonResultMock);

        $this->jsonResultMock->expects($this->once())->method('setData');
        $withArgs = [];

        foreach ($expectedMessages as $expectedMessage) {
            $withArgs[] = [$expectedMessage];
        }
        $this->messageManagerMock
            ->method('addErrorMessage')
            ->withConsecutive(...$withArgs);

        $this->controller->execute();
    }

    /**
     * Data provider for testUserFriendlyErrorMessageOutput
     *
     * @return array
     */
    public function userFriendlyErrorMessageOutputDataProvider(): array
    {
        return [
            [
                'List Name',
                null,
                [
                    'The product could not be added to the "List Name" requisition list.'
                ]
            ],
            [
                null,
                null,
                [
                    'The product could not be added to the requisition list.'
                ]
            ],
            [
                null,
                'Product Name',
                [
                    'Product Name could not be added to the requisition list.'
                ]
            ],
            [
                'List Name',
                'Product Name',
                [
                    'Product Name could not be added to the "List Name" requisition list.'
                ]
            ]
        ];
    }
}
