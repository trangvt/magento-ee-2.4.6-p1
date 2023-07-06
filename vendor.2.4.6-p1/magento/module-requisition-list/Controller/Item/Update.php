<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\RequisitionList\Controller\Item;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\RequisitionList\Items;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Class Update
 */
class Update extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var Items
     */
    private $requisitionListItemRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @param Context $context
     * @param RequestValidator $requestValidator
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param Items $requisitionListItemRepository
     * @param LoggerInterface $logger
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        Context $context,
        RequestValidator $requestValidator,
        RequisitionListRepositoryInterface $requisitionListRepository,
        Items $requisitionListItemRepository,
        LoggerInterface $logger,
        StockRegistryInterface $stockRegistry
    ) {
        parent::__construct($context);
        $this->requestValidator = $requestValidator;
        $this->requisitionListRepository = $requisitionListRepository;
        $this->requisitionListItemRepository = $requisitionListItemRepository;
        $this->logger = $logger;
        $this->stockRegistry = $stockRegistry;
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

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setRefererUrl();

        try {
            $id = $this->_request->getParam('requisition_id');
            $requisitionList = $this->requisitionListRepository->get($id);

            $update = $this->_request->getParam('qty');
            $ids = array_keys($update);

            foreach ($requisitionList->getItems() as $item) {
                if (in_array($item->getId(), $ids)) {
                    $qty = (float)$update[$item->getId()];
                    if (!$this->isDecimalQtyUsed($item)) {
                        $qty = (int)$qty;
                    }
                    $item->setQty($qty);
                    $this->requisitionListItemRepository->save($item);
                }
            }

            $this->requisitionListRepository->save($requisitionList);

            $resultRedirect->setPath(
                'requisition_list/requisition/view',
                ['requisition_id' => $requisitionList->getId()]
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
     * Is stock item qty uses decimal
     *
     * @param RequisitionListItemInterface $item
     * @return bool
     */
    private function isDecimalQtyUsed(RequisitionListItemInterface $item): bool
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($item->getSku());

        return $stockItem->getIsQtyDecimal();
    }
}
