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
 * @category    Lazada-Sdk
 * @package     Ced_Lazada_Sdk
 * @author      CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CedCommerce (http://cedcommerce.com/)
 * @license     http://cedcommerce.com/license-agreement.txt
 */

namespace Lazada\Sdk\Api;

interface ConfigInterface
{

    /**
     * ConfigInterface constructor.
     * @param array $params
     */
    public function __construct($params = []);

    /**
     * Set Base Directory
     * @param $baseDirectory
     * @return mixed
     */
    public function setBaseDirectory($baseDirectory);

    /**
     * Get Base Directory
     * @return mixed
     */
    public function getBaseDirectory();

    /**
     * Set Lazada Code
     * @param string $code
     * @return void
     */
    public function setCode($code);

    /**
     * Get Lazada Seller Id
     * @return string
     */
    public function getCode();

    /**
     * Set Lazada App Secret
     * @param string $appSecret
     * @return boolean
     */
    public function setAppSecret($appSecret);

    /**
     * Get Lazada ApiKey
     * @return mixed
     */
    public function getAppSecret();

    /**
     * Set Lazada AppKey
     * @param string $appKey
     * @return void
     */
    public function setAppKey($appKey);

    /**
     * Get Lazada AppKey
     * @return string
     */
    public function getAppKey();

    /**
     * Set Lazada Access Token
     * @param string $token
     * @return void
     */
    public function setAccessToken($token);

    /**
     * Get Lazada Access Token
     * @return string
     */
    public function getAccessToken();

    /**
     * Set Lazada Refresh Token
     * @param string $token
     * @return void
     */
    public function setRefreshToken($token);

    /**
     * Get Lazada Refresh Token
     * @return string
     */
    public function getRefreshToken();

    /**
     * Set Lazada Token Expiry
     * @param string $expiry
     * @return void
     */
    public function setTokenExpiry($expiry);

    /**
     * Get Lazada Token Expiry
     * @return string
     */
    public function getTokenExpiry();

    /**
     * Set Lazada Refresh Token Expiry
     * @param string $expiry
     * @return void
     */
    public function setRefreshTokenExpiry($expiry);

    /**
     * Get Lazada Refresh Token Expiry
     * @return string
     */
    public function getRefreshTokenExpiry();

    /**
     * Set Lazada Gateway Url
     * @param string $endpointUrl
     * @return boolean
     */
    public function setEndpointUrl($endpointUrl);

    /**
     * Get Lazada Service Url
     * @return string
     */
    public function getEndpointUrl();

    /**
     * Set Lazada Region Code
     * @param string $region
     * @return void
     */
    public function setRegion($region);

    /**
     * Get Lazada Marketplace Code
     * @return string
     */
    public function getRegion();

    /**
     * Set to enable or disable logging
     * @param bool $debugMode
     * @return boolean
     */
    public function setDebugMode($debugMode = true);

    /**
     * Get Logging status
     * @return boolean
     */
    public function getDebugMode();

    /**
     * Get Xml Generator
     * @return mixed
     */
    public function getParser();

    /**
     * Set Xml Parser
     * @param $parser
     * @return mixed
     */
    public function setParser($parser);

    /**
     * Get Xml Generator
     * @return mixed
     */
    public function getGenerator();

    /**
     * Set Xml Generator
     * @param $generator
     * @return mixed
     */
    public function setGenerator($generator);

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger);

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger();
}
