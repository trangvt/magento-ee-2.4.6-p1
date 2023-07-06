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

class Product extends \Lazada\Sdk\Api
{
    /**
     * Retrieve Category Tree
     * @param boolean $force
     * @link  https://lazada-sellercenter.readme.io/docs/getcategorytree
     * @return array
     */
    public function getCategories($force = false)
    {
        $categoryTree = [];
        try {
            $name =  self::ACTION_GET_CATEGORIES .'-'. $this->config->getRegion() . '.json';
            $dir = $this->config->getBaseDirectory() . DS . 'categories';
            $path = $dir . DS . $name;
            if (file_exists($path) && !$force) {
                $categoryTree = file_get_contents($path);
                $categoryTree = json_decode($categoryTree, true);
            } else {
                $client = new \Lazada\Sdk\Lazop\Client($this->config);
                $request = new \Lazada\Sdk\Lazop\Request('/category/tree/get','GET');
                $response = $client->execute($request, $this->config->getAccessToken());
                $response = new \Lazada\Sdk\Api\Response($response);
                $response->setFeedFile($path);
                $response->setAction(self::ACTION_GET_CATEGORIES);
                if (!empty($response->getBody()) and
                    $response->getStatus() == \Lazada\Sdk\Api\Response::REQUEST_STATUS_SUCCESS
                ) {
                    file_put_contents($this->getFile($dir, $name), json_encode($response->getBody()));
                    $categoryTree = $response->getBody();
                }
            }
        } catch (\Exception $e) {
            if ($this->config->getDebugMode()) {
                $this->config->getLogger()->debug($e->getMessage(), ['path' => __METHOD__]);
            }
        }

        return $categoryTree;
    }

    /**
     * Retrieve Attributes for a Category
     * @param null $categoryId
     * @param bool $force
     * @return array
     */
    public function getCategoriesAttributes($categoryId = null, $force = false)
    {
        $categoryAttributeTree = [];
        if (isset($categoryId)) {
            try {
                $dir = $this->config->getBaseDirectory() . DS . 'categories';
                $name = self::ACTION_GET_CATEGORIES_ATTRIBUTES .'-'. $this->config->getRegion() . "-{$categoryId}.json";
                $path = $dir . DS . $name;
                if (file_exists($path) && !$force) {
                    $categoryAttributeTree = file_get_contents($path);
                    $categoryAttributeTree = json_decode($categoryAttributeTree, true);
                } else {
                    $client = new \Lazada\Sdk\Lazop\Client($this->config);
                    $request = new \Lazada\Sdk\Lazop\Request('/category/attributes/get','GET');
                    $request->addApiParam("primary_category_id", $categoryId);
                    $response = $client->execute($request, $this->config->getAccessToken());
                    $response = new \Lazada\Sdk\Api\Response($response);
                    $response->setFeedFile($path);
                    $response->setAction(self::ACTION_GET_CATEGORIES_ATTRIBUTES);
                    if (!empty($response->getBody()) and
                        $response->getStatus() == \Lazada\Sdk\Api\Response::REQUEST_STATUS_SUCCESS
                    ) {
                        file_put_contents($this->getFile($dir, $name), json_encode($response->getBody()));
                        $categoryAttributeTree = $response->getBody();
                    }
                }
            } catch (\Exception $e) {
                if ($this->config->getDebugMode()) {
                    $this->config->getLogger()->debug($e->getMessage(), ['path' => __METHOD__]);
                }
            }
        }

        return $categoryAttributeTree;
    }

    public function getProducts($params = [])
    {
        $products = new \Lazada\Sdk\Api\Response([]);
        try {
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/products/get','GET');
            foreach ($params as $id => $value) {
                $request->addApiParam($id, $value);
            }
            $response = $client->execute($request, $this->config->getAccessToken());
            $response = new \Lazada\Sdk\Api\Response($response);
            $response->setAction(self::ACTION_GET_PRODUCTS);
        } catch (\Exception $e) {
            if ($this->config->getDebugMode()) {
                $this->config->getLogger()->debug($e->getMessage(), ['path' => __METHOD__]);
            }
        }
        return $products;
    }

    /**
     * @param array $params
     * @param bool $force
     * @return array|bool|mixed|string
     */
    public function getBrands($params = [], $force = true)
    {
        $response = new \Lazada\Sdk\Api\Response([]);
        $response->setAction(self::ACTION_GET_BRANDS);
        $brands = [];
        try {
            $dir = $this->config->getBaseDirectory() . DS . 'brands';
            $name = self::ACTION_GET_BRANDS .'-'. $this->config->getRegion() . ".json";
            $path = $dir . DS . $name;
            if (file_exists($path) && !$force) {
                $brands = file_get_contents($path);
                $brands = json_decode($brands, true);
            } else {
                $client = new \Lazada\Sdk\Lazop\Client($this->config);
                $request = new \Lazada\Sdk\Lazop\Request('/brands/get','GET');
                $response = $client->execute($request, $this->config->getAccessToken());
                $response = new \Lazada\Sdk\Api\Response($response);
                $response->setFeedFile($path);
                if (!empty($response->getBody()) and
                    $response->getStatus() == \Lazada\Sdk\Api\Response::REQUEST_STATUS_SUCCESS
                ) {
                    file_put_contents($this->getFile($dir, $name), json_encode($response->getBody()));
                    $brands = $response->getBody();
                }
            }
        } catch (\Exception $e) {
            if ($this->config->getDebugMode()) {
                $this->config->getLogger()->debug($e->getMessage(), ['path' => __METHOD__]);
            }
        }

        return $brands;
    }

    /**
     * Create Product on Lazada
     * @param $data
     * @return Api\Response
     * @throws \Exception
     */
    public function createProduct($data)
    {
        $response = new \Lazada\Sdk\Api\Response([]);
        $response->setAction(self::ACTION_POST_PRODUCT);
        if (isset($data[0]['Product'])) {
            $products = [
                'Request' => [
                    '_attribute' => [],
                    '_value' => $data
                ]
            ];

            $products = $this->config->getGenerator()->arrayToXml($products);
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/product/create');
            $request->addApiParam('payload', $products->__toString());
            $params = $client->execute($request, $this->config->getAccessToken());
            $response->load($params);

            if ($this->config->getDebugMode()) {
                $path = $this->getFile(
                    $this->config->getBaseDirectory(),
                    self::ACTION_POST_PRODUCT. '-' . $this->getSuffix() . '-' . $this->config->getRegion() . '.xml'
                );
                $products->save($path);
                $response->setFeedFile($path);
            }
        }

        return $response;
    }

    /**
     * Update Product on Lazada
     * @param $data
     * @return Api\Response
     * @throws \Exception
     */
    public function updateProduct($data)
    {
        $response = new \Lazada\Sdk\Api\Response([]);
        $response->setAction(self::ACTION_POST_UPDATE_PRODUCT);
        if (isset($data[0]['Product'])) {
            $products = [
                'Request' => [
                    '_attribute' => [],
                    '_value' => $data
                ]
            ];

            $products = $this->config->getGenerator()->arrayToXml($products);
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/product/update');
            $request->addApiParam('payload', $products->__toString());
            $params = $client->execute($request, $this->config->getAccessToken());
            $response->load($params);

            if ($this->config->getDebugMode()) {
                $path = $this->getFile(
                    $this->config->getBaseDirectory(),
                    self::ACTION_POST_UPDATE_PRODUCT. '-' . $this->getSuffix() . '-' . $this->config->getRegion() . '.xml'
                );
                $products->save($path);
                $response->setFeedFile($path);
            }
        }

        return $response;
    }

    /**
     * Upload images to lazada
     * @param $data
     * @return Api\Response
     * @throws \Exception
     */
    public function uploadImages($data)
    {
        $response = new \Lazada\Sdk\Api\Response([]);
        $response->setAction(self::ACTION_MIGRATE_IMAGE);
        if (isset($data)) {
            $images = [
                'Request' => [
                    '_attribute' => [],
                    '_value' => $data
                ]
            ];
            $images = $this->config->getGenerator()->arrayToXml($images);
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/image/migrate');
            $request->addApiParam('payload', $images->__toString());
            $params = $client->execute($request, $this->config->getAccessToken());
            $response->load($params);

            if ($this->config->getDebugMode()) {
                $path = $this->getFile(
                    $this->config->getBaseDirectory(),
                    self::ACTION_MIGRATE_IMAGE. '-' . $this->getSuffix() . '-' . $this->config->getRegion() . '.xml'
                );
                $images->save($path);
                $response->setFeedFile($path);
            }
        }

        return $response;
    }

    /**
     * Update Inventory Price
     * @param $data
     * @return Api\Response
     * @throws \Exception
     */
    public function updateInventoryPrice($data)
    {
        $response = new \Lazada\Sdk\Api\Response([]);
        $response->setAction(self::ACTION_POST_UPDATE_PRICE_QUANTITY);
        if (isset($data[0]['Product'])) {
            $products = [
                'Request' => [
                    '_attribute' => [],
                    '_value' => $data
                ]
            ];

            $products = $this->config->getGenerator()->arrayToXml($products);
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/product/price_quantity/update');
            $request->addApiParam('payload', $products->__toString());
            $params = $client->execute($request, $this->config->getAccessToken());
            $response->load($params);

            if ($this->config->getDebugMode()) {
                $path = $this->getFile(
                    $this->config->getBaseDirectory(),
                    self::ACTION_POST_UPDATE_PRICE_QUANTITY. '-' . $this->getSuffix() . '-' .
                    $this->config->getRegion() . '.xml'
                );
                $products->save($path);
                $response->setFeedFile($path);
            }
        }

        return $response;
    }

    /**
     * Delete Product on Lazada
     * @param $data
     * @return Api\Response
     * @throws \Exception
     */
    public function deleteProduct($data)
    {
        $response = new \Lazada\Sdk\Api\Response([]);
        $response->setAction(self::ACTION_POST_DELETE_PRODUCT);
        if (count($data) > 0) {

            $json = \GuzzleHttp\json_encode($data);
            $client = new \Lazada\Sdk\Lazop\Client($this->config);
            $request = new \Lazada\Sdk\Lazop\Request('/product/remove');
            $request->addApiParam('seller_sku_list', $json);
            $params = $client->execute($request, $this->config->getAccessToken());
            $response->load($params);

            if ($this->config->getDebugMode()) {
                $path = $this->getFile(
                    $this->config->getBaseDirectory(),
                    self::ACTION_POST_DELETE_PRODUCT. '-' . $this->getSuffix() . '-' .
                    $this->config->getRegion() . '.xml'
                );
                $response->setFeedFile($path);
            }
        }

        return $response;
    }
}
