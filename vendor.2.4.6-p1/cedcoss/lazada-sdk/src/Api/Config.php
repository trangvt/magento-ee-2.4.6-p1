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

class Config implements ConfigInterface
{

    const ENDPOINTS_LIVE = [
        'Singapore' => \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_SG,
        'Thailand' => \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_TH,
        'Malaysia' => \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_MY,
        'Vietnam' => \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_VN,
        'Philippines' => \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_PH,
        'Indonesia' => \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_ID
    ];

    /**
     * Lazada Region
     * @var string $region
     */
    protected $region;

    /**
     * Lazada Code
     * @var string $code
     */
    protected $code;

    /**
     * Lazada Api Key
     * @var string $appSecret
     */
    protected $appSecret;

    /**
     * Lazada Endpoint Url
     * @var string $endpoint
     * @refer var $marketplaceIds
     */
    protected $endpoint;

    /**
     * Mute Logging
     * @var boolean $debugMode
     */
    protected $debugMode;

    /**
     * Base Directory
     * @var boolean $baseDirectory
     */
    protected $baseDirectory;

    /**
     * Xml Parser
     * @var $parser
     */
    protected $parser;

    /**
     * Xml Generator
     * @var $generator
     */
    protected $generator;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var string $appKey
     */
    protected $appKey;

    /**
     * @var string $token
     */
    protected $token;

    /**
     * @var string $refreshToken
     */
    protected $refreshToken;

    /**
     * @var string $refreshExpiry
     */
    protected $refreshExpiry;

    /**
     * @var string $expiry
     */
    protected $expiry;

    /**
     * [
     * 'code' => 'AU5LJQPL530RI',
     * 'appKey' => 'rOnMU7LxyLSE1VtaNUtTcpEbXje/0FFrE29g+isl',
     * 'appSecret' => 'rOnMU7LxyLSE1VtaNUtTcpEbXje/0FFrE29g+isl',
     * 'endpoint' => 'https://mws.amazonservices.in/',
     * 'baseDirectory' => ''
     * 'debugMode' => false
     * ]
     * @inheritdoc
     */
    public function __construct($params = [])
    {
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getDebugMode()
    {
        return $this->debugMode;
    }

    /**
     * @inheritdoc
     */
    public function setDebugMode($debugMode = true)
    {
        $this->debugMode = $debugMode;
    }

    public function setBaseDirectory($baseDirectory)
    {
        $this->baseDirectory = $baseDirectory;
        return true;
    }

    /**
     * Set Lazada Code
     * @param string $code
     * @return void
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get Lazada Seller Id
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set Lazada Api Secret
     * @param string $appSecret
     * @return boolean
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
        return true;
    }

    /**
     * Get Lazada App Secret
     * @return mixed
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * Set Lazada Service Url
     * @param string $serviceUrl
     * @return boolean
     */
    public function setEndpointUrl($endpointUrl)
    {
        $this->endpoint = $endpointUrl;
        return true;
    }

    /**
     * Get Lazada Service Url
     * @return string
     */
    public function getEndpointUrl()
    {
        return (string)$this->endpoint;
    }

    public function getBaseDirectory()
    {
        return $this->baseDirectory;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function setParser($parser)
    {
        $this->parser = $parser;
    }

    public function getGenerator()
    {
        return $this->generator;
    }

    public function setGenerator($generator)
    {
        $this->generator = $generator;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set Lazada AppKey
     * @param string $appKey
     * @return void
     */
    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;
    }

    /**
     * Get Lazada AppKey
     * @return string
     */
    public function getAppKey()
    {
        return $this->appKey;
    }

    /**
     * Set Lazada Access Token
     * @param string $token
     * @return void
     */
    public function setAccessToken($token)
    {
        $this->token = $token;
    }

    /**
     * Get Lazada Access Token
     * @return string
     */
    public function getAccessToken()
    {
        return $this->token;
    }

    /**
     * Set Lazada Refresh Token
     * @param string $token
     * @return void
     */
    public function setRefreshToken($token)
    {
        $this->refreshToken = $token;
    }

    /**
     * Get Lazada Refresh Token
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set Lazada Token Expiry
     * @param string $expiry
     * @return void
     */
    public function setTokenExpiry($expiry)
    {
        $this->expiry = $expiry;
    }

    /**
     * Get Lazada Token Expiry
     * @return string
     */
    public function getTokenExpiry()
    {
        return $this->expiry;
    }

    /**
     * Set Lazada Refresh Token Expiry
     * @param string $expiry
     * @return void
     */
    public function setRefreshTokenExpiry($expiry)
    {
        $this->refreshExpiry = $expiry;
    }

    /**
     * Get Lazada Refresh Token Expiry
     * @return string
     */
    public function getRefreshTokenExpiry()
    {
        return $this->refreshExpiry;
    }

    /**
     * Set Lazada Region Code
     * @param string $region
     * @return void
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * Get Lazada Region Code
     * @return string
     */
    public function getRegion()
    {
        switch ($this->getEndpointUrl()) {
            case \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_ID:
                 $this->region = 'id';
                break;
            case \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_SG:
                 $this->region = 'sg';
                break;
            case \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_VN:
                 $this->region = 'vn';
                break;
            case \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_TH:
                 $this->region = 'th';
                break;
            case \Lazada\Sdk\Lazop\UrlConstants::API_GATEWAY_URL_PH:
                 $this->region = 'ph';
                break;
            default:
                $this->region = 'my';
                break;
        }

        return $this->region;
    }
}
