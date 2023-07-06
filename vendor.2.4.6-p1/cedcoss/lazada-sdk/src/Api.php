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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Api implements \Lazada\Sdk\ApiInterface
{
    /**
     * Api Config
     * @var \Lazada\Sdk\Api\ConfigInterface $config
     */
    public $config;

    /**
     * Api constructor.
     * @param Api\ConfigInterface $config
     */
    public function __construct(
        \Lazada\Sdk\Api\ConfigInterface $config
    ) {
        $this->config = $config;
    }

    public function getToken()
    {
        $result = new \Lazada\Sdk\Api\Response([]);
        $client = new \Lazada\Sdk\Lazop\Client($this->config);
        $request = new \Lazada\Sdk\Lazop\Request("/auth/token/create");
        $request->addApiParam("code", $this->config->getCode());
        $response = $client->execute($request);
        $result->load($response);
        $result->setBody($response);
        return $result;
    }

    public function refreshToken()
    {
        $client = new \Lazada\Sdk\Lazop\Client($this->config);
        $request = new \Lazada\Sdk\Lazop\Request("/auth/token/refresh");
        $request->addApiParam("refresh_token", $this->config->getRefreshToken());
        $response = $client->execute($request);
        return $response;
    }

    /**
     * Get a File or Create
     * @param $path
     * @param null $name
     * @return string
     */
    public function getFile($path, $name = null)
    {

        if (!file_exists($path)) {
            @mkdir($path, 0775, true);
        }

        if ($name != null) {
            $path = $path . DS . $name;

            if (!file_exists($path)) {
                @file($path);
            }
        }

        return $path;
    }

    public function getSuffix()
    {
        $id = uniqid();
        return $id;
    }
}
