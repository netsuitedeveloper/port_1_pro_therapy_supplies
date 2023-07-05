<?php

namespace Netsuite;

class ServiceService
{
    public static $_service;
    
    private static $_response;
    private static $_request;
    
    public static function popResponse()
    {
        self::_initClient();
        
        self::_waitForResponse();
        rewind(self::$_response);
        $str = '';
        while ( ($line = fgets(self::$_response)) ) {
            $str .= $line;
        }
        $parameters = unserialize($str);
        
        return $parameters;
    }
    
    public static function pushRequest($operation, $request)
    {
        self::_initClient();
        
        self::_lockRequest();
        
        $parameters = array('operation' => $operation, 'request' => $request);
        $str = serialize($parameters);
        ftruncate(self::$_request, 0);
        rewind(self::$_request);
        fwrite(self::$_request, $str);
        fflush(self::$_request);
    }
    
    private static function _initClient()
    {
        if (!self::$_request) {
            self::$_request = fopen('c:\\myspace\\tmp\\php-stream-request.dat', 'w');
            if (!self::$_request) {
                throw new Exception('No file stream');
            }
        }
        if (!self::$_response) {
            self::$_response = fopen('c:\\myspace\\tmp\\php-stream-response.dat', 'r');
            if (!self::$_response) {
                throw new Exception('No file stream for reading');
            }
        }
    }
    
    private static function _waitForRequest()
    {
        while (!file_exists('c:\\myspace\\tmp\\php-stream-lock.dat')) {
            sleep(1);
        }
    }
    
    private static function _waitForResponse()
    {
        while (file_exists('c:\\myspace\\tmp\\php-stream-lock.dat')) {
            sleep(1);
        }
    }
    
    private static function _lockRequest()
    {
        while (file_exists('c:\\myspace\\tmp\\php-stream-lock.dat')) {
            sleep(1);
        }
        file_put_contents('c:\\myspace\\tmp\\php-stream-lock.dat', '1');
    }
    
    private static function _unlockRequest()
    {
        unlink('c:\\myspace\\tmp\\php-stream-lock.dat');
    }
    
}


