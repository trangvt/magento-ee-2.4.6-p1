<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;
use Magento\Quote\Model\Quote\Address;

/**
 * @inheritdoc
 */
class NegotiableQuoteAddressTypeResolver implements TypeResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        if (!isset($data['model'])) {
            throw new GraphQlInputException(__('Missing key "model" in negotiable quote address data'));
        }
        /** @var Address $address */
        $address = $data['model'];

        if ($address->getAddressType() == AbstractAddress::TYPE_SHIPPING) {
            $addressType = 'NegotiableQuoteShippingAddress';
        } elseif ($address->getAddressType() == AbstractAddress::TYPE_BILLING) {
            $addressType = 'NegotiableQuoteBillingAddress';
        } else {
            throw new GraphQlInputException(__('Unsupported negotiable quote address type'));
        }
        return $addressType;
    }
}
