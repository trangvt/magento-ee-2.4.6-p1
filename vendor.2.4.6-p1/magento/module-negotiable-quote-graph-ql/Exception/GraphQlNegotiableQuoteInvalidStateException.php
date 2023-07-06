<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Exception;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class GraphQlNegotiableQuoteInvalidStateException extends GraphQlInputException
{
    public const EXCEPTION_CATEGORY = 'graphql-negotiable-quote-invalid-state';
}
