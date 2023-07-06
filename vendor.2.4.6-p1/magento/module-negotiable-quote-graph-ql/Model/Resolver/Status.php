<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\Status as StatusList;

/**
 * Resolver for the negotiable quote status
 */
class Status implements ResolverInterface
{
    /**
     * @var StatusList
     */
    private $statusList;

    /**
     * @param StatusList $statusList
     */
    public function __construct(StatusList $statusList)
    {
        $this->statusList = $statusList;
    }

    /**
     * Get negotiable quote statuses
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return string
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): string {
        if (!isset($value['status'])) {
            throw new LocalizedException(__('"status" value must be specified.'));
        }
        $status = $value['status'];
        $statusLabel = $this->statusList->getStatusLabels();
        return $statusLabel[$status];
    }
}
