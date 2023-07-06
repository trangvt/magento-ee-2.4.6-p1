<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Order\Address;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order\Address;

/**
 * Class Service.
 *
 * Service for order address processing.
 */
class Service
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Service constructor.
     *
     * @param EncryptorInterface $encryptor
     */
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * Get unique addresses by hash
     *
     * @param array $addresses
     *
     * @return array
     */
    public function getUniqueAddresses(array $addresses): array
    {
        $result = [];

        /** @var Address $address */
        foreach ($addresses as $address) {
            $addressHash = $this->hashAddress($address);

            if (!isset($result[$addressHash])) {
                $result[$addressHash] =
                    [
                        'label' => $this->aggregateAddress($address),
                        'entity_id' => $address->getEntityId(),
                    ];
            }
        }

        return $result;
    }

    /**
     * Hash address function
     *
     * @param Address $address
     *
     * @return string
     */
    public function hashAddress(Address $address): string
    {
        $rawChain = sprintf(
            '%s%s%s%s%s%s%s',
            $address->getCompany(),
            $address->getFirstname(),
            $address->getLastname(),
            implode('', $address->getStreet()),
            $address->getPostcode(),
            $address->getCity(),
            $address->getCountryId()
        );

        $preparedChain = trim(preg_replace('/[^a-zA-Z0-9\']/', '', $rawChain));

        return $this->encryptor->hash($preparedChain);
    }

    /**
     * Aggregate Address object
     *
     * @param Address $address
     *
     * @return string
     */
    public function aggregateAddress(Address $address): string
    {
        return sprintf(
            '%s %s / %s / %s %s %s',
            $address->getFirstname(),
            $address->getLastname(),
            implode(' ', $address->getStreet()),
            $address->getPostcode(),
            $address->getCity(),
            $address->getCountryId()
        );
    }
}
