<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RequisitionList\Controller\Item;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionListItem\Locator;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder\ConfigurationException;
use Magento\RequisitionList\Model\RequisitionListItem\SaveHandler;
use Magento\RequisitionList\Model\RequisitionListProduct;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Add product to the requisition list specified in the request.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends Action implements HttpPostActionInterface
{
    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * @var SaveHandler
     */
    private $requisitionListItemSaveHandler;

    /**
     * @var RequisitionListProduct
     */
    private $requisitionListProduct;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductInterface
     */
    private $product;

    /**
     * @var Locator
     */
    private $requisitionListItemLocator;

    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Redirect
     */
    private $resultRedirect;

    /**
     * @param Context $context
     * @param RequestValidator $requestValidator
     * @param SaveHandler $requisitionListItemSaveHandler
     * @param RequisitionListProduct $requisitionListProduct
     * @param LoggerInterface $logger
     * @param Locator $requisitionListItemLocator
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Context $context,
        RequestValidator $requestValidator,
        SaveHandler $requisitionListItemSaveHandler,
        RequisitionListProduct $requisitionListProduct,
        LoggerInterface $logger,
        Locator $requisitionListItemLocator,
        RequisitionListRepositoryInterface $requisitionListRepository = null,
        UrlInterface $urlBuilder = null
    ) {
        parent::__construct($context);
        $this->requestValidator = $requestValidator;
        $this->requisitionListItemSaveHandler = $requisitionListItemSaveHandler;
        $this->requisitionListProduct = $requisitionListProduct;
        $this->logger = $logger;
        $this->requisitionListItemLocator = $requisitionListItemLocator;
        $this->requisitionListRepository = $requisitionListRepository ??
            ObjectManager::getInstance()->get(RequisitionListRepositoryInterface::class);
        $this->urlBuilder = $urlBuilder ?? ObjectManager::getInstance()->get(UrlInterface::class);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $this->resultRedirect = $resultRedirect = $this->resultFactory->create(
            ResultFactory::TYPE_REDIRECT
        );

        if (!$this->isRequestValid()) {
            return $this->resultRedirect;
        }

        $itemId = (int) $this->getRequest()->getParam('item_id');

        $isNewRequisitionItem = !$itemId;

        $listId = $this->getRequest()->getParam('list_id') ?: $this->getRequisitionListIdByItemId($itemId);

        try {
            $requisitionList = $this->requisitionListRepository->get($listId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__(
                'We couldn\'t find the requested requisition list.'
            ));
            $resultRedirect->setPath('requisition_list/requisition/index');
            return $resultRedirect;
        }

        try {
            $preparedProductData = $this->getPreparedProductDataFromRequest();
            $options = $preparedProductData->getOptions() ?? [];

            $headerReferer = (string) $this->getRequest()->getHeader('referer');
            if (strpos($headerReferer, 'wishlist') !== false) {
                $options['from_wishlist'] = true;
            }

            $message = $this->requisitionListItemSaveHandler->saveItem(
                $preparedProductData,
                $options,
                $itemId,
                $listId
            );

            $this->addSuccessMessageBasedOnReferrer($requisitionList, $message);
        } catch (ConfigurationException $e) {
            $this->messageManager->addWarningMessage($e->getMessage());
            $resultRedirect->setUrl($this->getProductConfigureUrl());

            return $resultRedirect;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            if (!$isNewRequisitionItem) {
                $this->messageManager->addErrorMessage(__('We can\'t update your requisition list right now.'));
            } else {
                $this->messageManager->addErrorMessage(
                    __('We can\'t add the item to the Requisition List right now: %1.', $e->getMessage())
                );
            }
            $this->logger->critical($e);
        }

        if ($isNewRequisitionItem) {
            $resultRedirect->setRefererUrl();
            return $resultRedirect;
        }

        return $resultRedirect->setPath(
            'requisition_list/requisition/view',
            ['requisition_id' => $listId]
        );
    }

    /**
     * Add success message to message manager based on request parameters hinting at referrer page
     *
     * @param RequisitionListInterface $requisitionList
     * @param Phrase $messageFallback
     */
    private function addSuccessMessageBasedOnReferrer(
        RequisitionListInterface $requisitionList,
        $messageFallback
    ) {
        if ($this->getRequest()->getParam('isFromCartPage')) {
            $requisitionListUrl = $this->urlBuilder->getUrl(
                'requisition_list/requisition/view',
                ['requisition_id' => $requisitionList->getId()]
            );
            $this->messageManager->addComplexSuccessMessage(
                'addCartItemToRequisitionListSuccessMessage',
                [
                    'product_name' => $this->getProduct()->getName(),
                    'requisition_list_url' => $requisitionListUrl,
                    'requisition_list_name' => $requisitionList->getName(),
                ]
            );
        } else {
            $this->messageManager->addSuccessMessage($messageFallback);
        }
    }

    /**
     * Check is add to requisition list action allowed for the current user and product exists.
     *
     * @return bool
     */
    private function isRequestValid()
    {
        $resultRedirect = $this->requestValidator->getResult($this->getRequest());
        $isValid = !$resultRedirect;

        if (!$isValid) {
            $this->resultRedirect = $resultRedirect;
            return false;
        }

        if (!$this->getProduct()) {
            $this->messageManager->addErrorMessage(__(
                'We couldn\'t find the product you requested to add to your requisition list.'
            ));
            $this->resultRedirect->setPath('requisition_list/requisition/index');
            return false;
        }

        return true;
    }

    /**
     * Get prepared product data provided in the request.
     *
     * @return DataObject
     */
    private function getPreparedProductDataFromRequest()
    {
        return $this->requisitionListProduct->prepareProductData(
            $this->getRequest()->getParam('product_data')
        );
    }

    /**
     * Get product specified by serialized product data provided in the request.
     *
     * @return ProductInterface|bool
     */
    private function getProduct()
    {
        if ($this->product === null) {
            $this->product = $this->requisitionListProduct->getProduct(
                $this->getPreparedProductDataFromRequest()->getSku()
            );
        }
        return $this->product;
    }

    /**
     * Prepare product configure url.
     *
     * @return string
     */
    private function getProductConfigureUrl()
    {
        return $this->getProduct()->getUrlModel()->getUrl(
            $this->getProduct(),
            ['_fragment' => 'requisition_configure']
        );
    }

    /**
     * Get requisition list id by item id.
     *
     * @param int $itemId
     * @return int
     */
    private function getRequisitionListIdByItemId($itemId)
    {
        $item = $this->requisitionListItemLocator->getItem($itemId);

        return $item->getRequisitionListId();
    }
}
