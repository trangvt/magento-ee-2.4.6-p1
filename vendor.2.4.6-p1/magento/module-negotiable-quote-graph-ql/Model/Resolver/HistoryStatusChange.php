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

/**
 * Resolver for a negotiable quote history change related to the status
 */
class HistoryStatusChange implements ResolverInterface
{
    /**
     * Get the negotiable quote history status changes
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (!isset($value['changes'])) {
            throw new LocalizedException(__('"changes" value must be specified.'));
        }

        $statusChanges = [];
        foreach ($value['changes'] as $statusLine) {
            $oldStatusLabel = $statusLine['old_status'] ??  null;
            $newStatusLabel = $statusLine['new_status'] ??  null;

            $statusChanges[] = [
                'old_status' => $oldStatusLabel,
                'new_status' => $newStatusLabel
            ];
        }
        return $statusChanges;
    }
}
