<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

/**
 * Negotiable quote status model
 */
class Status
{
    /**
     * Map negotiable quote statuses to the respective labels
     *
     * @return array
     */
    public function getStatusLabels(): array
    {
        return [
            NegotiableQuoteInterface::STATUS_CREATED => 'SUBMITTED',
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => 'OPEN',
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => 'PENDING',
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => 'SUBMITTED',
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => 'UPDATED',
            NegotiableQuoteInterface::STATUS_ORDERED => 'ORDERED',
            NegotiableQuoteInterface::STATUS_EXPIRED => 'EXPIRED',
            NegotiableQuoteInterface::STATUS_DECLINED => 'DECLINED',
            NegotiableQuoteInterface::STATUS_CLOSED => 'CLOSED',
        ];
    }
}
