<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class to Delete Shared Catalogs from Admin
 */
class Delete extends AbstractAction implements HttpPostActionInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GroupExcludedWebsiteRepositoryInterface
     */
    private $groupExcludedWebsite;

    /**
     * Delete Shared Catalog constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param LoggerInterface $logger
     * @param GroupExcludedWebsiteRepositoryInterface|null $groupExcludedWebsite
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SharedCatalogRepositoryInterface $sharedCatalogRepository,
        LoggerInterface $logger,
        GroupExcludedWebsiteRepositoryInterface $groupExcludedWebsite = null
    ) {
        parent::__construct($context, $resultPageFactory, $sharedCatalogRepository);
        $this->logger = $logger;
        $this->groupExcludedWebsite = $groupExcludedWebsite ?:
            ObjectManager::getInstance()->get(GroupExcludedWebsiteRepositoryInterface::class);
    }

    /**
     * Delete shared catalog
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $sharedCatalog = $this->getSharedCatalog();
            $this->groupExcludedWebsite->delete($sharedCatalog->getCustomerGroupId());
            $this->sharedCatalogRepository->delete($sharedCatalog);
            $this->messageManager->addSuccessMessage(__('The shared catalog was deleted successfully.'));
            $resultRedirect->setPath('shared_catalog/sharedCatalog/index');
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect = $this->getEditRedirect();
        }

        return $resultRedirect;
    }
}
