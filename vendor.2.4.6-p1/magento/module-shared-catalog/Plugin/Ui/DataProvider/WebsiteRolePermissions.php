<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Plugin\Ui\DataProvider;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\SharedCatalog\Ui\DataProvider\Website as Websites;

/**
 * Plugin for store switch permission based on role
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class WebsiteRolePermissions
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var Session
     */
    private $backendAuthSession;

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
     * @param Websites $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetWebsites(
        Websites $subject,
        array    $result
    ):array {
        $role = $this->backendAuthSession->getUser()->getRole();
        if (!$role->getGwsIsAll() && $this->arrayManager->exists(0, $result)) {
            array_shift($result);
        }
        return $result;
    }
}
