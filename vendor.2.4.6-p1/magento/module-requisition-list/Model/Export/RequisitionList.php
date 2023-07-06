<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\RequisitionList\Model\Export;

use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\RequisitionList\Model\ResourceModel\RequisitionList\Item\Collection;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\ImportExport\Model\Export\Adapter\AbstractAdapter as WriterAdapter;

/**
 * Export requisition list entity model
 */
class RequisitionList extends AbstractEntity
{
    const ATTRIBUTE_COLLECTION_NAME = Collection::class;

    /**
     * @inheritDoc
     */
    protected $_permanentAttributes = [RequisitionListItemInterface::SKU, RequisitionListItemInterface::QTY];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ExportFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param WriterAdapter $writer
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        WriterAdapter $writer,
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $data
        );

        $this->setWriter($writer);
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        $writer = $this->getWriter();

        // create export file
        $writer->setHeaderCols($this->_getHeaderColumns());
        $this->_exportCollectionByPages($this->_getEntityCollection());

        return $writer->getContents();
    }

    /**
     * @inheritDoc
     */
    public function exportItem($item)
    {
        $this->getWriter()->writeRow($item->toArray($this->_permanentAttributes));
    }

    /**
     * @inheritDoc
     */
    public function getEntityTypeCode()
    {
        return $this->getAttributeCollection()->getNewEmptyItem()->getEntityTypeCode();
    }

    /**
     * @inheritDoc
     */
    protected function _getHeaderColumns()
    {
        return $this->_permanentAttributes;
    }

    /**
     * @inheritDoc
     */
    protected function _getEntityCollection()
    {
        return $this->_attributeCollection;
    }
}
