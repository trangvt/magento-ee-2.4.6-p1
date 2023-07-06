<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver\OperationResult;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class CloseNegotiableQuoteErrorTypeResolver implements TypeResolverInterface
{
    /**
     * Array of recognized errors for closeNegotiableQuote operations.
     *
     * @var string[]
     */
    private $validErrorTypes;

    /**
     * @param string[] $validErrorTypes
     */
    public function __construct(array $validErrorTypes)
    {
        $this->validErrorTypes = $validErrorTypes;
    }

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        if (isset($data['error_type']) && in_array($data['error_type'], $this->validErrorTypes)) {
            $errorType = $data['error_type'];
        } else {
            $errorType = 'InternalError';
        }

        return $errorType;
    }
}
