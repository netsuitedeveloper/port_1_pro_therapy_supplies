<?php

///////////////////////////////////////////////////////////////////////////////////////////////////

class AmazonInventory
{
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($config) { 
        $this->config = $config;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function listInventorySupply($data = null) { 
        if (isset($data['SellerSkus'])) { 
            $seller_sku_groups = array_split($data['SellerSkus'], 50);
            $data_ = $data;
            $inventory = null;
            foreach($seller_sku_groups as $seller_sku_group) { 
                $data_['SellerSkus'] = $seller_sku_group;
                $inventory = array_merge_ex($inventory, $this->_listInventorySupply($data_));
            }
            return $inventory;
        } else { 
            return $this->_listInventorySupply($data);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function _listInventorySupply($data = null) { 
        $request = new AmazonInventoryRequest($this);
        $request->setAction('ListInventorySupply');
        $request->setParameters(array_merge_ex($data, array(
            'SellerId' => get_and_require($this->config, 'merchant_id'),
        )));
        $response = $request->getResponse();

        $members = get_and_require($response, 'ListInventorySupplyResult', 'InventorySupplyList', 'member');

        $next_token = get_or_null($response, 'ListInventorySupplyResult', 'NextToken');
        while ($next_token) { 
            usleep(500);

            $request = new AmazonInventoryRequest($this);
            $request->setAction('ListInventorySupplyByNextToken');
            $request->setParameters(array_merge_ex($data, array(
                'SellerId' => get_and_require($this->config, 'merchant_id'),
                'NextToken' => $next_token,
            )));
            $response = $request->getResponse();

            $members = array_merge_ex($members, get_and_require($response, 'ListInventorySupplyByNextTokenResult', 'InventorySupplyList', 'member'));

            $next_token = get_or_null($response, 'ListInventorySupplyByNextTokenResult', 'NextToken');
        }

        return $members;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

///////////////////////////////////////////////////////////////////////////////////////////////////

class AmazonInventoryRequest
{
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    const REQUEST_TYPE = 'POST';
    const VERSION = '2010-10-01';
    const SIGNATURE_VERSION = 2;
    const SIGNATURE_METHOD = 'HmacSHA256';

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public $inventory;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public $parameters;
    public $headers;
    public $action;
    public $data;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($inventory) { 
        $this->inventory = $inventory;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function setAction($action) { 
        $this->action = $action;
        return $this;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function setParameters($parameters) {
        $this->parameters = $this->_prepareParameters($parameters);
        $this->parameters['Action'] = $this->action;
        $this->parameters['AWSAccessKeyId'] = get_and_require($this->inventory->config, 'access_key');
        $this->parameters['Timestamp'] = $this->_getTimestamp();
        $this->parameters['Version'] = self::VERSION;
        $this->parameters['SignatureVersion'] = self::SIGNATURE_VERSION;
        $this->parameters['SignatureMethod'] = self::SIGNATURE_METHOD;
        $this->parameters['Signature'] = $this->signParameters($this->parameters, get_and_require($this->inventory->config, 'secret_key'));
        return $this;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function setBody($data) {
        $this->data = $data;
        $this->headers['Content-MD5'] = $this->getContentMD5($data);
        return $this;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function getResponse() { 
        require_value($this->action);
        require_value($this->parameters);

        if ($this->data) {
            $response = CURL::post_xml(get_and_require($this->inventory->config, 'url'), $this->data, array(
                'query_arguments' => $this->parameters,
            ));
        } else { 
            $response = CURL::post(get_and_require($this->inventory->config, 'url'), $this->parameters, array(
                'as_array' => true,
            ));
            return $response;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function signParameters($parameters, $secret_key) { 
        $parameters['SignatureMethod'] = self::SIGNATURE_METHOD;
        $signature_string = $this->_getSignatureString($parameters);
        return $this->sign($signature_string, $secret_key);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function _getTimestamp() { 
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        return $dt->format(DATE_ISO8601);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function _getSignatureString($parameters, $queue_path = null) { 
        $data = 'POST';
        $data .= "\n";
        $endpoint = parse_url($this->inventory->config['url']);
        $data .= $endpoint['host'];
        $data .= "\n";
        $uri = array_key_exists('path', $endpoint) ? $endpoint['path'] : null;
        if (!isset ($uri)) {
            $uri = "/";
        }
        $uriencoded = implode("/", array_map(array($this, "urlencode"), explode("/", $uri)));
        $data .= $uriencoded;
        $data .= "\n";
        uksort($parameters, 'strcmp');
        $data .= $this->_buildHTTPQueryString($parameters);
        return $data;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function urlencode($value) {
        return str_replace('%7E', '~', rawurlencode($value));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function _buildHTTPQueryString($parameters) {
        $string = array();
        foreach ($parameters as $key => $value) {
            $string[] = $key . '=' . $this->urlencode($value);
        }
        return implode('&', $string);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function sign($data, $key) { 
        return base64_encode(
            hash_hmac('sha256', $data, $key, true)
        );
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function getContentMD5($data) {
        $md5Hash = md5($data, true);
        return base64_encode($md5Hash);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function _prepareParameters($data) {
        if ($data) {
            foreach($data as $key => & $datum) { 
                if (is_array($datum)) { 
                    foreach($datum as $idx => $value) { 
                        $data[$key . '.member.' . (intval($idx)+1)] = $value;
                    }
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////
}

///////////////////////////////////////////////////////////////////////////////////////////////////

