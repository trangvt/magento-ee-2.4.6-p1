<?php

/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @package     Lazada-Sdk
 * @author      CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CedCommerce (http://cedcommerce.com/)
 * @license     http://cedcommerce.com/license-agreement.txt
 */

namespace Lazada\Sdk;

class Signature
{
    /**
     * Api Key provided by Developer Portal
     * @var string $sellerEmail
     */
    public $apiKey;

    /**
     * User ID provided by Developer Portal
     * @var string $sellerId
     */
    public $userId;

    /**
     * Url Action
     * @var string $authKey
     */
    public $action;

    /**
     * Timestamp of certificate generation
     * @var integer $timestamp
     */
    public $timestamp = 0;


    /**
     * @var string $primaryCategory
     */
    public $primaryCategory;

    public $orderId;

    public $orderList;

    /**
     * Signature constructor.
     */
    public function __construct()
    {
        if (empty($this->timestamp)) {
            $this->timestamp = $this->getTimestamp();
        }
    }

    /**
     * Get current timestamp in milliseconds
     * @return float
     */
    public static function getTimestamp()
    {
        $now = new \DateTime();
        return $now->format(\DateTime::ISO8601);
    }

    /**
     * @TODO: 1. check keys. 2. check values. 3. required checks
     * @param null $userId
     * @param null $apiKey
     * @param null $action
     * @return array|string
     */
    public function getSignature(
        $apiKey = null,
        $params = []
    )
    {
        $this->apiKey = empty($apiKey) ? '' : $apiKey;
        return $this->calculateSignature(
            $this->apiKey,
            $params
        );
    }

    /**
     * Static method for quick calls to calculate a signature.
     * @param $apiKey
     * @param array $params
     * @param null $timestamp
     * @param string $format
     * @param string $version
     * @return array|string
     */
    public static function calculateSignature($apiKey, $params = [])
    {

        $parameters = [
            'Version' => '1.0',
            'Format' => 'json',
            'Timestamp' => self::getTimestamp()
        ];

        $parameters = array_merge($parameters, $params);

        try {
            ksort($parameters);
            $strToSign = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
            $signature = rawurlencode(hash_hmac('sha256', $strToSign, $apiKey, false));
            $parameters['Signature'] = $signature;
            return $parameters;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

}
