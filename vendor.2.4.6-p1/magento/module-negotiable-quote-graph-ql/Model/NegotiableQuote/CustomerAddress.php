<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\QuoteGraphQl\Model\Cart\QuoteAddressFactory;

/**
 * Customer Address model with associated validation methods
 */
class CustomerAddress
{
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var QuoteAddressFactory
     */
    private $quoteAddressFactory;

    /**
     * @param AddressRepositoryInterface $addressRepository
     * @param QuoteAddressFactory $quoteAddressFactory
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        QuoteAddressFactory $quoteAddressFactory
    ) {
        $this->addressRepository = $addressRepository;
        $this->quoteAddressFactory = $quoteAddressFactory;
    }

    /**
     * Get address from id and verify it belongs to the current customer
     *
     * @param int $customerId
     * @param int $addressId
     * @return AddressInterface
     *
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function getOwnedAddress(int $customerId, int $addressId): AddressInterface
    {
        $errorMessage = "No address exists with the specified customer address ID.";
        try {
            $address = $this->addressRepository->getById($addressId);
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($errorMessage));
        }
        if ($customerId != $address->getCustomerId()) {
            throw new GraphQlNoSuchEntityException(__($errorMessage));
        }

        return $address;
    }

    /**
     * Create a new customer address based on input data.
     *
     * @param array $addressInputData
     * @return AddressInterface
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function createNewAddress(array $addressInputData): AddressInterface
    {
        $address = $this->quoteAddressFactory->createBasedOnInputData($addressInputData);

        $errors = $address->validate();

        if ($errors !== true) {
            $e = new GraphQlInputException(__('Shipping address errors'));

            foreach ($errors as $error) {
                $e->addError(new GraphQlInputException($error));
            }

            throw $e;
        }

        return $address->getDataModel();
    }
}
