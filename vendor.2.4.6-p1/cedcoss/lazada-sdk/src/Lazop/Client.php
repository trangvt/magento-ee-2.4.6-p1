<?php

namespace Lazada\Sdk\Lazop;

class Client
{
    public $appkey;

    public $secretKey;

    public $gatewayUrl;

    public $connectTimeout;

    public $readTimeout;

    public $logLevel;

    public $logger;

    protected $signMethod = "sha256";

    protected $sdkVersion = "lazop-sdk-php-20180422";

    public function __construct(\Lazada\Sdk\Api\ConfigInterface $config)
    {
        $length = strlen($config->getEndpointUrl());
        if ($length == 0) {
            throw new \Exception("url is empty", 0);
        }

        $this->gatewayUrl = $config->getEndpointUrl();
        $this->appkey = $config->getAppKey();
        $this->secretKey = $config->getAppSecret();
        $this->logger = $config->getLogger();
        $this->logLevel = \Psr\Log\LogLevel::ERROR;
    }

    public function getAppkey()
    {
        return $this->appkey;
    }

    public function execute(Request $request, $accessToken = null)
    {
        $sysParams["app_key"] = $this->appkey;
        $sysParams["sign_method"] = $this->signMethod;
        $sysParams["timestamp"] = $this->msectime();
        if (null != $accessToken) {
            $sysParams["access_token"] = $accessToken;
        }

        $apiParams = $request->udfParams;

        $requestUrl = $this->gatewayUrl;

        if ($this->endWith($requestUrl, "/")) {
            $requestUrl = substr($requestUrl, 0, -1);
        }

        $requestUrl .= $request->apiName;
        $requestUrl .= '?';

        $sysParams["partner_id"] = $this->sdkVersion;

        if ($this->logLevel == \Psr\Log\LogLevel::DEBUG) {
            $sysParams["debug"] = 'true';
        }

        $sysParams["sign"] = $this->generateSign($request->apiName, array_merge($apiParams, $sysParams));

        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }

        $requestUrl = substr($requestUrl, 0, -1);
        $response = '';

        try {
            if ($request->httpMethod == 'POST') {
                $response = $this->post($requestUrl, $apiParams, $request->fileParams, $request->headerParams);
            } else {
                $response = $this->get($requestUrl, $apiParams, $request->headerParams);
            }
        } catch (\Exception $e) {
            $this->log($requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
            throw $e;
        }

        unset($apiParams);

        $response = json_decode($response, true);
        if (isset($response['code']) && $response['code'] != "0") {
            $this->log($requestUrl, $response['code'], $response['message']);
        } else {
            if ($this->logLevel == \Psr\Log\LogLevel::DEBUG || $this->logLevel == \Psr\Log\LogLevel::INFO) {
                $this->log($requestUrl, '', '');
            }
        }

        return $response;
    }

    public function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        return $sec . '000';
    }

    public function endWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return false;
        }
        return (substr($haystack, -$length) === $needle);
    }

    protected function generateSign($apiName, $params)
    {
        ksort($params);

        $stringToBeSigned = '';
        $stringToBeSigned .= $apiName;
        foreach ($params as $k => $v) {
            $stringToBeSigned .= "$k$v";
        }
        unset($k, $v);

        return strtoupper($this->hmacSha256($stringToBeSigned, $this->secretKey));
    }

    public function hmacSha256($data, $key)
    {
        return hash_hmac('sha256', $data, $key);
    }

    public function post($url, $postFields = null, $fileFields = null, $headerFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }

        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }

        if ($headerFields) {
            $headers = array();
            foreach ($headerFields as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            unset($headers);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);

        //https ignore ssl check ?
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $delimiter = '-------------' . uniqid();
        $data = '';
        if ($postFields != null) {
            foreach ($postFields as $name => $content) {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"';
                $data .= "\r\n\r\n" . $content . "\r\n";
            }
            unset($name, $content);
        }

        if ($fileFields != null) {
            foreach ($fileFields as $name => $file) {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file['name'] . "\" \r\n";
                $data .= 'Content-Type: ' . $file['type'] . "\r\n\r\n";
                $data .= $file['content'] . "\r\n";
            }
            unset($name, $file);
        }
        $data .= "--" . $delimiter . "--";

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
                'Content-Length: ' . strlen($data)
            )
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        unset($data);

        $err = curl_error($ch);
        if ($err) {
            curl_close($ch);
            $this->log($url, $err, $response);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (200 !== $httpStatusCode) {
                $this->log($url, $httpStatusCode, $response);
            }
        }

        return $response;
    }

    public function get($url, $apiFields = null, $headerFields = null)
    {
        $ch = curl_init();

        foreach ($apiFields as $key => $value) {
            $url .= "&" . "$key=" . urlencode($value);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if ($headerFields) {
            $headers = [];
            foreach ($headerFields as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            unset($headers);
        }

        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }

        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $this->sdkVersion);

        //https ignore ssl check ?
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);

        $err = curl_error($ch);

        if ($err) {
            curl_close($ch);
            $this->log($url, $err, $response);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (200 !== $httpStatusCode) {
                $this->log($url, $httpStatusCode, $response);
            }
        }

        return $response;
    }

    protected function log($url, $error, $response)
    {
        $localIp = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : "CLI";
        if (isset($this->logger) && method_exists($this->logger, 'log')) {
            $log = [
                'timestamp' => date("Y-m-d H:i:s"),
                'appKey' => $this->appkey,
                'ip' => $localIp,
                'os' => PHP_OS,
                'sdk' => $this->sdkVersion,
                'url' => $url,
                'error' => $error,
                'response' => str_replace("\n", "", $response)
            ];
            $this->logger->log(\Psr\Log\LogLevel::DEBUG, 'Sdk client error log.', $log);
        }
    }
}
