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

/**
 * Directory separator shorthand
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * @api
 */
interface ApiInterface
{
    const SSL_VERIFY = false;

    /**
     * Default API Actions
     */
    const ACTION_GET_ORDERS = 'GetOrders';
    const ACTION_GET_ORDER = 'GetOrder';
    const ACTION_GET_ORDER_ITEMS = 'GetOrderItems';
    const ACTION_GET_ORDER_ITEMS_MULTIPLE = 'GetMultipleOrderItems';
    const ACTION_GET_CATEGORIES = 'GetCategoryTree';
    const ACTION_GET_CATEGORIES_ATTRIBUTES = 'GetCategoryAttributes';
    const ACTION_GET_PRODUCTS = 'GetProducts';
    const ACTION_POST_SEARCH_SPU = 'SearchSPUs';
    const ACTION_POST_UPLOAD_IMAGE = 'UploadImage';
    const ACTION_POST_UPLOAD_IMAGES = 'UploadImages';
    const ACTION_GET_BRANDS = 'GetBrands';
    const ACTION_POST_PRODUCT = 'CreateProduct';
    const ACTION_POST_DELETE_PRODUCT = 'RemoveProduct';
    const ACTION_POST_UPDATE_PRODUCT = 'UpdateProduct';
    const ACTION_POST_UPDATE_PRICE_QUANTITY = 'UpdatePriceQuantity';
    const ACTION_POST_CANCEL_ORDER_ITEM = 'SetStatusToCanceled';
    const ACTION_GET_CANCEL_ORDER_ITEM_REASONS = 'GetFailureReasons';
    const ACTION_GET_SHIPMENT_PROVIDERS = 'GetShipmentProviders';
    const ACTION_POST_SET_STATUS_TO_BE_PACKED_BY_MARKET_PLACE = 'SetStatusToPackedByMarketplace';
    const ACTION_POST_SET_STATUS_TO_READY_TO_SHIP = 'SetStatusToReadyToShip';
    const ACTION_MIGRATE_IMAGES = 'MigrateImages';
    const ACTION_MIGRATE_IMAGE = 'MigrateImage';

    const ACTIONS = [
        self::ACTION_POST_PRODUCT,
        self::ACTION_POST_DELETE_PRODUCT,
        self::ACTION_POST_UPDATE_PRODUCT,
        self::ACTION_POST_UPDATE_PRICE_QUANTITY,
        self::ACTION_GET_ORDER_ITEMS_MULTIPLE,
        self::ACTION_GET_ORDER_ITEMS,
        self::ACTION_GET_ORDER,
        self::ACTION_GET_ORDERS,
        self::ACTION_GET_BRANDS,
        self::ACTION_GET_PRODUCTS,
        self::ACTION_GET_CATEGORIES_ATTRIBUTES,
        self::ACTION_GET_CATEGORIES,
        self::ACTION_POST_SEARCH_SPU,
        self::ACTION_POST_UPLOAD_IMAGE,
        self::ACTION_POST_UPLOAD_IMAGES,
        self::ACTION_GET_CANCEL_ORDER_ITEM_REASONS,
        self::ACTION_POST_CANCEL_ORDER_ITEM,
        self::ACTION_GET_SHIPMENT_PROVIDERS,
        self::ACTION_POST_SET_STATUS_TO_BE_PACKED_BY_MARKET_PLACE,
        self::ACTION_POST_SET_STATUS_TO_READY_TO_SHIP,
        self::ACTION_MIGRATE_IMAGES,
        self::ACTION_MIGRATE_IMAGE
    ];

    const FEED_CODE_ORDER_CREATE = 'order-create';
    const FEED_CODE_ITEM_UPDATE = 'item-update';
    const FEED_CODE_ITEM_DEACTIVATE = 'item-deactivate';
    const FEED_CODE_ITEM_DELETE = 'item-delete';
    const FEED_CODE_INVENTORY_UPDATE = 'inventory-update';
    const FEED_CODE_PRICE_UPDATE = 'price-update';
    const FEED_CODE_ORDER_SHIPMENT = 'order-shipment';
    const FEED_CODE_IMAGE_UPDATE = 'image-update';
    const FEED_CODE_PRODUCT_OVERRIDES = 'product-overrides';
    const FEED_CODE_PRODUCT_RELATIONSHIP = 'product-relationship';
    const FEED_CODE_ORDER_ACKNOWLEDGEMENT = 'order-acknowledgement';
    const FEED_CODE_ORDER_PAYMENT_ADJUSTMENT = 'order-payment-adjustment';
    const FEED_CODE_ORDER_FULFILLMENT = 'order-fulfillment';
    const FEED_CODE_MOCK_FEED = 'mock-feed';
}
