<?php

///////////////////////////////////////////////////////////////////////////////////////////////////

class AmazonMWS
{
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public $config;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($config) { 
        $this->config = $config;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function requestReportAsync($report_type, $data = null) { 
        $request = new AmazonMWSRequest($this);
        $request->setAction('RequestReport');
        $request->setParameters(array_merge_ex($data, array(
            'MarketplaceIdList.Id.1' => get_and_require($this->config, 'marketplace_id'),
            'Merchant' => get_and_require($this->config, 'merchant_id'),
            'ReportType' => $report_type,
        )));
        $response = $request->getResponse();

        $request_id = get_and_require($response, 'RequestReportResult', 'ReportRequestInfo', 'ReportRequestId');

        return $request_id;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function waitForReport($request_id) { 
        while (true) { 
            list($status, $report_id) = $this->getReportRequestInfo($request_id);

            if ($status === '_DONE_') { 
                require_value($report_id);
                return $report_id;
            } else if ($status === '_DONE_NO_DATA_') { 
                return false;
            } else if ($status === '_CANCELLED_') { 
                return null;
            } else { 
                sleep(45);
            }
        }
        return null;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function getReportRequestInfo($request_id) { 
        $request = new AmazonMWSRequest($this);
        $request->setAction('GetReportRequestList');
        $request->setParameters(array(
            'Merchant' => get_and_require($this->config, 'merchant_id'),
            'ReportRequestIdList.Id.1' => $request_id,
        ));
        $response = $request->getResponse();

        $status = get_and_require($response, 'GetReportRequestListResult', 'ReportRequestInfo', 'ReportProcessingStatus');
        $report_id = get_or_null($response, 'GetReportRequestListResult', 'ReportRequestInfo', 'GeneratedReportId');

        return array($status, $report_id);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function getReport($report_id) { 
        $request = new AmazonMWSRequest($this);
        $request->setAction('GetReport');
        $request->setParameters(array(
            'Merchant' => get_and_require($this->config, 'merchant_id'),
            'ReportId' => $report_id,
        ));
        $response = $request->getDataResponse();

        $path = FILE::writeToTemporary($response);
        $csv = CSV::read($path, "\t");

        return $csv;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

///////////////////////////////////////////////////////////////////////////////////////////////////

class AmazonMWSRequest
{
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    const REQUEST_TYPE = 'POST';
    const SERVICE_VERSION = '2009-01-01';
    const MWS_CLIENT_VERSION = '2014-09-30';
    const SIGNATURE_VERSION = 2;
    const SIGNATURE_METHOD = 'HmacSHA256';

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public $mws;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public $parameters;
    public $headers;
    public $action;
    public $data;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($mws) { 
        $this->mws = $mws;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function setAction($action) { 
        $this->action = $action;
        return $this;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function setParameters($parameters) {
        $this->parameters = $parameters;
        $this->parameters['Action'] = $this->action;
        $this->parameters['AWSAccessKeyId'] = get_and_require($this->mws->config, 'access_key');
        $this->parameters['Timestamp'] = $this->_getTimestamp();
        $this->parameters['Version'] = self::SERVICE_VERSION;
        $this->parameters['SignatureVersion'] = self::SIGNATURE_VERSION;
        $this->parameters['SignatureMethod'] = self::SIGNATURE_METHOD;
        $this->parameters['Signature'] = $this->signParameters($this->parameters, get_and_require($this->mws->config, 'secret_key'));
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
        //if (isset($this->headers['Content-MD5']) == false) { 
            //$this->headers['Content-MD5'] = null;
        //}
        //$this->headers['Transfer-Encoding'] = 'chunked';

        require_value($this->action);
        require_value($this->parameters);

        $this->parameters['Action'] = $this->action;

        if ($this->data) {
            $response = CURL::post_xml(get_and_require($this->mws->config, 'endpoint'), $this->data, array(
                //'headers' => $this->headers,
                'query_arguments' => $this->parameters,
            ));
        } else { 
            $response = CURL::post(get_and_require($this->mws->config, 'endpoint'), $this->parameters, array(
                //'headers' => $this->headers,
                'as_array' => true,
            ));
            return $response;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDataResponse() { 
        //if (isset($this->headers['Content-MD5']) == false) { 
            //$this->headers['Content-MD5'] = null;
        //}
        //$this->headers['Transfer-Encoding'] = 'chunked';

        require_value($this->action);
        require_value($this->parameters);

        $this->parameters['Action'] = $this->action;

        if ($this->data) {
            $response = CURL::post_xml(get_and_require($this->mws->config, 'endpoint'), $this->data, array(
                //'headers' => $this->headers,
                'query_arguments' => $this->parameters,
            ));
        } else { 
            $response = CURL::post(get_and_require($this->mws->config, 'endpoint'), $this->parameters, array(
                //'headers' => $this->headers,
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
        $url = parse_url(get_and_require($this->mws->config, 'endpoint'));
        $host = $url['host'];
        if (isset($url['port']) && !is_null($url['port'])) {
            $host .= ':' . $url['port'];
        }

        $data = 'POST';
        $data .= "\n";
        $data .= $host;
        $data .= "\n";
        if ($queue_path) {
            $uri  = $queue_path;
        } else {
            $uri = "/";
        }
        $data .= implode('/', array_map(array($this, 'urlencode'), explode('/', $uri)));
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

}

///////////////////////////////////////////////////////////////////////////////////////////////////

