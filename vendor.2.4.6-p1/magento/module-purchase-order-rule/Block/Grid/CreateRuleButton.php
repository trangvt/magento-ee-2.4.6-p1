<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Block\Grid;

use Magento\Framework\View\Element\Template;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Framework\View\Element\Template\Context as TemplateContext;

/**
 * Add approval rule button.
 *
 * @deprecated in favor of UI component uiPurchaseOrderAddNewRuleButton
 * @api
 * @since 100.2.0
 */
class CreateRuleButton extends Template
{
    /**
     * Required resource for action authorization.
     */
    const COMPANY_RESOURCE = 'Magento_PurchaseOrderRule::manage_approval_rules';

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param TemplateContext $context
     * @param AuthorizationInterface $authorization
     * @param array $data [optional]
     */
    public function __construct(
        TemplateContext $context,
        AuthorizationInterface $authorization,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->authorization = $authorization;
    }

    /**
     * Get href to create rule.
     *
     * @return string
     * @since 100.2.0
     */
    public function getCreateRuleUrl()
    {
        return $this->getUrl('purchaseorderrule/create');
    }

    /**
     * Checks if is allowed to edit approval rules.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isCreateRuleAllowed()
    {
        return $this->authorization->isAllowed(self::COMPANY_RESOURCE);
    }
}
