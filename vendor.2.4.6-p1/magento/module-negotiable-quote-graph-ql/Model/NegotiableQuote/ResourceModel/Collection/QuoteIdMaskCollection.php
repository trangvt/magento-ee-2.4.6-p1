<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\Collection;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\Collection;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Magento\Quote\Model\QuoteIdMask;

class QuoteIdMaskCollection extends Collection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(QuoteIdMask::class, QuoteIdMaskResource::class);
    }
}
