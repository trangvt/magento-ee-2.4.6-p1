<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartManagementInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Controller\Item\AddToCart;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionList\ItemSelector;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for AddToCart controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddToCartTest extends TestCase
{
    /**
     * @var RequestValidator|MockObject
     */
    private $requestValidator;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var RequisitionListManagementInterface|MockObject
     */
    private $listManagement;

    /**
     * @var CartManagementInterface|MockObject
     */
    private $cartManagement;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

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
     * @var ItemSelector|MockObject
     */
    private $itemSelector;

    /**
     * @var ResponseInterface|MockObject
     */
    private $response;

    /**
     * @var RedirectInterface|MockObject
     */
    private $redirect;

    /**
     * @var AddToCart
     */
    private $addToCart;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private $cookieManagerMock;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    private $cookieMetadataFactoryMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestValidator = $this->getMockBuilder(RequestValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->listManagement = $this
            ->getMockBuilder(RequisitionListManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartManagement = $this->getMockBuilder(CartManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this
            ->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite'])
            ->getMockForAbstractClass();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemSelector = $this->getMockBuilder(ItemSelector::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->redirect = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->createAdditionalMocks();
        $objectManager = new ObjectManager($this);
        $this->addToCart = $objectManager->getObject(
            AddToCart::class,
            [
                'requestValidator' => $this->requestValidator,
                'userContext' => $this->userContext,
                'logger' => $this->logger,
                'listManagement' => $this->listManagement,
                'cartManagement' => $this->cartManagement,
                'storeManager' => $this->storeManager,
                'resultFactory' => $this->resultFactory,
                '_request' => $this->request,
                'messageManager' => $this->messageManager,
                'itemSelector' => $this->itemSelector,
                '_response' => $this->response,
                '_redirect' => $this->redirect,
                'cookieManager' => $this->cookieManagerMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock
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
        $isReplace = true;
        $listId = 1;
        $itemsIds = '2,3';
        $userId = 4;
        $cartId = 5;
        $websiteId = 1;

        $this->requestValidator->expects($this->once())->method('getResult')->with($this->request)->willReturn(null);
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($result);
        $result->expects($this->once())->method('setRefererUrl')->willReturnSelf();
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->withConsecutive(['is_replace', false], ['requisition_id'], ['selected'])
            ->willReturnOnConsecutiveCalls($isReplace, $listId, $itemsIds);
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn($userId);
        $this->cartManagement->expects($this->once())
            ->method('createEmptyCartForCustomer')->with($userId)->willReturn($cartId);
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requisitionListItem->method('getSku')->willReturn('sku1');
        $requisitionListItem->method('getOptions')->willReturn(
            ['info_buyRequest' => ['product' => '1']]
        );
        $itemsIds = explode(',', $itemsIds);
        $this->itemSelector->expects($this->atLeastOnce())->method('selectItemsFromRequisitionList')
            ->with($listId, $itemsIds, $websiteId)->willReturn([$requisitionListItem]);
        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($websiteMock);
        $websiteMock->expects($this->atLeastOnce())->method('getId')->willReturn($websiteId);
        $this->listManagement->expects($this->once())->method('placeItemsInCart')
            ->with($cartId, [$requisitionListItem], $isReplace)
            ->willReturn([$requisitionListItem]);
        $this->messageManager->expects($this->once())->method('addSuccess')
            ->with(__('You added %1 item(s) to your shopping cart.', 1))->willReturnSelf();
        $productResultMock = $this->getMockForAbstractClass(ProductSearchResultsInterface::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getProductId', 'getName', 'getPrice'])
            ->getMock();
        $productMock->method('getSku')->willReturn('sku1');
        $productMock->method('getProductId')->willReturn('1');
        $productMock->method('getName')->willReturn('name1');
        $productMock->method('getPrice')->willReturn('100');
        $productResultMock->method('getItems')->willReturn([$productMock]);
        $this->cartManagement->expects($this->once())
            ->method('getCartForCustomer')->with($userId)->willReturn($productResultMock);
        $this->cookieManagerMock->expects($this->once())
            ->method('setPublicCookie')
            ->with('add_to_cart')
            ->willReturnSelf();
        $this->assertEquals($result, $this->addToCart->execute());
    }

    /**
     * Test for execute method with request validation errors.
     *
     * @return void
     */
    public function testExecuteWithRequestValidationErrors(): void
    {
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestValidator
            ->expects($this->once())
            ->method('getResult')
            ->with($this->request)
            ->willReturn($result);
        $this->assertEquals($result, $this->addToCart->execute());
    }

    /**
     * Test for execute method with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException(): void
    {
        $userId = 4;
        $exceptionMesage = 'Cart cannot be created';
        $this->requestValidator->expects($this->once())->method('getResult')->with($this->request)->willReturn(null);
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($result);
        $result->expects($this->once())->method('setRefererUrl')->willReturnSelf();
        $this->request->expects($this->atLeastOnce())->method('getParam')->with('is_replace', false)->willReturn(false);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->cartManagement->expects($this->once())->method('createEmptyCartForCustomer')->with($userId)
            ->willThrowException(new LocalizedException(__($exceptionMesage)));
        $this->messageManager->expects($this->once())->method('addError')->with($exceptionMesage)->willReturnSelf();
        $this->assertEquals($result, $this->addToCart->execute());
    }

    /**
     * Test for execute method with InvalidArgumentException.
     *
     * @return void
     */
    public function testExecuteWithInvalidArgumentException(): void
    {
        $requisitionListId = 1;
        $exceptionMesage = 'Invalid argument';
        $redirectPath = 'requisition_list/requisition/view';

        $this->requestValidator->expects($this->once())->method('getResult')->with($this->request)->willReturn(null);
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willThrowException(new \InvalidArgumentException($exceptionMesage));
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($exceptionMesage)
            ->willReturnSelf();
        $this->request->expects($this->atLeastOnce())->method('getParam')->with('requisition_id')
            ->willReturn($requisitionListId);
        $this->redirect->expects($this->once())->method('redirect')
            ->with($this->response, $redirectPath, ['requisition_id' => $requisitionListId]);

        $this->assertEquals($this->response, $this->addToCart->execute());
    }

    /**
     * Test for execute method with generic exception.
     *
     * @return void
     */
    public function testExecuteWithGenericException(): void
    {
        $userId = 4;
        $exception = new \Exception('Cart cannot be created');
        $this->requestValidator->expects($this->once())->method('getResult')->with($this->request)->willReturn(null);
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($result);
        $result->expects($this->once())->method('setRefererUrl')->willReturnSelf();
        $this->request->expects($this->atLeastOnce())->method('getParam')->with('is_replace', false)->willReturn(false);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->cartManagement->expects($this->once())
            ->method('createEmptyCartForCustomer')->with($userId)->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')->with('Something went wrong.')->willReturnSelf();
        $this->logger->expects($this->once())->method('critical')->with($exception);
        $this->assertEquals($result, $this->addToCart->execute());
    }

    private function createAdditionalMocks(): void
    {
        $this->cookieManagerMock = $this->getMockForAbstractClass(CookieManagerInterface::class);

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['createPublicCookieMetadata'])
            ->getMock();
        $cookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieMetadataFactoryMock->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($cookieMetadataMock);
        $cookieMetadataMock->expects($this->any())
            ->method('setDuration')
            ->willReturnSelf();
        $cookieMetadataMock->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();
        $cookieMetadataMock->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();
        $cookieMetadataMock->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();
    }
}
