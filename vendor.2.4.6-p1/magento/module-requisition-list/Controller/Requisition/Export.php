<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Controller\Requisition;

use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\RequisitionList\Model\Export\RequisitionList as RequisitionListExport;
use Magento\RequisitionList\Model\RequisitionList\ItemSelector as RequisitionListItemSelector;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;

/**
 * Export RequisitionList Controller
 */
class Export extends Action implements HttpGetActionInterface
{
    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var RequisitionListExport
     */
    private $requisitionListExport;

    /**
     * @var RequisitionListItemSelector
     */
    private $requisitionListItemSelector;

    /**
     * @param Context $context
     * @param RequestValidator $requestValidator
     * @param FileFactory $fileFactory
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param RequisitionListItemSelector $requisitionListItemSelector
     * @param RequisitionListExport $requisitionListExport
     */
    public function __construct(
        Context $context,
        RequestValidator $requestValidator,
        FileFactory $fileFactory,
        RequisitionListRepositoryInterface $requisitionListRepository,
        RequisitionListItemSelector $requisitionListItemSelector,
        RequisitionListExport $requisitionListExport
    ) {
        parent::__construct($context);
        $this->requestValidator = $requestValidator;
        $this->fileFactory = $fileFactory;
        $this->requisitionListRepository = $requisitionListRepository;
        $this->requisitionListItemSelector = $requisitionListItemSelector;
        $this->requisitionListExport = $requisitionListExport;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redirectResult = $this->requestValidator->getResult($this->getRequest());

        if ($redirectResult) {
            return $redirectResult;
        }

        $id = $this->_request->getParam('requisition_id');

        try {
            $requisitionList = $this->requisitionListRepository->get($id);
        } catch (NoSuchEntityException $e) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $this->requisitionListExport->getAttributeCollection()->addFieldToFilter(
            RequisitionListItemInterface::REQUISITION_LIST_ID,
            $requisitionList->getId()
        );

        $writer = $this->requisitionListExport->getWriter();

        $fileName = "{$requisitionList->getName()}.{$writer->getFileExtension()}";

        return $this->fileFactory->create(
            $fileName,
            $this->requisitionListExport->export(),
            DirectoryList::VAR_DIR,
            $writer->getContentType()
        );
    }
}
