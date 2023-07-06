<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\NegotiableQuote;

/**
 * Plugin to make sure the isNegotiableQuoteOperation flag is reset before any top resolver executes
 */
class ResetNegotiableQuoteOperationFlagPlugin
{
    /**
     * @var NegotiableQuote
     */
    private $negotiableQuoteHelper;

    /**
     * @param NegotiableQuote $negotiableQuoteHelper
     */
    public function __construct(NegotiableQuote $negotiableQuoteHelper)
    {
        $this->negotiableQuoteHelper = $negotiableQuoteHelper;
    }

    /**
     * If this resolver is at the top level, clear the negotiable quote operation flag so it doesn't persist
     *
     * @param ResolverInterface $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeResolve(
        ResolverInterface $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): void {
        if ($info->isTopResolver()) {
            $this->negotiableQuoteHelper->setIsNegotiableQuoteOperation(false);
        }
    }
}
