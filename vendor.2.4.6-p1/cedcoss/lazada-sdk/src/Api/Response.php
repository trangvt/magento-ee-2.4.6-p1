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
 * @category    Ced
 * @package     Ced_Lazada
 * @author      CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CedCommerce (http://cedcommerce.com/)
 * @license     http://cedcommerce.com/license-agreement.txt
 */

namespace Lazada\Sdk\Api;

class Response
{

    const REQUEST_STATUS_SUCCESS = 'success';

    const REQUEST_STATUS_FAILURE = 'failure';

    protected $request_id = null;

    protected $data;

    protected $code = '0';

    protected $type = null;

    protected $action = null;

    protected $timestamp;

    protected $status = self::REQUEST_STATUS_FAILURE;

    protected $feedFile;

    protected $message = '';

    protected $detail = '';

    public function __construct($params = [])
    {
        $this->init();
        $this->load($params);
    }

    public function load($params = [])
    {
        if (isset($params) and is_array($params)) {
            foreach ($params as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }

            if (isset($params['code']) && $params['code'] == '0') {
                $this->setStatus(\Lazada\Sdk\Api\Response::REQUEST_STATUS_SUCCESS);
            }
        }
    }

    public function init()
    {
        $now = new \DateTime();
        $defaults = [
            'code' => '0',
            'request_id' => null,
            'timestamp' => $now->format(\DateTime::ISO8601),
            'status' => \Lazada\Sdk\Api\Response::REQUEST_STATUS_FAILURE,
            'data' => [],
        ];

        foreach ($defaults as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function setBody($data)
    {
        $this->data = $data;
    }

    public function getBody()
    {
        if (isset($this->data) and is_array($this->data)) {
            return $this->data;
        }

        return [];
    }

    public function setRequestId($requestId)
    {
        $this->request_id = $requestId;
    }

    public function getRequestId()
    {
        return $this->request_id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setFeedFile($feedFile)
    {
        $this->feedFile = $feedFile;
    }

    public function getFeedFile()
    {
        return $this->feedFile;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setError($message)
    {
        $this->message = $message;
    }

    public function getError()
    {
        return $this->message;
    }

    public function setDetail($detail)
    {
        $this->detail = $detail;
    }

    public function getDetail()
    {
        return $this->detail;
    }

    public function setCode($code) {
        return $this->code = $code;
    }

    public function getCode() {
        return $this->code;
    }

}
