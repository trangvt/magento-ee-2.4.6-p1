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

class Order extends \Lazada\Sdk\Api
{

    public function getOrders($params = [])
    {
        $orders = new \Lazada\Sdk\Api\Response([]);
        $orders->setAction(self::ACTION_GET_ORDERS);
        try {
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/orders/get','GET');
            foreach ($params as $id => $value) {
                $request->addApiParam($id, $value);
            }
            $params = $client->execute($request, $this->config->getAccessToken());
            $orders->load($params);

            //$params = array_merge(['Action' => self::ACTION_GET_ORDERS], $params);
           // $response = $this->getRequest($params);
        } catch (\Exception $e) {
            if ($this->debugMode) {
                $this->logger->debug(
                    "Lazada\\Sdk\\Order\\getOrders() : Errors: " . var_export($e->getMessage(), true)
                );
            }
        }
        return $orders;
    }

    /**
     * Get a Order
     * @param array $params
     * @link https://lazada-sellercenter.readme.io/docs/getorder
     */
    public function getOrder($params = [])
    {
        $order = new \Lazada\Sdk\Api\Response([]);
        $order->setAction(self::ACTION_GET_ORDER);
        try {
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/order/get','GET');

            foreach ($params as $id => $value) {
                $request->addApiParam($id, $value);
            }
            $params = $client->execute($request, $this->config->getAccessToken());
            $order->load($params);
        } catch (\Exception $e) {
            if ($this->debugMode) {
                $this->logger->debug(
                    "Lazada\\Sdk\\Order\\getOrder() : Errors: " . var_export($e->getMessage(), true)
                );
            }
        }
        return $order;
    }

    /**
     * @param array $params
     * @return array|bool
     */
    public function getOrderItems($orderId)
    {
        $orderItems = new \Lazada\Sdk\Api\Response([]);
        $orderItems->setAction(self::ACTION_GET_ORDER_ITEMS);
        try {
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/order/items/get','GET');

            $request->addApiParam('order_id', $orderId);

            $params = $client->execute($request, $this->config->getAccessToken());
            $orderItems->load($params);

            //$params = array_merge(['Action' => self::ACTION_GET_ORDERS], $params);
            // $response = $this->getRequest($params);
        } catch (\Exception $e) {
            if ($this->debugMode) {
                $this->logger->debug(
                    "Lazada\\Sdk\\Order\\getOrderItems() : Errors: " . var_export($e->getMessage(), true)
                );
            }
        }
        return $orderItems;
    }

    /**
     * @param array $params
     * @return array|Api\Response
     */
    public function cancelOrderItem(array $params = [])
    {
        $orderItems = $this->defaultResponse();
        try {
            $params = array_merge(['Action' => self::ACTION_POST_CANCEL_ORDER_ITEM], $params);
            $response = $this->postRequest($params);
            $path = '';
            if ($this->debugMode) {
                $path = $this->getFile(
                    $this->baseDirectory,
                    self::ACTION_POST_CANCEL_ORDER_ITEM . '-' . $this->timeStamp . '.json'
                );
                @file_put_contents($path, json_encode($params));
            }
            $orderItems = $this->responseParse($response, self::ACTION_POST_CANCEL_ORDER_ITEM, $path);
        } catch (\Exception $e) {
            if ($this->debugMode) {
                $this->logger->debug(
                    "Lazada\\Sdk\\Order\\cancelOrderItem() : Errors: " . var_export($e->getMessage(), true)
                );
            }
        }
        return $orderItems;
    }
    /**
     * @param array $params
     * @return array|Api\Response
     */
    public function packOrderItems(array $params = [])
    {
        $response = new \Lazada\Sdk\Api\Response([]);
        $response->setAction(self::ACTION_POST_SET_STATUS_TO_BE_PACKED_BY_MARKET_PLACE);
        try {
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/order/pack','POST');
            foreach ($params as $id => $value) {
                $request->addApiParam($id, $value);
            }
            $result = $client->execute($request, $this->config->getAccessToken());
            $response->load($result);
        } catch (\Exception $e) {
            if ($this->debugMode) {
                $this->logger->debug(
                    "Lazada\\Sdk\\Order\\packOrderItems() : Errors: " . var_export($e->getMessage(), true)
                );
            }
        }
        return $response;
    }
    /**
     * @param array $params
     * @return array|Api\Response
     */
    public function readyOrderItems(array $params = [])
    {
        $response = new \Lazada\Sdk\Api\Response([]);
        $response->setAction(self::ACTION_POST_SET_STATUS_TO_READY_TO_SHIP);
        try {
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/order/rts','POST');
            foreach ($params as $id => $value) {
                $request->addApiParam($id, $value);
            }
            $result = $client->execute($request, $this->config->getAccessToken());
            $response->load($result);
        } catch (\Exception $e) {
            if ($this->debugMode) {
                $this->logger->debug(
                    "Lazada\\Sdk\\Order\\readyOrderItems() : Errors: " . var_export($e->getMessage(), true)
                );
            }
        }
        return $response;
    }

    public function getCancelReasons(array $params = [], $forceFetch = false)
    {
        $reasons = [];
        try {
            $dir = $this->baseDirectory . DS . 'order';
            $name = self::ACTION_GET_CANCEL_ORDER_ITEM_REASONS . ".json";
            $path = $dir . DS . $name;
            if (file_exists($path) && !$forceFetch) {
                $reasons = json_decode(file_get_contents($path), true);
            } else {
                $params = array_merge(['Action' => self::ACTION_GET_CANCEL_ORDER_ITEM_REASONS], $params);
                $response = $this->postRequest($params);
                $response = $this->responseParse($response, self::ACTION_GET_CANCEL_ORDER_ITEM_REASONS);
                if (!empty($response->getBody()) and
                    $response->getStatus() == \Lazada\Sdk\Api\Response::REQUEST_STATUS_SUCCESS
                ) {
                    file_put_contents($this->getFile($dir, $name), json_encode($response->getBody()));
                    $reasons = $response->getBody();
                }
            }
        } catch (\Exception $e) {
            if ($this->debugMode) {
                $this->logger->debug(
                    "Lazada\\Sdk\\Order\\getCancelReasons() : Errors: " . var_export($e->getMessage(), true)
                );
            }
        }

        return $reasons;
    }

    public function getShipmentProviders(array $params = [], $forceFetch = false)
    {
        $shipmentProviders = new \Lazada\Sdk\Api\Response([]);
        $shipmentProviders->setAction(self::ACTION_GET_SHIPMENT_PROVIDERS);
        try {
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/shipment/providers/get','GET');
            $params = $client->execute($request, $this->config->getAccessToken());
            $shipmentProviders->load($params);
        } catch (\Exception $e) {
            if ($this->debugMode) {
                $this->logger->debug(
                    "Lazada\\Sdk\\Order\\getCancelReasons() : Errors: " . var_export($e->getMessage(), true)
                );
            }
        }

        return $shipmentProviders;
    }

}
