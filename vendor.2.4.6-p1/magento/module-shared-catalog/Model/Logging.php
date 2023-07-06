<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\Processor;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;

/**
 * Logging actions handler for Shared Catalog module
 */
class Logging
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;

    /**
     * @param RequestInterface $request
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     */
    public function __construct(RequestInterface $request, SharedCatalogRepositoryInterface $sharedCatalogRepository)
    {
        $this->request = $request;
        $this->sharedCatalogRepository = $sharedCatalogRepository;
    }

    /**
     * Custom handler for shared catalog companies
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processor
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function postDispatchSharedCatalogCompanies(array $config, Event $eventModel, Processor $processor)
    {
        $sharedCatalogId = $this->request->getParam(SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM);
        if (!$sharedCatalogId) {
            $collectedIds = $processor->getCollectedIds();
            $sharedCatalogId = array_shift($collectedIds);
            if (!$sharedCatalogId) {
                return false;
            }
        }
        try {
            $sharedCatalog = $this->sharedCatalogRepository->get($sharedCatalogId);
            $eventModel->setInfo(sprintf('Id: %s (%s)', $sharedCatalogId, $sharedCatalog->getName()));
        } catch (LocalizedException $e) {
            $eventModel->setInfo(sprintf('Id: %s', $sharedCatalogId));
        }

        return true;
    }
}
