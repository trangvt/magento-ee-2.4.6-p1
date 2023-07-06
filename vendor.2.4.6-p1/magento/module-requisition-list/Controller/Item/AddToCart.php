<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Controller\Item;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionList\ItemSelector;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Add specified items from requisition list to cart.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddToCart extends Action implements HttpPostActionInterface
{
    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequisitionListManagementInterface
     */
    private $listManagement;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ItemSelector
     */
    private $itemSelector;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @param Context $context
     * @param RequestValidator $requestValidator
     * @param UserContextInterface $userContext
     * @param LoggerInterface $logger
     * @param RequisitionListManagementInterface $listManagement
     * @param CartManagementInterface $cartManagement
     * @param StoreManagerInterface $storeManager
     * @param ItemSelector $itemSelector
     * @param CookieManagerInterface|null $cookieManager
     * @param CookieMetadataFactory|null $cookieMetadataFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        RequestValidator $requestValidator,
        UserContextInterface $userContext,
        LoggerInterface $logger,
        RequisitionListManagementInterface $listManagement,
        CartManagementInterface $cartManagement,
        StoreManagerInterface $storeManager,
        ItemSelector $itemSelector,
        ?CookieManagerInterface $cookieManager = null,
        ?CookieMetadataFactory $cookieMetadataFactory = null
    ) {
        parent::__construct($context);
        $this->requestValidator = $requestValidator;
        $this->userContext = $userContext;
        $this->logger = $logger;
        $this->listManagement = $listManagement;
        $this->cartManagement = $cartManagement;
        $this->storeManager = $storeManager;
        $this->itemSelector = $itemSelector;
        $this->cookieManager = $cookieManager ?: $this->_objectManager->get(CookieManagerInterface::class);
        $this->cookieMetadataFactory = $cookieMetadataFactory ?:
            $this->_objectManager->get(CookieMetadataFactory::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->requestValidator->getResult($this->getRequest());
        if ($result) {
            return $result;
        }

        try {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        } catch (\InvalidArgumentException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect(
                'requisition_list/requisition/view',
                ['requisition_id' => $this->getRequest()->getParam('requisition_id')]
            );
        }
        $resultRedirect->setRefererUrl();

        $isReplace = $this->getRequest()->getParam('is_replace', false);

        try {
            $cartId = $this->cartManagement->createEmptyCartForCustomer($this->userContext->getUserId());
            $listId = $this->getRequest()->getParam('requisition_id');
            $itemIds = explode(',', $this->_request->getParam('selected'));
            $items = $this->itemSelector->selectItemsFromRequisitionList(
                $listId,
                $itemIds,
                $this->storeManager->getWebsite()->getId()
            );
            $addedItems = $this->listManagement->placeItemsInCart($cartId, $items, $isReplace);

            $this->setCartCookieByItems($addedItems);

            $this->messageManager->addSuccess(
                __('You added %1 item(s) to your shopping cart.', count($addedItems))
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Something went wrong.'));
            $this->logger->critical($e);
        }

        return $resultRedirect;
    }

    /**
     * Set cookie for add to cart
     *
     * @param array $items
     * @return void
     */
    private function setCartCookieByItems(array $items): void
    {
        $productsToAdd = [];
        $cart = $this->cartManagement->getCartForCustomer($this->userContext->getUserId());

        foreach ($items as $item) {
            $itemOptions = $item->getOptions();

            if (!isset($itemOptions['info_buyRequest'])) {
                continue;
            }

            $info = $itemOptions['info_buyRequest'];
            $infoProduct = $info['product'] ?? null;
            $productIdFromList = $info['selected_configurable_option'] ?? $infoProduct;

            if (!$productIdFromList) {
                continue;
            }

            foreach ($cart->getItems() as $cartItem) {
                $productIdInCart = !empty($cartItem->getQtyOptions()) ?
                    array_keys($cartItem->getQtyOptions())[0] : $cartItem->getProductId();

                if ((int)$productIdFromList === (int)$productIdInCart) {
                    $productsToAdd[] = [
                        'sku' => $cartItem->getSku(),
                        'name' => $cartItem->getName(),
                        'price' => $cartItem->getPrice(),
                        'qty' => $item->getQty(),
                    ];
                }
            }
        }

        if (empty($productsToAdd)) {
            return;
        }

        /** @var PublicCookieMetadata $publicCookieMetadata */
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(false)
            ->setSameSite('Strict');

        $this->cookieManager->setPublicCookie(
            'add_to_cart',
            \rawurlencode(\json_encode($productsToAdd)),
            $publicCookieMetadata
        );
    }
}
