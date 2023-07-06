<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Configure;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\Data\GroupExtensionInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\SharedCatalog\Api\PriceManagementInterface;
use Magento\SharedCatalog\Model\Configure\Category;
use Magento\SharedCatalog\Model\Form\Storage\DiffProcessor;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Save shared catalog structure and pricing.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * @var Category
     */
    private $configureCategory;

    /**
     * @var WizardFactory
     */
    private $wizardStorageFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScheduleBulk
     */
    private $scheduleBulk;

    /**
     * @var PriceManagementInterface
     */
    private $priceSharedCatalogManagement;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var DiffProcessor
     */
    private $diffProcessor;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var GroupExtensionInterfaceFactory
     */
    private $groupExtensionInterfaceFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param Category $configureCategory
     * @param WizardFactory $wizardStorageFactory
     * @param LoggerInterface $logger
     * @param ScheduleBulk $scheduleBulk
     * @param PriceManagementInterface $priceSharedCatalogManagement
     * @param UserContextInterface $userContextInterface
     * @param DiffProcessor $diffProcessor
     * @param GroupRepositoryInterface|null $groupRepository
     * @param GroupExtensionInterfaceFactory|null $groupExtensionInterfaceFactory
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        Context $context,
        Category $configureCategory,
        WizardFactory $wizardStorageFactory,
        LoggerInterface $logger,
        ScheduleBulk $scheduleBulk,
        PriceManagementInterface $priceSharedCatalogManagement,
        UserContextInterface $userContextInterface,
        DiffProcessor $diffProcessor,
        GroupRepositoryInterface $groupRepository = null,
        GroupExtensionInterfaceFactory $groupExtensionInterfaceFactory = null,
        StoreManagerInterface $storeManager = null
    ) {
        parent::__construct($context);
        $this->configureCategory = $configureCategory;
        $this->wizardStorageFactory = $wizardStorageFactory;
        $this->logger = $logger;
        $this->scheduleBulk = $scheduleBulk;
        $this->priceSharedCatalogManagement = $priceSharedCatalogManagement;
        $this->userContext = $userContextInterface;
        $this->diffProcessor = $diffProcessor;
        $this->groupRepository = $groupRepository ?: ObjectManager::getInstance()->get(GroupRepositoryInterface::class);
        $this->groupExtensionInterfaceFactory = $groupExtensionInterfaceFactory ?:
            ObjectManager::getInstance()->get(GroupExtensionInterfaceFactory::class);
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Save shared catalog products, categories and tier prices.
     *
     * @return Redirect
     */
    public function execute()
    {
        $sharedCatalogId = $this->getRequest()->getParam('catalog_id');
        $currentStorage = $this->wizardStorageFactory->create([
            'key' => $this->getRequest()->getParam('configure_key')
        ]);

        try {
            $resultDiff = $this->diffProcessor->getDiff($currentStorage, $sharedCatalogId);

            // store_id filter stand for store group id (group_id from store_group)
            $storeId = (int)$this->getRequest()->getParam('store_id');
            $sharedCatalog = $this->configureCategory->saveConfiguredCategories(
                $currentStorage,
                $sharedCatalogId,
                $storeId
            );
            $customerGroupId = $sharedCatalog->getCustomerGroupId();
            $this->excludeWebsites($storeId, $customerGroupId);

            $unassignProductSkus = $currentStorage->getUnassignedProductSkus();
            $this->priceSharedCatalogManagement->deleteProductTierPrices(
                $sharedCatalog,
                $unassignProductSkus
            );
            $prices = $currentStorage->getTierPrices(null, true);
            $prices = array_diff_key($prices, array_flip($unassignProductSkus));
            $this->scheduleBulk->execute($sharedCatalog, $prices, $this->userContext->getUserId());
            if ($resultDiff['pricesChanged'] || $resultDiff['categoriesChanged']) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'The selected items are being processed. You can continue to work in the meantime.'
                    )
                );
            } elseif ($resultDiff['productsChanged']) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'The selected changes have been applied to the shared catalog.'
                    )
                );
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('shared_catalog/sharedCatalog/index');
    }

    /**
     * Exclude websites to shared catalog(customer group) based on chosen store
     *
     * @param int|null $storeId
     * @param int $customerGroupId
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function excludeWebsites(?int $storeId, int $customerGroupId)
    {
        if ($storeId > 0) {
            $allWebsiteIds = [];

            //get all website ids
            foreach ($this->storeManager->getWebsites() as $website) {
                $allWebsiteIds[] = $website->getId();
            }

            //get website id which should be included based on selected store group
            $websiteId = $this->storeManager->getGroup($storeId)->getWebsiteId();

            //exclude websites from customer group
            $excludeWebsiteIds = array_diff($allWebsiteIds, [$websiteId]);
            $customerGroup = $this->groupRepository->getById($customerGroupId);
            $customerGroupExtensionAttributes = $this->groupExtensionInterfaceFactory->create();
            $customerGroupExtensionAttributes->setExcludeWebsiteIds($excludeWebsiteIds);
            $customerGroup->setExtensionAttributes($customerGroupExtensionAttributes);
            $this->groupRepository->save($customerGroup);
        }
    }
}
