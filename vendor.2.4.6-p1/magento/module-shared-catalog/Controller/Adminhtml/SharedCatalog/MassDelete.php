<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

/**
 * Class MassDelete
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassDelete extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Shared Catalog Repository Interface
     * @var SharedCatalogRepositoryInterface
     */
    protected $sharedCatalogRepository;

    /**
     * @var GroupExcludedWebsiteRepositoryInterface
     */
    private $groupExcludedWebsite;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param GroupExcludedWebsiteRepositoryInterface|null $groupExcludedWebsite
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        SharedCatalogRepositoryInterface $sharedCatalogRepository,
        GroupExcludedWebsiteRepositoryInterface $groupExcludedWebsite = null
    ) {
        parent::__construct($context, $filter, $collectionFactory, $logger);
        $this->sharedCatalogRepository = $sharedCatalogRepository;
        $this->groupExcludedWebsite = $groupExcludedWebsite ?:
            ObjectManager::getInstance()->get(GroupExcludedWebsiteRepositoryInterface::class);
    }

    /**
     * Mass action
     * @param AbstractCollection $collection
     * @return Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $sharedCatalogsDeleted = 0;
        /** @var SharedCatalog $sharedCatalog */
        foreach ($collection as $sharedCatalog) {
            try {
                $this->groupExcludedWebsite->delete($sharedCatalog->getCustomerGroupId());
                $this->sharedCatalogRepository->delete($sharedCatalog);
                $sharedCatalogsDeleted++;
            } catch (StateException $e) {
                $this->logger->critical($e);
                $this->messageManager->addError($e->getMessage());
            }
        }
        if ($sharedCatalogsDeleted) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) were deleted.', $sharedCatalogsDeleted));
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
    }
}
