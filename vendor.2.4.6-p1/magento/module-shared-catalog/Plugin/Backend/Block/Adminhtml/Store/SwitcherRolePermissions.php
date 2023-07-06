<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Plugin\Backend\Block\Adminhtml\Store;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\SharedCatalog\Block\Adminhtml\Store\Switcher;

/**
 * Plugin for store switch permission based on role
 */
class SwitcherRolePermissions
{
    /**
     * @var Session
     */
    private $backendAuthSession;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param Session $backendAuthSession
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        Session $backendAuthSession,
        ArrayManager $arrayManager
    ) {
        $this->backendAuthSession = $backendAuthSession;
        $this->arrayManager = $arrayManager;
    }

    /**
     * Remove 'All Stores' for website restricted users
     *
     * @param Switcher $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetStoreOptionsAsArray(
        Switcher $subject,
        array $result
    ):array {
        $role = $this->backendAuthSession->getUser()->getRole();
        if (!$role->getGwsIsAll() && $this->arrayManager->exists(Switcher::ALL_STORES_ID, $result)) {
            array_shift($result);
        }
        return $result;
    }
}
