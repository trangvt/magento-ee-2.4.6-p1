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
class DeleteNegotiableQuoteOperationResultTypeResolver implements TypeResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        if (isset($data['errors'])) {
            $resultType = 'DeleteNegotiableQuoteOperationFailure';
        } else {
            $resultType = 'NegotiableQuoteUidOperationSuccess';
        }

        return $resultType;
    }
}
