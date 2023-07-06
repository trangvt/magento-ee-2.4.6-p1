<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Model for setting a shipping address on a negotiable quote
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetNegotiableQuoteShippingAddressForUser
{
    /**
     * @var Address
     */
    private $negotiableQuoteAddress;

    /**
     * @var CustomerAddress
     */
    private $shippingAddress;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var QuoteIdMask
     */
    private $quoteIdMaskResource;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @param Address $negotiableQuoteAddress
     * @param CustomerAddress $shippingAddress
     * @param Customer $customer
     * @param Quote $quote
     * @param QuoteIdMask $quoteIdMaskResource
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        Address $negotiableQuoteAddress,
        CustomerAddress $shippingAddress,
        Customer $customer,
        Quote $quote,
        QuoteIdMask $quoteIdMaskResource,
        IdEncoder $idEncoder
    ) {
        $this->negotiableQuoteAddress = $negotiableQuoteAddress;
        $this->shippingAddress = $shippingAddress;
        $this->customer = $customer;
        $this->quote = $quote;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Set shipping address on a negotiable quote
     *
     * @param string $maskedId
     * @param array $shippingAddresses
     * @param int $customerId
     * @param WebsiteInterface $website
     * @return NegotiableQuoteInterface
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(
        string $maskedId,
        array $shippingAddresses,
        int $customerId,
        WebsiteInterface $website
    ): NegotiableQuoteInterface {
        $this->customer->validateCanManage($customerId);

        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedId);
        $quote = $this->quote->getOwnedQuote($quoteId, $website);
        $this->quote->validateNegotiable([$quote]);
        $this->quote->validateCanSubmit([$quote]);

        $this->validateShippingAddressInput($shippingAddresses);
        $shippingAddress = current($shippingAddresses) ?? [];

        if (isset($shippingAddress['customer_address_uid'])) {
            $customerAddressId = (int) $this->idEncoder->decode($shippingAddress['customer_address_uid']);
            $shippingAddress = $this->shippingAddress->getOwnedAddress($customerId, $customerAddressId);
        } elseif (isset($shippingAddress['address'])) {
            $addressInputData = $shippingAddress['address'];

            if (!isset($addressInputData['save_in_address_book'])) {
                $addressInputData['save_in_address_book'] = true;
            }
            $shippingAddress = $this->shippingAddress->createNewAddress($addressInputData);
        }

        try {
            $this->negotiableQuoteAddress->updateQuoteShippingAddress($quoteId, $shippingAddress);
        } catch (Exception $exception) {
            throw new LocalizedException(__("Unable to set the shipping address on the specified negotiable quote."));
        }

        return $quote->getExtensionAttributes()->getNegotiableQuote();
    }

    /**
     * Validate the shipping address input.
     *
     * @param array $shippingAddressInput
     * @throws GraphQlInputException
     */
    public function validateShippingAddressInput(array $shippingAddressInput): void
    {
        if (count($shippingAddressInput) > 1) {
            throw new GraphQlInputException(
                __('You cannot specify multiple shipping addresses.')
            );
        }

        $shippingAddress = current($shippingAddressInput) ?? [];
        $customerAddressUid = $shippingAddress['customer_address_uid'] ?? null;
        $addressInput = $shippingAddress['address'] ?? null;

        if ($addressInput) {
            $addressInput['customer_notes'] = $shippingAddressInput['customer_notes'] ?? '';
        }

        if (null === $customerAddressUid && null === $addressInput) {
            throw new GraphQlInputException(
                __('The shipping address must contain either "customer_address_uid" or "address".')
            );
        }

        if ($customerAddressUid && $addressInput) {
            throw new GraphQlInputException(
                __('The shipping address cannot contain "customer_address_uid" and "address" at the same time.')
            );
        }
    }
}
