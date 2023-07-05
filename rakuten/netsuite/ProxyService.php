<?php

namespace Netsuite;
use App;
use Exception;

require_once 'ServiceService.php';

class ProxyService
{
    private static function _init()
    {
        
    }
    
    public function setSearchPreferences($i, $i)
    {
        
    }
    
    public function update($request) 
    {
        ServiceService::pushRequest('update', $request);
        $response = ServiceService::popResponse();
        if ($response instanceof exception) {
            throw $response;
        }
        return $response;
    }

    public function get($request) 
    {
        ServiceService::pushRequest('get', $request);
        $response = ServiceService::popResponse();
        if ($response instanceof exception) {
            throw $response;
        }
        return $response;
    }

    public function add($request) 
    {
        ServiceService::pushRequest('add', $request);
        $response = ServiceService::popResponse();
        if ($response instanceof exception) {
            throw $response;
        }
        return $response;
    }

    public function search($request)
    {
        ServiceService::pushRequest('search', $request);
        $response = ServiceService::popResponse();
        if ($response instanceof exception) {
            throw $response;
        }
        return $response;
    }
    
    public function searchCustomField($request)
    {
        ServiceService::pushRequest('search', $request);
        $response = ServiceService::popResponse();
        if ($response instanceof exception) {
            throw $response;
        }
        return $response;
    }
}

