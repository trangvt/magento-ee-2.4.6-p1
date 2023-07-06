<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Model\Customer\Source;

use Magento\Config\Block\System\Config\Form;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\SharedCatalog\Api\StatusInfoInterface;
use Magento\SharedCatalog\Model\Customer\Source\Collection\GroupFactory;

/**
 * List of customer groups with excluded shared catalog groups.
 */
class LandingGroup implements OptionSourceInterface
{
    /**
     * @var StatusInfoInterface
     */
    private $config;

    /**
     * @var Form
     */
    private $form;

    /**
     * @var GroupFactory
     */
    private $groupCollectionFactory;

    /**
     * @param StatusInfoInterface $config
     * @param Form $form
     * @param GroupFactory $groupCollectionFactory
     */
    public function __construct(
        StatusInfoInterface $config,
        Form $form,
        GroupFactory $groupCollectionFactory
    ) {
        $this->config = $config;
        $this->form = $form;
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $groupCollection = $this->groupCollectionFactory->create();
        $scopeType = $this->form->getScope();
        $scopeCode = $this->form->getScopeCode();
        if ($this->config->isActive($scopeType, $scopeCode)) {
            $groupCollection->joinSharedCatalogTable();
        }
        $groupCollection->load();

        return $groupCollection->toOptionArray();
    }
}
