<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\RequisitionList\Controller\Item;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\Catalog\Controller\Product\View\ViewInterface;

/**
 * Configure requisition list item.
 */
class Configure extends Action implements ViewInterface, HttpPostActionInterface, HttpGetActionInterface
{
    /**
     * @var \Magento\RequisitionList\Model\Action\RequestValidator
     */
    private $requestValidator;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionList\Items
     */
    private $requisitionListItemRepository;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \Magento\RequisitionList\Model\OptionsManagement
     */
    private $optionsManagement;

    /**
     * @var \Magento\Catalog\Helper\Product\View
     */
    private $productViewHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param RequestValidator $requestValidator
     * @param \Magento\RequisitionList\Model\RequisitionList\Items $requisitionListItemRepository
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\RequisitionList\Model\OptionsManagement $optionsManagement
     * @param \Magento\Catalog\Helper\Product\View $productViewHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        RequestValidator $requestValidator,
        \Magento\RequisitionList\Model\RequisitionList\Items $requisitionListItemRepository,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\RequisitionList\Model\OptionsManagement $optionsManagement,
        \Magento\Catalog\Helper\Product\View $productViewHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->requestValidator = $requestValidator;
        $this->requisitionListItemRepository = $requisitionListItemRepository;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->optionsManagement = $optionsManagement;
        $this->productViewHelper = $productViewHelper;
        $this->logger = $logger;
    }

    /**
     * Configure requisition list item.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \InvalidArgumentException
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('item_id');
        $productId = (int)$this->getRequest()->getParam('id');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $item = $this->requisitionListItemRepository->get($id);
            $params = $this->dataObjectFactory->create();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            $buyRequest = $this->optionsManagement->getInfoBuyRequest($item);
            $buyRequest = $this->dataObjectFactory->create()->setData($buyRequest);
            $buyRequest->setQty($item->getQty());
            $params->setBuyRequest($buyRequest);
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $this->productViewHelper->prepareAndRender(
                $resultPage,
                $productId,
                $this,
                $params
            );
            return $resultPage;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $resultRedirect->setPath('*');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t configure the product right now.'));
            $this->logger->critical($e);
            $resultRedirect->setPath('*');
            return $resultRedirect;
        }
    }
}
