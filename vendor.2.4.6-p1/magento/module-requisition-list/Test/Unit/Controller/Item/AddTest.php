<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\Product\Url;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Controller\Item\Add;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionListItem\Locator;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder\ConfigurationException;
use Magento\RequisitionList\Model\RequisitionListItem\SaveHandler;
use Magento\RequisitionList\Model\RequisitionListProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Add product item to requisition list.
 * @see Add
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var RequestValidator|MockObject
     */
    private $requestValidator;

    /**
     * @var SaveHandler|MockObject
     */
    private $requisitionListItemSaveHandler;

    /**
     * @var RequisitionListProduct|MockObject
     */
    private $requisitionListProduct;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Locator|MockObject
     */
    private $requisitionListItemLocator;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var Add
     */
    private $add;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultFactory = $this
            ->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->request = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getHeader'])
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestValidator = $this
            ->getMockBuilder(RequestValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemSaveHandler = $this
            ->getMockBuilder(SaveHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListProduct = $this
            ->getMockBuilder(RequisitionListProduct::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemLocator = $this->getMockBuilder(
            Locator::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->requisitionListRepository = $this
            ->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->urlBuilder = $this
            ->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->add = $objectManagerHelper->getObject(
            Add::class,
            [
                'resultFactory' => $this->resultFactory,
                '_request' => $this->request,
                'messageManager' => $this->messageManager,
                'requestValidator' => $this->requestValidator,
                'requisitionListItemSaveHandler' => $this->requisitionListItemSaveHandler,
                'requisitionListProduct' => $this->requisitionListProduct,
                'logger' => $this->logger,
                'requisitionListItemLocator' => $this->requisitionListItemLocator,
                'requisitionListRepository' => $this->requisitionListRepository,
                'urlBuilder' => $this->urlBuilder,
            ]
        );
    }

    /**
     * Test for execute() with valid request payload and successful response from cart page
     *
     * @return void
     */
    public function testExecuteFromCartPage()
    {
        $sku = 'sku';
        $itemId = 1;
        $listId = 2;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $productData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getOptions'])
            ->getMock();
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->willReturnMap([
                ['item_id', null, $itemId],
                ['list_id', null, $listId],
                ['product_data', null, $productData],
                ['isFromCartPage', null, true],
            ]);
        $this->request->expects($this->atLeastOnce())->method('getHeader')
            ->willReturn('mock');
        $productData->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $productData->expects($this->atLeastOnce())->method('getOptions')->willReturn([]);
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('prepareProductData')
            ->willReturn($productData);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getName')->willReturn('Desired Product to Add to Req List');
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('getProduct')->with($sku)
            ->willReturn($product);
        $message = $this->getMockBuilder(Phrase::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemSaveHandler->expects($this->atLeastOnce())->method('saveItem')->willReturn($message);

        $result->expects($this->atLeastOnce())->method('setPath')->willReturnSelf();

        $resolvedRequisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resolvedRequisitionList->expects($this->once())->method('getName')->willReturn('Resolved Req List Name');
        $resolvedRequisitionList->expects($this->once())->method('getId')->willReturn($listId);

        $this->requisitionListRepository->expects($this->once())->method('get')->with($listId)->willReturn(
            $resolvedRequisitionList
        );

        $this->urlBuilder->expects($this->once())->method('getUrl')->with(
            'requisition_list/requisition/view',
            ['requisition_id' => $listId]
        )->willReturn('URL to the Req List');

        $this->messageManager->expects($this->atLeastOnce())->method('addComplexSuccessMessage')->with(
            'addCartItemToRequisitionListSuccessMessage',
            [
                'product_name' => 'Desired Product to Add to Req List',
                'requisition_list_url' => 'URL to the Req List',
                'requisition_list_name' => 'Resolved Req List Name'
            ]
        );
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertInstanceOf(ResultInterface::class, $this->add->execute());
    }

    /**
     * Test for execute() with valid request payload and successful response from non-cart page
     *
     * @return void
     */
    public function testExecuteFromNonCartPage()
    {
        $sku = 'sku';
        $itemId = 1;
        $listId = 2;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $productData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getOptions'])
            ->getMock();
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->willReturnMap([
                ['item_id', null, $itemId],
                ['list_id', null, $listId],
                ['product_data', null, $productData],
                ['isFromCartPage', null, false],
            ]);
        $this->request->expects($this->atLeastOnce())->method('getHeader')
            ->willReturn('mock');
        $productData->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $productData->expects($this->atLeastOnce())->method('getOptions')->willReturn([]);
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('prepareProductData')
            ->willReturn($productData);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('getProduct')->with($sku)
            ->willReturn($product);
        $message = $this->getMockBuilder(Phrase::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemSaveHandler->expects($this->atLeastOnce())->method('saveItem')->willReturn($message);

        $result->expects($this->atLeastOnce())->method('setPath')->willReturnSelf();

        $resolvedRequisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListRepository->expects($this->once())->method('get')->with($listId)->willReturn(
            $resolvedRequisitionList
        );

        $this->messageManager->expects($this->atLeastOnce())->method('addSuccessMessage')->with($message);
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertInstanceOf(ResultInterface::class, $this->add->execute());
    }

    /**
     * Test for execute() when product with provided sku doesn't exist.
     *
     * @return void
     */
    public function testExecuteWithNotExistingProduct()
    {
        $sku = 'sku';
        $itemId = 1;
        $listId = 2;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $productData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku'])
            ->getMock();
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->willReturnOnConsecutiveCalls($productData, $itemId, $productData, $listId, $listId);
        $productData->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('prepareProductData')
            ->willReturn($productData);
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('getProduct')->with($sku)
            ->willReturn(null);

        $this->messageManager->expects($this->atLeastOnce())->method('addErrorMessage');
        $result->expects($this->atLeastOnce())->method('setPath')->willReturnSelf();
        $this->messageManager->expects($this->never())->method('addComplexSuccessMessage');

        $this->assertInstanceOf(ResultInterface::class, $this->add->execute());
    }

    /**
     * Test for execute with not configured product.
     *
     * @return void
     */
    public function testExecuteWithNotConfiguredProduct()
    {
        $sku = 'sku';
        $listId = 2;
        $url = 'url';
        $warning = new Phrase('Warning message.');
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath', 'setUrl', 'setRefererUrl'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $productData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getOptions'])
            ->getMock();
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturnOnConsecutiveCalls(
            $productData,
            null,
            $productData,
            $listId,
            $listId,
            $productData,
            $productData
        );
        $this->request->expects($this->atLeastOnce())->method('getHeader')
            ->willReturn('mock');
        $productData->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $productData->expects($this->atLeastOnce())->method('getOptions')->willReturn(null);
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('prepareProductData')
            ->willReturn($productData);
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeInstance', 'getUrlModel'])
            ->getMockForAbstractClass();
        $this->requisitionListProduct->expects($this->once())->method('getProduct')->with($sku)
            ->willReturn($product);
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['processConfiguration'])
            ->getMockForAbstractClass();
        $typeInstance->method('processConfiguration')->willReturn($warning);
        $product->method('getTypeInstance')->willReturn($typeInstance);
        $this->requisitionListItemSaveHandler->expects($this->atLeastOnce())->method('saveItem')
            ->willThrowException(new ConfigurationException($warning));
        $urlModel = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();
        $urlModel->expects($this->atLeastOnce())->method('getUrl')->willReturn($url);
        $product->expects($this->atLeastOnce())->method('getUrlModel')->willReturn($urlModel);
        $this->messageManager->expects($this->atLeastOnce())->method('addWarningMessage')->with($warning);
        $result->expects($this->atLeastOnce())->method('setUrl')->willReturnSelf();
        $this->messageManager->expects($this->never())->method('addComplexSuccessMessage');

        $this->assertInstanceOf(ResultInterface::class, $this->add->execute());
    }

    /**
     * Test for execute when list with provided ID doesn't exist.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException()
    {
        $sku = 'sku';
        $itemId = 1;
        $listId = 0;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $productData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getOptions'])
            ->getMock();
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->withConsecutive(['product_data'], ['item_id'], ['list_id'], ['product_data'], ['list_id'])
            ->willReturnOnConsecutiveCalls($productData, $itemId, $listId, $productData, $listId);
        $productData->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequisitionListId'])
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getRequisitionListId')->willReturn($listId);
        $this->requisitionListItemLocator->expects($this->once())
            ->method('getItem')
            ->willReturn($item);
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('prepareProductData')
            ->willReturn($productData);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('getProduct')->with($sku)
            ->willReturn($product);

        $this->requisitionListRepository->expects($this->once())->method('get')->with($listId)->willThrowException(
            new NoSuchEntityException(__('No such requisition list could be found.'))
        );

        $message = __('We couldn\'t find the requested requisition list.');
        $this->messageManager->expects($this->atLeastOnce())->method('addErrorMessage')->with($message);
        $result->expects($this->atLeastOnce())
            ->method('setPath')
            ->with('requisition_list/requisition/index')
            ->willReturnSelf();

        $this->assertInstanceOf(ResultInterface::class, $this->add->execute());
    }

    /**
     * Test for execute() with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $this->prepareMocksForExecuteWithException();
        $phrase = new Phrase(__('Exception'));
        $exception = new LocalizedException($phrase);
        $this->request->expects($this->atLeastOnce())->method('getHeader')
            ->willReturn('mock');
        $this->requisitionListItemSaveHandler->expects($this->atLeastOnce())->method('saveItem')
            ->willThrowException($exception);
        $this->messageManager->expects($this->atLeastOnce())->method('addErrorMessage');

        $this->assertInstanceOf(ResultInterface::class, $this->add->execute());
    }

    /**
     * Test for execute() with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->prepareMocksForExecuteWithException();
        $exception = new \Exception();
        $this->request->expects($this->atLeastOnce())->method('getHeader')
            ->willReturn('mock');
        $this->requisitionListItemSaveHandler->expects($this->atLeastOnce())->method('saveItem')
            ->willThrowException($exception);
        $this->messageManager->expects($this->atLeastOnce())->method('addErrorMessage');

        $this->assertInstanceOf(ResultInterface::class, $this->add->execute());
    }

    /**
     * Test for execute() with Exception and empty item id.
     *
     * @return void
     */
    public function testExecuteWithExceptionAndEmptyItemId()
    {
        $sku = 'SKU1';
        $itemId = null;
        $listId = 2;
        $exceptionMessage = 'Error message';
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath', 'setRefererUrl'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $productData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku'])
            ->getMock();
        $productData->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeInstance', 'getUrlModel'])
            ->getMockForAbstractClass();
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('getProduct')->with($sku)
            ->willReturn($product);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->willReturnOnConsecutiveCalls($productData, $itemId, $productData, $listId, $listId);
        $this->request->expects($this->atLeastOnce())->method('getHeader')
            ->willReturn('mock');
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('prepareProductData')
            ->willReturn($productData);
        $result->expects($this->never())->method('setPath')->willReturnSelf();
        $result->expects($this->atLeastOnce())->method('setRefererUrl')->willReturnSelf();
        $exception = new \Exception($exceptionMessage);
        $this->requisitionListItemSaveHandler->expects($this->atLeastOnce())->method('saveItem')
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addErrorMessage')
            ->with(__('We can\'t add the item to the Requisition List right now: %1.', $exceptionMessage));

        $this->assertInstanceOf(ResultInterface::class, $this->add->execute());
    }

    /**
     * Test for execute() with redirect.
     *
     * @return void
     */
    public function testExecuteWithRedirect()
    {
        $resultFromController = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMockForAbstractClass();

        $resultFromValidation = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMockForAbstractClass();

        $this->resultFactory
            ->expects($this->atLeastOnce())
            ->method('create')
            ->with('redirect')
            ->willReturn($resultFromController);

        $this->requestValidator
            ->expects($this->atLeastOnce())
            ->method('getResult')
            ->willReturn($resultFromValidation);

        $this->assertSame($resultFromValidation, $this->add->execute());
    }

    /**
     * Prepare mocks for execute with Exception and LocalizedException.
     *
     * @return void
     */
    private function prepareMocksForExecuteWithException()
    {
        $sku = 'sku';
        $itemId = 1;
        $listId = 2;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath', 'setRefererUrl'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $productData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku'])
            ->getMock();
        $productData->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('getProduct')->with($sku)
            ->willReturn($product);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->willReturnOnConsecutiveCalls($productData, $itemId, $productData, $listId, $listId);
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('prepareProductData')
            ->willReturn($productData);
        $result->expects($this->atLeastOnce())->method('setPath')->willReturnSelf();
        $result->expects($this->never())->method('setRefererUrl')->willReturnSelf();
    }
}
