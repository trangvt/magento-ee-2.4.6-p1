<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

/**
 * Encode and decode id values
 */
class IdEncoder
{
    /**
     * Decode a base64 encoded string into an id.
     *
     * @param string $id
     * @return false|string
     * @phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function decode(string $id)
    {
        return base64_decode($id, true);
    }

    /**
     * Encode an id into a base64 string.
     *
     * @param string $id
     * @return string
     * @phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function encode(string $id): string
    {
        return base64_encode($id);
    }

    /**
     * Decode array of base64 encoded strings
     *
     * @param array $ids
     * @return array
     */
    public function decodeList(array $ids): array
    {
        return \array_map(
            function ($id) {
                return $this->decode((string)$id);
            },
            $ids
        );
    }

    /**
     * Encode array of ID values into base64 strings
     *
     * @param array $ids
     * @return array
     */
    public function encodeList(array $ids): array
    {
        return \array_map(
            function ($id) {
                return $this->encode((string)$id);
            },
            $ids
        );
    }
}
