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
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\Customer;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Resolver for the buyer of a negotiable quote
 */
class Buyer implements ResolverInterface
{
    /**
     * @var Customer
     */
    private $customer;

    /**
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Get data about the negotiable quote buyer
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
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value must be specified.'));
        }

        /** @var CartInterface $quote */
        $quote = $value['model'];
        $customerId = (int) $quote->getCustomer()->getId();
        $customer = $this->customer->getCustomer($customerId);
        $buyerName = [];

        if ($customer) {
            $buyerName = [
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname()
            ];
        }

        return $buyerName;
    }
}
