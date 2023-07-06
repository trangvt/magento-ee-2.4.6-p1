<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid;

class Encoder
{

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * The constructor basically sets up the Uid
     */
    public function __construct()
    {
        $this->uid = new Uid();
    }

    /**
     * Calls Uid encode
     *
     * @param string $id
     * @return string
     */
    public function encode(string $id): string
    {
        return $this->uid->encode($id);
    }

    /**
     * Calls Uid decode
     *
     * @param string $uid
     * @return string|null
     * @throws GraphQlInputException
     */
    public function decode(string $uid): null|string
    {
        return $this->uid->decode($uid);
    }

    /**
     * Encodes an array with MIME base64
     *
     * @param array $arrayOfIds
     * @return array
     */
    public function encodeArray(array $arrayOfIds): array
    {
        return array_map(function ($value) {
            return $this->uid->encode($value);
        }, $arrayOfIds);
    }

    /**
     * Converts an array to string to use it conveniently
     *
     * @param array $ids
     * @return string
     */
    public function convertToString(array $ids): string
    {
        return '"' . implode('","', $ids) . '"';
    }
}
