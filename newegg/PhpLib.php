<?php

if (!@class_exists('PDEBUG')) {

    class PDEBUG {
        
    }

}

if (!@APP::getClassConstant('PDEBUG', 'NO_GLOBALS')) {

    function get() {
        return call_user_func_array('APP::get', func_get_args());
    }

    function iget() {
        return call_user_func_array('APP::iget', func_get_args());
    }

    function set() {
        return call_user_func_array('APP::set', func_get_args());
    }

    function iset() {
        return call_user_func_array('APP::iset', func_get_args());
    }

    function has() {
        return call_user_func_array('APP::has', func_get_args());
    }

}

if (!@class_exists('HTTP')) {

    class HTTP {

        public static function upload($name) {
            $entry = get($_FILES, $name);
            if (!$entry) {
                throw new CustomException('Missing file upload for ', $name);
            }
            return file_get_contents(get($_FILES, array($name, 'tmp_name')));
        }

        public static function uploadCSV($name, $separator = ',', $with_header = true) {
            $entry = get($_FILES, $name);
            if (!$entry) {
                throw new CustomException('Missing file upload for ', $name);
            }
            if (stripos(get($_FILES, array($name, 'type')), 'text/x-comma-separated-values') !== false) {
                return CSV::read(get($_FILES, array($name, 'tmp_name')), $separator, $with_header);
            } else {
                throw new CustomException('Mime type does not look like CSV: ', get($_FILES, array($name, 'type')));
            }
        }

        public static function download($uri, $content_type, $file_name = null) {
            APP::reqFile($uri);
            APP::reqValue($content_type);

            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Description: File Transfer');
            header("Content-type: " . $content_type); //text/csv");
            header("Content-Disposition: attachment; fileName=" . ($file_name ? $file_name : basename($uri)));
            header("Expires: 0");
            header("Pragma: public");

            echo file_get_contents($uri);
            exit;
        }

    }

}

class STRING {

    public static function split($str, $len) {
        $groups = array();
        while ($str) {
            $groups[] = substr($str, 0, min($len, strlen($str)));
            $str = substr($str, min($len, strlen($str) + 1));
        }
        return $groups;
    }

    public static function trimEnd($str, $character_array_or_number_of_characters_or_string = false, $case_sensitive = true) {
        if ($character_array_or_number_of_characters_or_string === false) {
            $str = rtrim($str);
        } else if (is_numeric($character_array_or_number_of_characters_or_string)) {
            $str = substr($str, 0, strlen($str) - intval($character_array_or_number_of_characters_or_string));
        } else if (is_array($character_array_or_number_of_characters_or_string)) {
            $str = rtrim($str, $character_array_or_number_of_characters_or_string);
        } else if (is_string($character_array_or_number_of_characters_or_string)) {
            if (STRING::endsWith($str, $character_array_or_number_of_characters_or_string, $case_sensitive)) {
                $str = substr($str, 0, self::lastIndexOf($str, $character_array_or_number_of_characters_or_string, $case_sensitive));
            }
        }
        return $str;
    }

    public static function endsWith($str, $sub_string, $case_sensitive = true) {
        return self::lastIndexOf($str, $sub_string, $case_sensitive) === strlen($str) - strlen($sub_string);
    }

    public static function toLowercaseSeparatedByUnderscore($str) {
        if (!$str) {
            return $str;
        }
        if (stripos($str, ' ') !== false) {
            throw new CustomException('Function with whitespace in string not supported');
        }
        $prepared = '';
        $uppercased = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $chr = $str[$i];
            if (strtoupper($chr) == $chr) {
                $uppercased++;
            } else {
                $uppercased = 0;
            }
            if ($uppercased > 1) {
                $prepared .= strtolower($chr);
            } else {
                $prepared .= $chr;
            }
        }

        $str = lcfirst($str);
        $str = strtolower(preg_replace('/(.)([A-Z])/', '$1_$2$3', $prepared));

        return $str;
    }

    public static function toCamelCase($str, $uppercase_first_character = false) {
        if (!$str) {
            return $str;
        }
        if (stripos($str, ' ') !== false) {
            throw new CustomException('Function with whitespace in string not supported');
        }
        $str = str_ireplace('_', ' ', $str);
        $str = str_ireplace('-', ' ', $str);
        $str = ucwords($str);
        $str = str_ireplace(' ', '', $str);
        if (!$uppercase_first_character) {
            $str = lcfirst($str);
        }
        return $str;
    }

    public static function fromLast($str, $end, $case_sensitive = true) {
        $last_index_of = self::lastIndexOf($str, $end, $case_sensitive);
        if ($last_index_of !== false) {
            return substr($str, $last_index_of + 1);
        }
        return '';
    }

    public static function untilLast($str, $end, $case_sensitive = true) {
        $last_index_of = self::lastIndexOf($str, $end, $case_sensitive);
        if ($last_index_of !== false) {
            return substr($str, 0, $last_index_of);
        }
        return '';
    }

    public static function until($str, $end, $case_sensitive = true) {
        $splits = self::splitBy($str, $end, $case_sensitive);
        return $splits ? $splits[0] : $str;
    }

    public static function inBetween($str, $start, $end = false, $case_sensitive = true) {
        if (!$str) {
            return $str;
        }
        APP::reqValue($start);

        $pos = is_numeric($start) ? $start : self::indexOf($str, $start, $case_sensitive);
        if ($pos === false) {
            return false;
        }
        $str = substr($str, $pos + (!is_numeric($start) ? strlen($start) : 0));
        $pos = $end !== false ? self::indexOf($str, $end, $case_sensitive) : strlen($str);
        if ($pos === false) {
            return false;
        }
        return substr($str, 0, $pos);
    }

    public static function contains($str, $sub_string, $case_sensitive = true) {
        return self::indexOf($str, $sub_string, $case_sensitive) !== false;
    }

    public static function lastIndexOf($str, $sub_string, $case_sensitive = true) {
        if (!$case_sensitive) {
            $str = strtolower($str);
            $sub_string = strtolower($sub_string);
        }
        return strpos($str, $sub_string);
    }

    public static function indexOf($str, $sub_string, $case_sensitive = true) {
        return $case_sensitive ? strpos($str, $sub_string) : stripos($str, $sub_string);
    }

    public static function splitBy($str, $bys, $case_sensitive = true, $remove_empty_parts = false, &$set_of_used_bys = false) {
        if (!is_string($str)) {
            throw new InvalidArgumentException('Invalid str');
        }
        if (!$str) {
            return array($str);
        }
        $bys = APP::toArray($bys);
        if (!$bys) {
            return array($str);
        }
        $parts = array();
        do {
            $pos = array();
            foreach ($bys as $by) {
                $pos[$by] = $case_sensitive ? strpos($str, $by) : stripos($str, $by);
            }
            $cpos = false;
            $cby = false;
            foreach ($pos as $by => $po) {
                $po = $po === false ? PHP_INT_MAX : $po;
                $cpos = $cpos === false ? PHP_INT_MAX : $cpos;
                $cpos_before_min = $cpos;
                $cpos = min($cpos, $po);
                if ($po < $cpos_before_min || $po < PHP_INT_MAX && $po <= $cpos_before_min && $cby === false) {
                    $cby = $by;
                }
            }
            if ($cpos === false || $cpos === PHP_INT_MAX) {
                if (!$remove_empty_parts || $remove_empty_parts && $str) {
                    array_push($parts, $str);
                }
                break;
            } else {
                $p = substr($str, 0, $cpos);
                if (!$remove_empty_parts || $remove_empty_parts && $p) {
                    array_push($parts, $p);
                }
                $str = substr($str, $cpos + strlen($cby));
                if ($set_of_used_bys !== false) {
                    $set_of_used_bys[] = $cby;
                }
            }
        } while ($str !== false);

        return $parts;
    }

}

class PATH {

    public static function matchAll($uris, $filter) {
        if (!$uris) {
            return false;
        }
        APP::reqValue($filter);
        $uris = APP::toArray($uris);
        $expr = str_ireplace('*', '.*', str_ireplace('.', '\\.', $filter));
        for ($i = 0; $i < count($uris); $i++) {
            $match = preg_match("/$expr/", $uris[$i]);
            if ($match === false) {
                throw new CustomException('Unable to filter ', $uris, ': Invalid filter');
            } else if (!$match) {
                return false;
            }
        }
        return true;
    }

    public static function matchAny($uris, $filter) {
        if (!$uris) {
            return false;
        }
        APP::reqValue($filter);
        $uris = APP::toArray($uris);
        $expr = str_ireplace('*', '.*', str_ireplace('.', '\\.', $filter));
        for ($i = 0; $i < count($uris); $i++) {
            $match = preg_match("/$expr/", $uris[$i]);
            if ($match === false) {
                throw new CustomException('Unable to filter ', $uris, ': Invalid filter');
            } else if ($match) {
                return true;
            }
        }
        return false;
    }

    public static function filter($uris, $filter) {
        if (!$uris) {
            return $uris;
        }
        if (ARR::isAssociativeArray($uris)) {
            throw new CustomException('Associative arrays not supported');
        }
        APP::reqValue($filter);
        $uris = APP::toArray($uris);
        $count = count($uris);
        $expr = str_ireplace('*', '.*', str_ireplace('.', '\\.', $filter));
        for ($i = 0; $i < $count; $i++) {
            $match = preg_match("/$expr/", $uris[$i]);
            if ($match === false) {
                throw new CustomException('Unable to filter ', $uris, ': Invalid filter');
            } else if (!$match) {
                unset($uris[$i]);
            }
        }
        return array_values($uris);
    }

    public static function split($path) {
        return STRING::splitBy($path, array('/', '\\'), false, true);
    }

    public static function combine() {
        $path = '';
        $ds = '';
        for ($i = 0; $i < func_num_args(); $i++) {
            $arg = func_get_arg($i);
            $arg = rtrim($arg, '/\\');
            if (stripos($arg, '/') === 0 || stripos($arg, '\\') === 0) {
                if ($i > 0) {
                    $arg = substr($arg, 1);
                }
            }
            $path .= $ds;
            $path .= $arg;
            $ds = '/';
        }
        return $path;
    }

    public static function enumerateFiles($path, $filter = '*') {
        APP::reqValue($path);
        if (!file_exists($path)) {
            throw new CustomException('Directory ', $path, ' not exists');
        }
        $dh = opendir($path);
        if (!$dh) {
            throw new CustomException('Unable to open directory handle to ', $path);
        }
        $uris = array();
        while (($filename = readdir($dh)) !== false) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (PATH::matchAny($filename, $filter)) {
                $uris[] = PATH::combine($path, $filename);
            }
        }
        closedir($dh);
        return $uris;
    }

    public static function deleteFiles($path, $filter = '*') {
        foreach(PATH::enumerateFiles($path, $filter) as $uri) {
            if (unlink($uri) === false) {
                throw new CustomException('Unable to delete ', $uri);
            }
        }
    }

}

class FILE {

    public static function toName($name) {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    }

    public static function touch($uri, $must_exist = true) {
        if ($must_exist && !file_exists($uri)) {
            throw new CustomException('Trying to touch a non-existend file ', $uri, ' with must_exist set');
        }
        if (touch($uri) === false) {
            throw new CustomException('Unable to touch ', $uri);
        }
    }

    public static function getLastModified($uri) {
        if (!($fp = fopen($uri, 'r'))) {
            throw new CustomException('Unable to open ', $uri, ' for reading');
        }
        $stat = fstat($fp);
        $last_ex = false;
        try {
            $time = DT::toString(DT::parseUnixTime($stat['mtime']));
        } catch (Exception $ex) {
            $last_ex = $ex;
        }
        fclose($fp);
        if ($last_ex) {
            throw $last_ex;
        }
        return $time;
    }

    public static function getAccessTime($uri) {
        if (!($fp = fopen($uri, 'r'))) {
            throw new CustomException('Unable to open ', $uri, ' for reading');
        }
        $stat = fstat($fp);
        $last_ex = false;
        try {
            $time = DT::toString(DT::parseUnixTime($stat['atime']));
        } catch (Exception $ex) {
            $last_ex = $ex;
        }
        fclose($fp);
        if ($last_ex) {
            throw $last_ex;
        }
        return $time;
    }

    public static function getSize($uri) {
        if (!($fp = fopen($uri, 'r'))) {
            throw new CustomException('Unable to open ', $uri, ' for reading');
        }
        $stat = fstat($fp);
        $last_ex = false;
        try {
            $last_modified_from = DT::toString(DT::parseUnixTime($stat['size']));
        } catch (Exception $ex) {
            $last_ex = $ex;
        }
        fclose($fp);
        if ($last_ex) {
            throw $last_ex;
        }
        return $last_modified_from;
    }

    public static function getCreatedAt($uri) {
        if (!($fp = fopen($uri, 'r'))) {
            throw new CustomException('Unable to open ', $uri, ' for reading');
        }
        $stat = fstat($fp);
        $last_ex = false;
        try {
            $time = DT::toString(DT::parseUnixTime($stat['ctime']));
        } catch (Exception $ex) {
            $last_ex = $ex;
        }
        fclose($fp);
        if ($last_ex) {
            throw $last_ex;
        }
        return $time;
    }

    public static function basename($uri) {
        $pos1 = strripos($uri, '/');
        $pos2 = strripos($uri, '\\');
        if ($pos1 !== false && $pos2 !== false) {
            $pos = max($pos1, $pos2);
        } else if ($pos1 !== false) {
            $pos = $pos1;
        } else if ($pos2 !== false) {
            $pos = $pos2;
        } else {
            $pos = false;
        }
        if ($pos === strlen($uri)) {
            return '';
        }
        if ($pos === false) {
            return $uri;
        } else {
            $basename = substr($uri, $pos + 1);
            return $basename;
        }
    }

    public static function delete($uri) {
        $retries = 1;
        do {
            $ok = unlink($uri);
            if ($ok === false) {
                sleep($retries * 2);
            }
        } while ($ok === false && $retries++ < 4);

        if (!$ok) {
            throw new CustomException('Unable to delete file at ', $uri);
        }
    }

    public static function writeToTemporary($data) {
        $uri = tempnam('/temp', 'php-lib');
        if (file_put_contents($uri, $data) === false) {
            throw new CustomException('Unable to write to temporary file');
        }
        return $uri;
    }

    public static function readAllLines($uri) {
        $data = file_get_contents($uri);
        if ($data === false) {
            throw new CustomException('Unable to read file at ', $uri);
        }
        if (stripos($data, '﻿') === 0) {
            $data = substr($data, 3); // bom has 3 characters in ascii (?)
        }
        $index1 = stripos($data, "\r\n");
        $index2 = stripos($data, "\n");

        $separator = $index1 !== false ? "\r\n" : "\n";

        $lines = explode($separator, $data);

        return $lines;
    }

}

class FTP {

    const MODE_ASCII = FTP_ASCII;
    const MODE_BINARY = FTP_BINARY;

    private static $_config;

    public static function init($config) {
        self::$_config = $config;
    }

    public static function move($ftp, $remote_uri_source, $remote_uri_target, $mode) {
        APP::req($ftp, 'host');
        APP::req($ftp, 'username');
        APP::req($ftp, 'password');

        $temp_uri = tempnam('/tmp', 'ftp-move');
        self::download($ftp, $remote_uri_source, $temp_uri, $mode);
        self::upload($ftp, $temp_uri, $remote_uri_target, $mode);
        self::delete($ftp, $remote_uri_source);
        @unlink($temp_uri);
    }

    public static function downloadDir($ftp, $remote_dir, $local_dir, $mode, $filter = '*') {
        APP::req($ftp, 'host');
        APP::req($ftp, 'username');
        APP::req($ftp, 'password');

        $stream = self::connect($ftp);

        self::cd($stream, $remote_dir);

        $list = PATH::filter(ftp_nlist($stream, '.'), $filter);

        if ($list) {
            foreach ($list as $remote_uri) {
                $local_uri = PATH::combine($local_dir, FILE::basename($remote_uri));
                self::get($stream, $remote_uri, $local_uri, $mode);
            }
        }
        ftp_close($stream);

        return $list;
    }

    public static function cd($stream, $remote_path) {
        APP::reqValue($stream);

        $paths = PATH::split($remote_path);
        if ($paths) {
            foreach ($paths as $path) {
                if (ftp_chdir($stream, $path) === false) {
                    throw new CustomException('Unable to change to directory ', $path, ' in ', $remote_path);
                }
            }
        }
    }

    public static function nlist($ftp, $remote_path, $filter = '*') {
        APP::req($ftp, 'host');
        APP::req($ftp, 'username');
        APP::req($ftp, 'password');

        $stream = self::connect($ftp);
        $list = PATH::filter(ftp_nlist($stream, $remote_path), $filter);
        ftp_close($stream);

        return $list;
    }

    public static function download($ftp, $remote_uri, $local_uri, $mode) {
        APP::req($ftp, 'host');
        APP::req($ftp, 'username');
        APP::req($ftp, 'password');

        $stream = self::connect($ftp);
        self::get($stream, $remote_uri, $local_uri, $mode);

        ftp_close($stream);
    }

    public static function upload($ftp, $local_uri, $remote_uri, $mode) {
        APP::req($ftp, 'host');
        APP::req($ftp, 'username');
        APP::req($ftp, 'password');

        if (!file_exists($local_uri)) {
            throw new CustomException('Unable to upload to ftp: file not exists (', $local_uri, ')');
        }
        $stream = self::connect($ftp);

        self::put($stream, $local_uri,  $remote_uri, $mode);

        ftp_close($stream);
    }

    public static function get($stream, $remote_uri, $local_uri, $mode) {
        APP::reqValue($stream);

        $retries = 1;
        do {
            $ok = ftp_get($stream, $local_uri, $remote_uri, $mode);
            if ($ok === false && !APP::get(self::$_config, 'retries', 4)) {
                sleep($retries * 2);
            }
        } while ($ok === false && $retries++ < APP::get(self::$_config, 'retries', 4));

        if (!$ok) {
            throw new CustomException('Unable to download ', $local_uri, ' from ftp ', $remote_uri);
        }
    }

    public static function put($stream, $local_uri, $remote_uri, $mode) {
        APP::reqValue($stream);

        $retries = 1;
        do {
            $ok = ftp_put($stream, $remote_uri, $local_uri, $mode);

            if ($ok === false && !APP::get(self::$_config, 'retries', 4)) {
                sleep($retries * 2);
            }
        } while ($ok === false && $retries++ < APP::get(self::$_config, 'retries', 4));

        if (!$ok) {
            throw new CustomException('Unable to upload ', $local_uri, ' to ftp ', $remote_uri);
        }
    }

    public static function delete($ftp, $remote_uri) {
        APP::req($ftp, 'host');
        APP::req($ftp, 'username');
        APP::req($ftp, 'password');

        $stream = self::connect($ftp);

        $retries = 1;
        do {
            $ok = ftp_delete($stream, $remote_uri);
            if ($ok === false && !APP::get(self::$_config, 'retries', 4)) {
                sleep($retries * 2);
            }
        } while ($ok === false && $retries++ < APP::get(self::$_config, 'retries', 4));

        if (!$ok) {
            throw new CustomException('Unable to delete ', $remote_uri, ' from ftp ', $ftp);
        }

        ftp_close($stream);
    }

    public static function connect($ftpOrHost) {
        $host = APP::get($ftpOrHost, 'host', $ftpOrHost);
        APP::reqValue($host);

        $retries = 1;
        do {
            $stream = ftp_connect($host);
            if ($stream === false && !APP::get(self::$_config, 'retries', 4)) {
                sleep($retries * 10);
            }
        } while ($stream === false && $retries++ < APP::get(self::$_config, 'retries', 4));

        if (!$stream) {
            throw new CustomException('Unable to connect to FTP ', $ftpOrHost);
        }
        if (APP::get($ftpOrHost, 'username')) {
            if (ftp_login($stream, $ftpOrHost['username'], $ftpOrHost['password']) === false) {
                throw new CustomException('Unable to login to ', $ftpOrHost);
            }
        }
        return $stream;
    }

}

class DT {

    const UTC = 'utc';

    public static function parseUnixTime($unix_time) {
        return new DateTime(date('c', $unix_time));
    }

    public static function getNow($date_time_zone = null) {
        if (is_string($date_time_zone)) {
            $date_time_zone = new DateTimeZone($date_time_zone);
        }
        $dt = new DateTime('now', $date_time_zone);
        return $dt;
    }

    public static function getToday($date_time_zone = null) {
        if (is_string($date_time_zone)) {
            $date_time_zone = new DateTimeZone($date_time_zone);
        }
        $dt = new DateTime('today', $date_time_zone);
        return $dt;
    }

    public static function getPrettyNowString($date_time_zone = null) {
        return self::toPrettyString(self::getNow($date_time_zone));
    }

    public static function getTodayString($date_time_zone = null) {
        return self::toShortString(self::getToday($date_time_zone));
    }

    public static function getNowString($date_time_zone = null) {
        return self::toString(self::getNow($date_time_zone));
    }

    public static function getYesterdayString($date_time_zone = null) {
        return self::toString(self::getYesterday($date_time_zone));
    }

    public static function getYesterday($date_time_zone = null) {
        return self::getBefore('1 day', $date_time_zone);
    }

    public static function getBefore($date_interval_str, $date_time_zone = null) {
        $dt = self::getNow($date_time_zone);
        $dt->sub(DateInterval::createFromDateString($date_interval_str));
        return $dt;
    }

    public static function toShortString($date_time) {
        if (is_string($date_time)) {
            throw new CustomException('toShortString must be used with DateTime');
        }
        return $date_time->format('Y-m-d');
    }

    public static function toString($date_time) {
        if (is_string($date_time)) {
            return $date_time;
        }
        return $date_time->format('c');
    }

    public static function toPrettyString($date_time) {
        if (is_string($date_time)) {
            $data_time = new DateTime($date_time);
        }
        return $date_time->format('Y-m-d H:i:s');
    }

}

class ZIP {

    public static function unzipFirstFile($zip_uri) {
        $zip = zip_open($zip_uri);
        if (!$zip) {
            throw new CustomException('Unable to open zip file at ', $zip_uri);
        }

        while ( ($zip_entry = zip_read($zip)) !== false) {
            if (!zip_entry_open($zip, $zip_entry)) {
                throw new CustomException('Unable to open zip entry ', $zip_entry, ' in file ', $zip_uri);
            }
            $length = zip_entry_filesize($zip_entry);
            $zip_entry_data = zip_entry_read($zip_entry, $length);
            if (!$zip_entry_data && $length > 0) {
                throw new CustomException('No zip data for ', $zip_entry, ' in file ', $zip_uri);
            }
            zip_entry_close($zip_entry);

            break;
        }

        zip_close($zip);

        return $zip_entry_data;
    }

}

class CSV {

    public static function getEmpty() {
        return array('header' => array(), 'rows' => array());
    }

    public static function readString($str, $separator = ',', $with_header = true) {
        if (!$str) {
            throw new InvalidArgumentException('No CSV content');
        }
        $uri = FILE::writeToTemporary($str);

        return self::read($uri, $separator, $with_header);
    }

    public static function read($uri, $separator = ',', $with_header = true) {
        if (!file_exists($uri)) {
            throw new InvalidArgumentException('Input URI ' . $uri . ' does not exist');
        }
        $stream = fopen($uri, 'r');
        if (!$stream) {
            throw new InvalidArgumentException('Unable to open read stream for ' . $uri);
        }
        $header = false;

        $rows_before_header = array();
        if ($with_header) {
            if (is_numeric($with_header)) {
                for ($i = 0; $i < intval($with_header)-1; $i++) {
                    $rows_before_header[] = fgetcsv($stream, NULL, $separator);
                }
            }
            $header = fgetcsv($stream, NULL, $separator);
            if (!$header) {
                throw new InvalidArgumentException('Missing header in CSV file');
            }
        }
        $rows = array();

        do {
            $row = fgetcsv($stream, NULL, $separator);
            if ($row && $row[0] === NULL) {
                continue;
            }
            if ($row !== false) {
                for ($i = 0; $i < min(count($row), count($header)); $i++) {
                    $row[$header[$i]] = $row[$i];
                }
                $rows[] = $row;
            }
        } while ($row !== false);

        @fclose($stream);

        return array('header' => $header, 'rows' => $rows, 'rows_before_header' => $rows_before_header);
    }

    public static function writeObjects($uri, Array $objects, $separator = ',') {
        $csv = array();
        if ($objects) {
            if (ARR::isAssociativeArray($objects)) {
                $objects = array_values($objects);
            }
            if (!ARR::isNumericArray($objects)) {
                throw new CustomException('Models must be numeric array');
            }
        }
        $csv['header'] = $objects ? array_keys((array) $objects[0]) : array();
        $csv['rows'] = $objects;
        return self::write($uri, $csv, $separator);
    }

    public static function readNumericArrays($uri, $separator = ',', $with_header = true) {
        $csv = self::read($uri, $separator, $with_header);
        if ($csv['rows']) {
            $rows = $csv['rows'];
            foreach ($rows as &$row) {
                $row = ARR::toNumericArray($row);
                if ($row) {
                    $row = array_pop($row);
                }
            }
            $csv['rows'] = $rows;
        }
        return $csv;
    }

    public static function readObjects($uri, $class_name = null, $separator = ',', $with_header = true) {
        $csv = self::read($uri, $separator, $with_header);
        if ($csv['rows']) {
            $rows = $csv['rows'];
            $csv['rows'] = ARR::toAssociativeArray($rows);
            $csv['rows'] = ARR::toObjects($csv['rows'], $class_name);
        }
        return $csv;
    }

    public static function append($uri, Array $csv, $separator = ',', $with_header = true) {
        $current_csv = file_exists($uri) ? self::read($uri, $separator, $with_header) : array();
        $current_csv['header'] = get($current_csv, 'header') ? $current_csv['header'] : $csv['header'];
        $current_csv['rows'] = array_merge(get($current_csv, 'rows', array()), $csv['rows']);

        self::write($uri, $current_csv, $separator);
    }

    public static function write($uri, Array $csv, $separator = ',') {
        APP::req($csv, 'header');
        APP::req($csv, 'rows');
        $stream = fopen($uri, 'w');
        if (!$stream) {
            throw new InvalidArgumentException('Unable to open write stream for ' . $uri);
        }
        if ($rows_before_header = APP::get($csv, 'rows_before_header')) {
            foreach($rows_before_header as $row_before_header) {
                if (fputcsv($stream, $row_before_header, $separator) === false) {
                    throw new CustomException('Unable to write row before header to ', $uri);
                }
            }
        }
        if ($header = APP::get($csv, 'header')) {
            if (fputcsv($stream, $header, $separator) === false) {
                throw new CustomException('Unable to write header to ', $uri);
            }
        }
        if ($rows = APP::get($csv, 'rows')) {
            if ($rows) {
                foreach ($rows as $row) {
                    $row = ARR::toNumericArray($row, true);
                    if (fputcsv($stream, $row, $separator) === false) {
                        throw new CustomException('Unable to write row ', $row, ' to ', $uri);
                    }
                }
            }
        }
        @fflush($stream);
        @fclose($stream);
    }

}

class XML {

    public static function prettyPrint($string_or_xml) {
        if (is_string($string_or_xml)) {
            $xml = self::fromString($string_or_xml);
        } else if ($string_or_xml instanceof SimpleXMLElement) {
            $xml = $string_or_xml;
        } else {
            throw new CustomException('Argument must be string or simple xml');
        }
        $dom = new DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $pretty_string = $dom->saveXML();
        return $pretty_string;
    }

    public static function xpath($xml, $expr) {
        if (is_string($xml)) {
            $xml = simplexml_load_string($xml);
        }
        $res = $xml->xpath($expr);
        return $res;
    }

    public static function xpathValue($xml, $expr) {
        $res = self::xpath($xml, $expr);
        $value = false;
        if (is_array($res) && $res) {
            foreach ($res as $element) {
                $value[] = html_entity_decode((string) $element, ENT_QUOTES);
            }
        }
        if (is_array($value) && count($value) == 1) {
            $value = $value[0];
        }
        return $value;
    }

    public static function copy($source, $target) {

        $target = $target->addChild($source->getName(), trim((string) $source));

        $iterator = new SimpleXMLIterator($source->asXML());
        $iterator->rewind();
        if ($iterator->valid()) {
            for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
                $current = $iterator->current();
                if ($current->children()) {
                    $name = $current->getName();
                    $childTarget = $target->addChild($current->getName());
                    foreach ($current->children() as $child) {
                        self::copy($child, $childTarget);
                    }
                } else {
                    $target->addChild($current->getName(), trim((string) $current));
                }
            }
        }
        return $target;
    }

    public static function toArray(&$stringOrElement) {
        if (is_string($stringOrElement)) {
            $stringOrElement = simplexml_load_string($stringOrElement);
        }
        $stringOrElement = (array) $stringOrElement;
        if ($stringOrElement) {
            foreach ($stringOrElement as &$value) {
                if (is_object($value)) {
                    if (method_exists($value, 'attributes')) {
                        foreach ($value->attributes() as $attrName => $attrValue) {
                            $value->$attrName = $attrValue;
                        }
                    }
                }
                if (is_object($value)) {
                    $value = self::toArray($value);
                } else if (is_array($value)) {
                    $value = self::toArray($value);
                }
            }
        }
        return $stringOrElement;
    }

    public static function write($uri, $data, $root_tag = null) {
        APP::reqValue($uri);
        $xml = self::parse($data, $root_tag);
        if ($xml->asXML($uri) === false) {
            throw new CustomException('Unable to write ', $data, ' to ', $uri);
        }
    }

    public static function read($uri) {
        APP::reqValue($uri);

        $xml = simplexml_load_file($uri, 'SimpleXMLElement', LIBXML_NOCDATA);

        return self::toArray($xml);
    }

    public static function parse($arr_or_obj, $tag = null, $root = null) {
        if (ARR::isAssociativeArray($arr_or_obj) || is_object($arr_or_obj)) {
            if ($root === null) {
                $root = self::parse('', $tag);
            } else {
                $root = $root->addChild($tag === null ? 'root' : $tag);
            }
            foreach ($arr_or_obj as $name => $value) {
                self::parse($value, $name, $root);
            }
        } else if (ARR::isNumericArray($arr_or_obj)) {
            if ($root === null) {
                $root = self::parse('', $tag);
            }
            foreach ($arr_or_obj as $idx => $value) {
                self::parse($value, $tag, $root);
            }
        } else {
            if ($root === null) {
                $tag = $tag === null ? 'root' : $tag;
                $root = new SimpleXMLElement("<$tag>$arr_or_obj</$tag>");
            } else {
                $root->addChild($tag === null ? 'root' : $tag, $arr_or_obj);
            }
        }
        return $root;
    }

    public static function print_r($str, $return = false) {
        if (is_string($str)) {
            $str = simplexml_load_string($str);
        }
        return print_r($str, $return);
    }

    public static function dumpElement($root) {
        self::_dump($root);
    }

    public static function dump($str) {
        Log::debug(self::dumpToString($str));
    }

    public static function dumpToString($str) {
        $root = new SimpleXMLElement($str);
        return self::_dumpToString($root);
    }

    private static function _dumpToString($element, $level = 0) {
        $prefix = '';
        for ($i = 0; $i < $level; $i++) {
            $prefix .= "\t";
        }
        $str = '';
        if ($element->children()) {
            if ($level > 0) {
                $str .= PHP_EOL;
            }
            $str .= $prefix . $element->getName() . PHP_EOL;
            $str .= PHP_EOL;

            $level++;
            $prefix .= "\t";
            foreach ($element->children() as $child) {
                if (!$child->children()) {
                    $str .= $prefix . str_pad($child->getName(), 30, ' ') . ': ' . trim((string) $child) . PHP_EOL;
                }
                $str .= self::_dumpToString($child, $level);
            }
            $str .= PHP_EOL;
        }
        return $str;
    }

    public function toXml($obj, $prefix, $suffix) {
        $this->_getObject2XML($this->xml, $obj);

        $this->xml->endElement();

        $xml = $this->xml->outputMemory(true);
        $xml = $prefix . $xml . $suffix;
        return $xml;
    }

    public function fromString($xmlString) {
        return simplexml_load_string($xmlString);
    }

    private function _getAttribute2XML(XMLWriter $xml, $data) {
        foreach ($data as $key => $value) {
// there should only be one
            $xml->writeAttribute($key, $value);
        }
    }

    private function _getObject2XML(XMLWriter $xml, $data) {
        $jagged = false;
        foreach ($data as $key => $value) {
            $jagged = true;
            if ($key == "attr") {
// we have an attribute for the current element
                $this->_getAttribute2XML($xml, $value);
            } elseif (is_object($value)) {
                $xml->startElement($key);
                $this->_getObject2XML($xml, $value);
                $xml->endElement();
                continue;
            } else if (is_array($value)) {
                $this->getArray2XML($xml, $key, $value);
            }

            if (is_string($value)) {
                $xml->writeElement($key, $value);
            } else if (is_numeric($value)) {
                $xml->writeElement($key, $value);
            }
        }

        if (!$jagged) {
            $xml->writeRaw((string) $data);
        }
    }

    private function getArray2XML(XMLWriter $xml, $keyParent, $data) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $xml->writeElement($keyParent, $value);
                continue;
            }

            if (is_numeric($key)) {
                $xml->startElement($keyParent);
            }

            if (is_object($value)) {
                $this->getObject2XML($xml, $value);
            } else if (is_array($value)) {
                $this->getArray2XML($xml, $key, $value);
                continue;
            }

            if (is_numeric($key)) {
                $xml->endElement();
            }
        }
    }

}

class TEMP {

    private static $_active;
    private static $_config;

    public static function init($config) {
        self::$_config = $config;

        if (APP::get($config, 'active')) {
            self::activate();
        } else if (APP::get($config, 'inactive')) {
            self::deactivate();
        }
    }

    public static function activate() {
        self::$_active = 1;
    }

    public static function deactivate() {
        self::$_active = 0;
    }

    public static function clear($key = false, $own_file = false) {
        if (file_exists(self::_getURI($key, $own_file))) {
            if ($key !== false) {
                $content = unserialize(file_get_contents(self::_getURI($key, $own_file)));
                if (isset($content[$key])) {
                    unset($content[$key]);
                    file_put_contents(self::_getURI($key, $own_file), serialize($content));
                }
            } else {
                unlink(self::_getURI($key, $own_file));
            }
        }
    }

    public static function peek($key = false, $own_file = false) {
        if (self::$_active === 0) {
            return false;
        } else {
            $value = self::_getValue($key, $own_file);
            return !empty($value);
        }
    }

    public static function pushValue($value, $key = false, $own_file = false) {
        self::_putValue($value, $key, $own_file);
        return $value;
    }

    public static function popValue($value = false, $key = false, $own_file = false) {
        if (!self::peek($key, $own_file)) {
            return self::pushValue($value, $key, $own_file);
        } else {
            return self::_getValue($key, $own_file);
        }
    }

    public static function push($delegate, $key = false, $own_file = false) {
        if (is_array($delegate)) {
            $func = $delegate['delegate'];
            $args = $delegate['args'];
            $content = $func($args);
        } else {
            $content = $delegate();
        }
        self::_putValue($content, $key, $own_file);
        return $content;
    }

    public static function pop($delegate = false, $key = false, $own_file = false) {
        if (!self::peek($key, $own_file)) {
            return self::push($delegate, $key, $own_file);
        } else {
            return self::_getValue($key, $own_file);
        }
    }

    private static function _getURI($key = false, $own_file = false) {
        $filename = false;
        if ($own_file) {
            $filename = FILE::toName($key !== false ? $key : '__no_key__');
        }
        $path = APP::get(self::$_config, 'path', __DIR__);
        return PATH::combine($path, ($filename !== false ? $filename . '.dat' : 'temp.dat'));
    }

    private static function _getArray($key = false, $own_file = false) {
        $content = file_exists(self::_getURI($key, $own_file)) ? file_get_contents(self::_getURI($key, $own_file)) : false;
        if (!empty($content)) {
            $content = unserialize($content);
        } else {
            $content = array();
        }
        return $content;
    }

    private static function _putValue($value, $key = false, $own_file = false) {
        if (self::$_active === 0) {
            return;
        } else {
            $content = self::_getArray($key, $own_file);
            $content[$key ? $key : '__no_key__'] = $value;
            file_put_contents(self::_getURI($key, $own_file), serialize($content));
        }
    }

    private static function _getValue($key = false, $own_file = false) {
        if (self::$_active === 0) {
            return;
        } else {
            $content = false;
            if (file_exists(self::_getURI($key, $own_file))) {
                $content = file_get_contents(self::_getURI($key, $own_file));
                if (!empty($content)) {
                    $content = unserialize($content);
                    $content = $key ? APP::get($content, $key) : $content['__no_key__'];
                }
            }
            return $content;
        }
    }

}

class Log {

    const LEVEL_NONE = 0;
    const LEVEL_ERROR = 1; //includes exceptions
    const LEVEL_WARNING = 2;
    const LEVEL_INFO = 3;
    const LEVEL_DEBUG = 4;
    const LEVEL_NETWORK = 5;
    const LEVEL_DB = 6;
    const LEVEL_DATA = 7;

    private static $_handle;
    private static $_config; // level, base, append (default true), limit (in MB)
    private static $_level = Log::LEVEL_ERROR;

    public static function init($config) {
        self::$_config = $config;
        self::$_level = APP::get($config, 'level');
        if (!APP::get(self::$_config, 'append')) {
            self::clear();
        }
    }

    public static function getLogLevel() {
        return self::$_level;
    }

    public static function clear() {
        self::_write('', true);
    }

    public static function welcome() {
        self::info('Starting script ', DT::getNowString());
        self::debug('Working directory: ', getcwd());
        if (APP::get($_SERVER, 'argv')) {
            self::debug('Arguments: ', APP::get($_SERVER, 'argv'));
        }
    }

    public static function info() {
        if (self::$_level >= Log::LEVEL_INFO) {
            self::_prepareAndWrite('info', func_get_args());
        }
    }

    public static function warning() {
        if (self::$_level >= Log::LEVEL_WARNING) {
            self::_prepareAndWrite('warning', func_get_args());
        }
    }

    public static function error() {
        if (self::$_level >= Log::LEVEL_ERROR) {
            self::_prepareAndWrite('error', func_get_args());
        }
    }

    public static function debug() {
        if (self::$_level >= Log::LEVEL_DEBUG) {
            self::_prepareAndWrite('debug', func_get_args());
        }
    }

    public static function data() {
        if (self::$_level >= Log::LEVEL_DATA) {
            self::_prepareAndWrite('data', func_get_args());
        }
    }

    public static function network() {
        if (self::$_level >= Log::LEVEL_NETWORK) {
            self::_prepareAndWrite('network', func_get_args());
        }
    }

    public static function db() {
        if (self::$_level >= Log::LEVEL_DB) {
            self::_prepareAndWrite('db', func_get_args());
        }
    }

    public static function ex() {
        if (self::$_level >= Log::LEVEL_ERROR) {
            $msg = 'exception ';
            if (func_num_args() > 1) {
                $msg .= 'An exception has occured during ' . func_get_arg(1);
                $msg .= ': ';
            }
            if (func_num_args() > 0) {
                $msg .= func_get_arg(0)->getMessage();
            }
            for ($i = 2; $i < func_num_args(); $i++) {
                $msg .= print_r(func_get_arg($i), true);
            }
            self::_write($msg);
        }
    }

    private static function _prepareAndWrite($type, $args) {
        $msg = $type . ' ';
        for ($i = 0; $i < count($args); $i++) {
            $msg .= print_r($args[$i], true);
        }
        self::_write($msg);
    }

    private static function _getURI() {
        return PATH::combine(App::get(self::$_config, 'uri', App::get(self::$_config, 'path', __DIR__)), APP::get(self::$_config, 'name', 'log.txt'));
    }

    private static function _write($msg, $clear = false) {
        $uri = self::_getURI();
        if (!self::$_handle) {
            self::$_handle = @fopen($uri, App::get(self::$_config, 'append') ? 'a' : 'w');
        }

        if (self::$_handle) {
            $stats = fstat(self::$_handle);
            if ($stats) {
                $size = $stats['size'] / (1024 * 1000);
                if ($size > APP::get(self::$_config, 'limit', 10) || $clear) {
                    @fclose(self::$_handle);
                    self::$_handle = @fopen($uri, "w");
                }
            }
        }

        if (self::$_handle && !$clear) {
            $msg = DT::getNowString() . ' ' . $msg;

            @fwrite(self::$_handle, print_r($msg, true) . PHP_EOL);
            @fflush(self::$_handle);
        }
    }

}

class CURL {

    const AUTH_BASIC = 'basic';
    const AUTH_DIGEST = 'digest';
    const AUTH_NTLM = 'ntlm';

    private static $_config;

    public static function init($config) {
        if (APP::get($config, 'log')) {
            self::$_config['log'] = APP::get($config, 'log');
        }
        if (APP::get($config, 'retries') !== false) {
            self::$_config['retries'] = APP::get($config, 'retries');
        }
    }

    public static function get($url, $params = false, $auth = false, $retry = 0) {
        $ch = curl_init();

        $query_url = $url;
        if ($params) {
            $query_url .= '?' . http_build_query($params);
        }
        if ($auth) {
            APP::req($auth, 'username');
            APP::req($auth, 'password');
            APP::req($auth, 'method');
            $auth_value = $auth['username'] . ':' . $auth['password'];
            $auth_method = false;
            $auth_method = $auth['method'] == self::AUTH_BASIC ? CURLAUTH_BASIC : $auth_method;
            $auth_method = $auth['method'] == self::AUTH_DIGEST ? CURLAUTH_DIGEST : $auth_method;
            $auth_method = $auth['method'] == self::AUTH_NTLM ? CURLAUTH_NTLM : $auth_method;
            if ($auth_method === false) {
                throw new InvalidArgumentException('Unsupported HTTP authentication code (supported are: basic, digest, NTML)');
            }
            $auth['method'] = $auth_method;
        }

        curl_setopt($ch, CURLOPT_URL, $query_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth_value);
            curl_setopt($ch, CURLOPT_HTTPAUTH, $auth_method);
        }

        if (APP::get(self::$_config, 'log')) {
            LOG::network($query_url);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (APP::get(self::$_config, 'log')) {
            LOG::network($httpCode);
            LOG::network($response);
        }

        curl_close($ch);

        if ($httpCode != 200 && $httpCode != 201 && $httpCode != 202) {
            if (APP::get(self::$_config, 'retries') !== false && $retry < APP::get(self::$_config, 'retries') || APP::get(self::$_config, 'retries') === false && $retry < 3) {
                sleep(3);
                $response = self::get($url, $params, $auth, $retry + 1);
            } else {
                throw new Exception('Unable to GET ' . $url . ' with ' . print_r($params, true));
            }
        }
        return $response;
    }

    public static function post($url, $params = false, $headers = false, $auth = false, $retry = 0) {
        $ch = curl_init();

        if ($auth) {
            APP::req($auth, 'username');
            APP::req($auth, 'password');
            APP::req($auth, 'method');
            $auth_value = $auth['username'] . ':' . $auth['password'];
            $auth_method = false;
            $auth_method = $auth['method'] == self::AUTH_BASIC ? CURLAUTH_BASIC : $auth_method;
            $auth_method = $auth['method'] == self::AUTH_DIGEST ? CURLAUTH_DIGEST : $auth_method;
            $auth_method = $auth['method'] == self::AUTH_NTLM ? CURLAUTH_NTLM : $auth_method;
            if ($auth_method === false) {
                throw new InvalidArgumentException('Unsupported HTTP authentication code (supported are: basic, digest, NTML)');
            }
            $auth['method'] = $auth_method;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth_value);
            curl_setopt($ch, CURLOPT_HTTPAUTH, $auth_method);
        }

        if (APP::get(self::$_config, 'log')) {
            LOG::debug($url);
            LOG::debug($params);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (APP::get(self::$_config, 'log')) {
            LOG::debug($response);
        }

        curl_close($ch);

        if ($httpCode != 200 && $httpCode != 201 && $httpCode != 202) {
            if (APP::get(self::$_config, 'retries') !== false && $retry < APP::get(self::$_config, 'retries') || APP::get(self::$_config, 'retries') === false && $retry < 3) {
                sleep(3);
                $response = self::post($url, $params, $headers, $auth, $retry + 1);
            } else {
                throw new Exception('Unable to POST to ' . $url . ' with ' . print_r($params, true));
            }
        }
        return $response;
    }

}

class MailClient {

    private static $_config; // array('log' => true, 'do_not_send' => true)

    public static function init($config) {
        self::$_config = $config;
    }

    public static function send($mail) {
        if (is_object($mail)) {
            $mail = (array) $mail;
        }
        if (!isset($mail['from'])) {
            throw new InvalidArgumentException("Missing from for mail");
        }
        if (!isset($mail['to'])) {
            throw new InvalidArgumentException("Missing to for mail");
        }
        if (!isset($mail['subject'])) {
            $mail['subject'] = '';
        }
        if (!isset($mail['body'])) {
            $mail['body'] = '';
        }
        $body = $mail['body'];
        $contentType = isset($mail['content-type']) ? $mail['content-type'] : 'text/plain';
        if (!isset($mail['content-type']) && strpos($body, '<html>')) {
            $contentType = 'text/html';
        }

        $headers = 'MIME-Version: 1.0' . PHP_EOL;
        $headers .= "From: " . $mail['from'] . PHP_EOL;
        if (isset($mail['reply-to'])) {
            $headers .= "Reply-To: " . $mail['reply-to'] . PHP_EOL;
        }
        if (isset($mail['bcc'])) {
            $headers .= "Bcc: " . $mail['bcc'] . PHP_EOL;
        }
        $headers .= 'X-Mailer: PHP/' . phpversion() . PHP_EOL;
        if (!APP::get($mail, 'attachments', false)) {
            $headers .= 'Content-type: ' . $contentType . '; charset=iso-8859-1' . PHP_EOL;
        } else {
            if (!is_array($mail['attachments'])) {
                $mail['attachments'] = array($mail['attachments']);
            }

            $random_hash = md5(date('r', time()));

            $headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-" . $random_hash . "\"";

            $body = "
--PHP-mixed-$random_hash
Content-type: text/html; charset=iso-8859-1'

$body";

            foreach ($mail['attachments'] as $attachment) {
                $attachmentContent = chunk_split(base64_encode(file_get_contents($attachment['path'])));
                $attachmentName = $attachment['name'];
                $attachmentContentType = APP::get($attachment, 'content-type', 'application/xlsx');

                $body .= "
--PHP-mixed-$random_hash
Content-Type: $attachmentContentType; name=$attachmentName
Content-Transfer-Encoding: base64
Content-Disposition: attachment

$attachmentContent";
            }

            $body .= "
--PHP-mixed-$random_hash--";
        }

        if (APP::get(self::$_config, 'log')) {
            Log::debug($mail);
        }

        if (!APP::get(self::$_config, 'do_not_send')) {
            $sent = false;
            $mail['to'] = APP::toArray($mail['to']);
            foreach ($mail['to'] as $to) {
                for ($i = 0; $i < 3; $i++) {
                    if (($sent = mail($to, $mail['subject'], $body, $headers)) !== false) {
                        break;
                    }
                    sleep(3);
                }
                if (!$sent) {
                    throw new CustomException('Unable to send mail to ', $mail['to']);
                }
            }
        }
    }

    public static function sendTemplateMail($mail, $uri, $variable_map = array()) {
        APP::reqFile($uri);

        $mail_content = file_get_contents($uri);
        if (!$mail_content) {
            throw new CustomException('Missing template content from ', $uri);
        }

        $variables = array_keys($variable_map);
        foreach ($variables as $var) {
            $value = APP::get($variable_map, $var, '');
            $mail_content = str_ireplace('{{' . $var . '}}', $value, $mail_content);
        }
        if (($missing_variable = STRING::inBetween($mail_content, '{{', '}}'))) {
            throw new CustomException('Missing variable for ', $missing_variable);
        }

        $mail['body'] = $mail_content;
        self::send($mail);
    }

    public static function sendGroupedTemplateMail($mail, $uri_or_string, $group_variables, $variables, $messages) {
        /**
         * group_variables: ERRORS
         * variables: DATETIME, CONTEXT, MESSAGE
         * template: {{ERRORS-BEGIN}} <tr><td style="width:150px">{{DATETIME}}</td><td>{{CONTEXT}}</td><td>{{MESSAGE}}<td></tr> {{ERRORS-END}}
        **/
        if (file_exists($uri_or_string)) {
            $uri = $uri_or_string;

            $template_content = file_get_contents($uri);
            if (empty($template_content)) {
                throw new CustomException('No mail template content at ', $uri);
            }

        } else {
            $template_content = $uri_or_string;
        }

        foreach ($group_variables as $group_variable) {
            $group_template = preg_replace('/.*\{\{' . $group_variable . '-BEGIN\}\}/s', '', $template_content);
            $group_template = preg_replace('/\{\{' . $group_variable . '-END\}\}.*/s', '', $group_template);
            if (!$group_template) {
                throw new CustomException('No ' . $group_variable . ' template content');
            }
            $group = $messages[$group_variable];
            $group_content = '';
            if ($group) {
                foreach ($group as $message) {
                    $message_content = $group_template;
                    foreach ($variables as $var) {
                        $value = APP::iget($message, $var);
                        $message_content = str_ireplace('{{' . $var . '}}', $value, $message_content);
                    }
                    $group_content .= $message_content . PHP_EOL;
                }
            }
            $template_content = preg_replace('/(.*)\{\{' . $group_variable . '-BEGIN\}\}.*\{\{' . $group_variable . '-END\}\}(.*)/s', '$1' . $group_content . '$2', $template_content);
        }

        $mail['body'] = $template_content;

        self::send($mail);
    }

    public static function getDefaultGroupedTemplateMail() {
        $html = <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            th {
                padding-left: 10px;
                text-align: left;
            }
            td {
                padding-left: 10px;
            }
        </style>
    </head>
    <body>
        <div>
            <table>
                <thead>
                    <tr><th>DateTime</th><th>Context</th><th>Message</th></tr>
                </thead>
                <tbody>
                    {{ERRORS-BEGIN}}
                    <tr><td style="width:150px">{{DATETIME}}</td><td style="width:150px">{{CONTEXT}}</td><td>{{MESSAGE}}<td></tr>
                    {{ERRORS-END}}
                </tbody>
            </table>
        </div>
    </body>
</html>
EOF;

        return $html;
    }

}

class CustomException extends Exception {

    public $wrapped;

    public function __construct() {
        if (func_num_args() == 0) {

        } else if (func_num_args() == 1) {
            $ex = func_get_arg(0);
            if ($ex instanceof Exception) {
                $this->wrapped = $ex;
            } else {
                $this->message = $ex;
            }
        } else {
            $ex = func_get_arg(0);
            if ($ex instanceof Exception) {
                $msg = $ex->getMessage();
                if ($msg) {
                    $msg .= ' ';
                }
                for ($i = 1; $i < func_num_args(); $i++) {
                    $msg .= print_r(func_get_arg($i), true);
                }
                $ex->message = $msg;
                $this->wrapped = $ex;
            } else {
                $msg = '';
                for ($i = 0; $i < func_num_args(); $i++) {
                    $msg .= print_r(func_get_arg($i), true);
                }
                $this->message = $msg;
            }
        }
    }

    public static function throwEx() {
        if (func_num_args() == 0) {
            throw new InvalidArgumentException('Missing exception as first parameter');
        } else if (func_num_args() == 1) {
            $ex = func_get_arg(0);
            throw new $ex();
        } else {
            $ex = func_get_arg(0);
            $msg = '';
            for ($i = 1; $i < func_num_args(); $i++) {
                $msg .= print_r(func_get_arg($i), true);
            }
            throw new $ex($msg);
        }
    }

}

class ARR {

    public static function toObjects($arr, $class_name = null, $case_sensitive = true) {
        if (ARR::isAssociativeArray($arr)) {
            $objects = ($class_name !== null && class_exists($class_name) ? new $class_name() : new stdClass());
            foreach ($arr as $name => $value) {
                if (is_array($value)) {
                    $value = self::toObjects($value, $name);
                } else if (is_object($value)) {
                    $value = self::toObjects($value);
                }
                if ($case_sensitive) {
                    $objects->$name = $value;
                } else {
                    APP::iset($objects, $name, $value);
                }
            }
        } else if (ARR::isNumericArray($arr)) {
            $objects = array();
            foreach ($arr as $element) {
                $objects[] = self::toObjects($element);
            }
        } else if (is_object($arr)) {
            $objects = $arr;
            foreach ($arr as $name => &$value) {
                if (is_array($value)) {
                    $value = self::toObjects($value, $name);
                } else if (is_object($value)) {
                    $value = self::toObjects($value);
                }
            }
        } else {
            $objects = $arr; // keep literal values
        }
        return $objects;
    }

    public static function insert(&$arr, $index, $value) {
        $result = array();
        $result = array_merge($result, array_slice($arr, 0, $index));
        $result[] = $value;
        $result = array_merge($result, array_slice($arr, $index));
        $arr = $result;
        return $result;
    }

    public static function filter($arr, $callback, $user_args = false) {
        $result = array();
        if ($arr) {
            for ($i = 0; $i < count($arr); $i++) {
                if ($callback($arr[$i], $i, $user_args)) {
                    $result[] = $arr[$i];
                }
            }
        }
        return $result;
    }

    public static function wrap($value, $ignoreFalse = true) {
        if (!$ignoreFalse || $value !== false) {
            if (is_object($value)) {
                return array($value);
            } else if (ARR::isAssociativeArray($value)) {
                return array($value);
            } else if (is_array($value)) {
                return $value;
            } else {
                return ARR::isNumericArray($value) ? $value : array($value);
            }
        }
        return $value;
    }

    public static function split($arr, $group_size) {
        $groups = array();
        if (is_array($arr) && count($arr) > 0) {
            if (ARR::isAssociativeArray($arr)) {
                $arr = ARR::toNumericArray($arr);
            }
            $one_group = array();
            for ($i = 0; $i < count($arr); $i++) {
                if ($i > 0 && $i % $group_size == 0) {
                    if ($one_group) {
                        $groups[] = $one_group;
                    }
                    $one_group = array();
                }
                $one_group[] = $arr[$i];
            }
            if ($one_group) {
                $groups[] = $one_group;
            }
        }
        return $groups;
    }

    public static function exists($values, $two_dim_array, $strict = false, $case_sensitive = true) {
        if (!$two_dim_array) {
            return false;
        }
        if (!$values) {
            return false;
        }
        foreach ($two_dim_array as $arr) {
            if (ARR::inArray($values, $arr, $strict, $case_sensitive)) {
                return true;
            }
        }
        return false;
    }

    public static function inArray($values, $arr, $strict = false, $case_sensitive = true) {
        if (!$arr) {
            return false;
        }
        if (!$values) {
            return false;
        }
        $arr = ARR::toNumericArray($arr);
        $values = APP::toArray($values);
        if (!$case_sensitive) {
            ARR::toLowercase($values);
        }
        $values = array_unique($values);
        $found = array();
        foreach ($arr as $el) {
            if (!$case_sensitive) {
                if (is_string($el)) {
                    $el = strtolower($el);
                }
            }
            if (($idx = array_search($el, $values, $strict)) !== false) {
                if (!in_array($values[$idx], $found)) {
                    $found[] = $values[$idx];
                }
            }
            if (count($found) == count($values)) {
                return true;
            }
        }
        return false;
    }

    public static function keysToLowercase(&$arr) {
        if (!$arr) {
            return $arr;
        }
        if (!ARR::isAssociativeArray($arr)) {
            throw new CustomException('Array must be associative: ', $arr);
        }
        foreach ($arr as $name => $value) {
            if (strtolower($name) != $name) {
                $arr[strtolower($name)] = $value;
                unset($arr[$name]);
            }
        }
        return $arr;
    }

    public static function toLowercase(&$arr) {
        if (!$arr) {
            return $arr;
        }
        if (array_walk($arr, function (&$element, $idx) {
                    if (is_string($element)) {
                        $element = strtolower($element);
                    }
                }
            ) === false
        ) {
            throw new CustomException('Unable to lowercase array');
        }
        return $arr;
    }

    public static function isAssociativeArray($var) {
        return is_array($var) && array_keys($var) !== range(0, sizeof($var) - 1);
    }

    public static function isNumericArray($var) {
        return is_array($var) && array_keys($var) === range(0, sizeof($var) - 1);
    }

    public static function toNumericArray(&$arr) {
        if (is_object($arr)) {
            $arr = (array) $arr;
        }
        if (ARR::isAssociativeArray($arr)) {
            if (array_key_exists(0, $arr)) {
                foreach (array_keys($arr) as $index) {
                    if (is_numeric($index)) {
                        unset($arr[$index]);
                    }
                }
            }
            $arr = array_values($arr);
        }
        return $arr;
    }

    public static function toAssociativeArray(&$arr_or_object) {
        if (is_object($arr_or_object)) {
            $arr_or_object = (array) $arr_or_object;
        }
        if (ARR::isNumericArray($arr_or_object)) {
            foreach ($arr_or_object as $index => &$value) {
                ARR::toAssociativeArray($value);
            }
        } else if (is_array($arr_or_object)) {
            $delete_indices = array();
            foreach ($arr_or_object as $index => &$value) {
                if (is_numeric($index)) {
                    $delete_indices[] = $index;
                } else if (is_object($value) || is_array($value)) {
                    $value = ARR::toAssociativeArray($value);
                }
            }
            if ($delete_indices) {
                foreach ($delete_indices as $index) {
                    unset($arr_or_object[$index]);
                }
            }
        }
        return $arr_or_object;
    }

    public static function trim(&$arr) {
        if ($arr) {
            array_walk($arr, function(&$value) {
                    $value = trim($value);
                });
        }
        return $arr;
    }

}

class APP {

    public static function init($config) {
        set_time_limit(0);
        ini_set('memory_limit', '812M');

        $configs = array(
            'log' => 'Log',
            'curl' => 'CURL',
            'ftp' => 'FTP',
            'mail' => 'MailClient',
            'temp' => 'TEMP',
            'login' => 'Login',
        );
        foreach ($configs as $name => $class_name) {
            if (APP::get($config, $name) !== null) {
                $class_name::init(APP::get($config, $name));
            }
        }
    }

    public static function exists($arr, $names) {
        $default = false;
        if (is_array($arr)) {
            if (!is_array($names)) {
                $names = array($names);
            }
            if (count($names) == 0) {
                return $default;
            } else if (count($names) == 1) {
                $name = array_shift($names);
                return isset($arr[$name]) ? true : false;
            } else {
                $name = array_shift($names);
                if (isset($arr[$name])) {
                    return APP::exists($arr[$name], $names);
                } else {
                    return $default;
                }
            }
        } else if (is_object($arr)) {
            if (!is_array($names)) {
                $names = array($names);
            }
            if (count($names) == 0) {
                return $default;
            } else if (count($names) == 1) {
                $name = array_shift($names);
                return isset($arr->$name) ? true : false;
            } else {
                $name = array_shift($names);
                if (isset($arr->$name)) {
                    return APP::exists($arr->$name, $names);
                } else {
                    return $default;
                }
            }
        } else {
            return false;
        }
    }

    public static function iset(&$arr_or_object, $names, $value, $case_sensitive = true) {
        return APP::set($arr_or_object, $names, $value, false);
    }

    public static function set(&$arr_or_object, $names, $value, $case_sensitive = true) {
        $names = APP::toArray($names);
        if (count($names) === 0) {
            return $arr_or_object;
        }
        $name = array_shift($names);
        if ($case_sensitive == false) {
            if (APP::has($arr_or_object, strtolower($name))) {
                $name = strtolower($name);
            } else if (APP::has($arr_or_object, strtoupper($name))) {
                $name = strtoupper($name);
            } else {
                foreach ($arr_or_object as $_name => $_value) {
                    if (strtolower($_name) === strtolower($name)) {
                        $name = $_name;
                        break;
                    }
                }
            }
        }
        if ($name) {
            if (!$names) {
                if (is_object($arr_or_object)) {
                    $arr_or_object->$name = $value;
                } else {
                    $arr_or_object[$name] = $value;
                }
            } else {
                if (is_object($arr_or_object)) {
                    $arr_or_object = $arr_or_object->$name;
                } else {
                    $arr_or_object = $arr_or_object[$name];
                }
            }
        }
        return self::set($arr_or_object, $names, $value);
    }

    public static function has($arr_or_object, $names, $case_sensitive = true) {
        $names = APP::toArray($names);
        if (count($names) === 0) {
            return true;
        }
        $name = array_shift($names);
        $has = false;
        if (is_object($arr_or_object)) {
            $has = APP::hasClassProperty($arr_or_object, $name, $case_sensitive); // || APP::get($arr_or_object, $name, null, false, $case_sensitive) !== null;
        } else if (is_array($arr_or_object)) {
            if (!ARR::isAssociativeArray($arr_or_object)) {
                throw new CustomException('has only supported for objects and associative arrays');
            }
            $has = ARR::inArray($name, array_keys($arr_or_object), false, $case_sensitive);
        } else {
            $has = false;
        }
        return $has && self::has(APP::get($arr_or_object, $name, null, false, $case_sensitive), $names, $case_sensitive);
    }

    public static function iget($arr, $names, $default = null, $req_is_not_empty = false) {
        return self::get($arr, $names, $default, $req_is_not_empty, false);
    }

    public static function get($arr, $names, $default = null, $req_is_not_empty = false, $case_sensitive = true) {
        if (is_array($arr)) {
            if (!$case_sensitive) {
                $arr = ARR::keysToLowercase($arr);
            }
            if (!is_array($names)) {
                $names = array($names);
            }
            if (count($names) == 0) {
                return $default;
            } else if (count($names) == 1) {
                $name = array_shift($names);
                if (!$case_sensitive) {
                    $name = strtolower($name);
                }
                return isset($arr[$name]) && (!$req_is_not_empty || !APP::isEmpty($arr[$name])) ? $arr[$name] : $default;
            } else {
                $name = array_shift($names);
                if (!$case_sensitive) {
                    $name = strtolower($name);
                }
                if (isset($arr[$name]) && (!$req_is_not_empty || !APP::isEmpty($arr[$name]))) {
                    return APP::get($arr[$name], $names, $default, $req_is_not_empty);
                } else {
                    return $default;
                }
            }
        } else if (is_object($arr)) {
            if (!is_array($names)) {
                $names = array($names);
            }
            if (count($names) == 0) {
                return $default;
            } else if (count($names) == 1) {
                $name = array_shift($names);
                if (!$case_sensitive) {
                    $property = APP::getClassProperty($arr, $name, false);
                    if ($property) {
                        $name = $property->name;
                    }
                }
                return isset($arr->$name) && (!$req_is_not_empty || !APP::isEmpty($arr->$name)) ? $arr->$name : $default;
            } else {
                $name = array_shift($names);
                if (!$case_sensitive) {
                    $name = APP::getClassProperty($arr, $name, false);
                    if ($name) {
                        $name = $name->name;
                    }
                }
                if (isset($arr->$name) && (!$req_is_not_empty || !APP::isEmpty($arr->$name))) {
                    return APP::get($arr->$name, $names, $default, $req_is_not_empty);
                } else {
                    return $default;
                }
            }
        } else {
            return $default;
        }
    }

    public static function getConstant($name, $default = false) {
        return defined($name) ? constant($name) : $default;
    }

    public static function reqFile($uri) {
        if (!file_exists($uri)) {
            throw new CustomException('File ', $uri, ' does not exist');
        }
    }

    public static function reqAssociativeArray($value, $name = "parameter") {
        if (!ARR::isAssociativeArray($value)) {
            throw new InvalidArgumentException("Value for $name must be a associative array");
        }
    }

    public static function reqNumericArray($value, $name = "parameter") {
        if (!ARR::isNumericArray($value)) {
            throw new InvalidArgumentException("Value for $name must be a numeric array");
        }
    }

    public static function reqValue($value, $name = "parameter") {
        if (APP::isEmpty($value)) {
            throw new InvalidArgumentException("Missing value for $name");
        }
    }

    public static function req($arrOrObj, $names, $reqNotEmpty = false) {
        $names = APP::toArray($names);
        $avail = false;
        foreach ($names as $name) {
            $avail = APP::exists($arrOrObj, $name);
            if ($avail && $reqNotEmpty) {
                $avail = APP::isEmpty(APP::get($arrOrObj, $name)) === false;
            }
            if (!$avail && defined('__APP_FAIL_STOP__') && __APP_FAIL_STOP__) {
                die('req failed for ' . $name . ' in data structure ' . print_r($arrOrObj, true));
            }
            if (!$avail) {
                throw new InvalidArgumentException("Missing value " . $name);
            }
        }
        return $avail;
    }

    public static function reqThis($obj, $reqNotEmpty = false) {
        $ref_class = new ReflectionClass($obj);
        if ($ref_class->getProperties(ReflectionProperty::IS_PUBLIC)) {
            foreach ($ref_class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $name = $property->getName();

                APP::req($obj, $name, $reqNotEmpty);
            }
        }
    }

    public static function getClassConstant($classNameOrObject, $name, $default = false) {
        try {
            $reflection = new ReflectionClass($classNameOrObject);
            return ($reflection && $reflection->hasConstant($name)) ? $reflection->getConstant($name) : $default;
        } catch (Exception $class_not_found) {
            return $default;
        }
    }

    public static function getClassPropertyNames($classNameOrObject) {
        $properties = self::getClassProperties($classNameOrObject);

        $names = array();
        foreach($properties as $property) {
            $names[] = (string)$property->name;
        }
        return $names;
    }

    public static function getClassPropertyValueMap($classNameOrObject) {
        $property_names = self::getClassPropertyNames($classNameOrObject);
        $property_values = array();
        foreach($property_names as $name) {
            $property_values[] = $classNameOrObject::$$name;
        }

        return array_combine($property_names, $property_values);
    }

    public static function getClassProperties($classNameOrObject) {
        $reflection = new ReflectionClass($classNameOrObject);
        return $reflection->getProperties();
    }

    public static function getClassConstants($classNameOrObject) {
        $reflection = new ReflectionClass($classNameOrObject);
        return $reflection->getConstants();
    }

    public static function getClassMethod($class_name_or_object, $method_name) {
        $reflection = new ReflectionClass($class_name_or_object);
        return $reflection->getMethod($method_name);
    }

    public static function getClassMethods($class_name_or_object) {
        $reflection = new ReflectionClass($class_name_or_object);
        return $reflection->getMethods();
    }

    public static function getClassProperty($class_name_or_object, $name, $case_sensitive = true) {
        try {
            $reflection = new ReflectionClass($class_name_or_object);
            if ($reflection && $reflection->hasProperty($name)) {
                return $reflection->getProperty($name);
            }
            foreach ($reflection->getProperties() as $property) {
                $_name = $property->name;
                if ($_name == $name || !$case_sensitive && strtolower($_name) == strtolower($name)) {
                    return $property;
                }
            }
// stdClass and other tpyes may not have any declared properties
            if (!is_object($class_name_or_object)) {
                $class_name_or_object = new $reflection->name();
            }
            if (is_object($class_name_or_object)) {
                foreach ($class_name_or_object as $_name => $value) {
                    if ($_name == $name || !$case_sensitive && strtolower($name) == strtolower($_name)) {
                        $property = new stdClass(); //ReflectionProperty requires existing property
                        $property->name = $_name;
                        $property->class = get_class($class_name_or_object);
                        return $property;
                    }
                }
            }
        } catch (Exception $class_not_found) {
            
        }
        return false;
    }

    public static function hasClassProperty($class_name_or_object, $name, $case_sensitive = true) {
        try {
            $reflection = new ReflectionClass($class_name_or_object);
            if ($reflection && $reflection->hasProperty($name)) {
                return true;
            }
            foreach ($reflection->getProperties() as $property) {
                $_name = $property->name;
                if ($_name == $name || !$case_sensitive && strtolower($_name) == strtolower($name)) {
                    return true;
                }
            }
            // stdClass and other tpyes may not have any declared properties
            if (!is_object($class_name_or_object)) {
                $class_name_or_object = new $reflection->name();
            }
            if (is_object($class_name_or_object)) {
                foreach ($class_name_or_object as $_name => $value) {
                    if ($_name == $name || !$case_sensitive && strtolower($name) == $_name) {
                        return true;
                    }
                }
            }
        } catch (Exception $class_not_found) {
            
        }
        return false;
    }

    public static function now() {
        return strftime('%y-%m-%dT%H:%M:%S');
    }

    public static function isEmpty($var) {
        return empty($var) && $var !== 0 || is_object($var) && !get_object_vars($var);
    }

    public static function toArray($value, $ignoreFalse = true) {
        if (!$ignoreFalse || $value !== false) {
            if (is_object($value)) {
                throw new CustomException('Use ARR::wrap instead');
            } else if (is_array($value)) {
                return $value;
            } else {
                return ARR::isNumericArray($value) ? $value : array($value);
            }
        }
        return $value;
    }

    public static function addUnique(& $array, $element) {
        if (in_array($element, $array)) {
            $array[] = $element;
        }
        return $array;
    }

    public static function explode($delimiter, $array, $removeEmptyStrings = false) {
        $array = explode($delimiter, $array);

        if ($removeEmptyStrings) {
            for ($i = 0; $i < count($array); $i++) {
                if (empty($array[$i])) {
                    array_splice($array, $i, 1);
                    $i--;
                }
            }
        }
        return $array;
    }

    public static function toMap($twoDimArray, $arrayKey) {
        $map = array();
        if ($twoDimArray) {
            foreach ($twoDimArray as $element) {
                if (is_array($element)) {
                    if (isset($element[$arrayKey])) {
                        $map[$element[$arrayKey]] = $element;
                    }
                } else if (is_object($element)) {
                    if (isset($element->$arrayKey)) {
                        $map[$element->$arrayKey] = $element;
                    }
                }
            }
        }
        return $map;
    }

    public static function toMapArray($twoDimArray, $arrayKey) {
        $map = array();
        if ($twoDimArray) {
            foreach ($twoDimArray as $element) {
                if (is_array($element)) {
                    if (isset($element[$arrayKey])) {
                        $map[$element[$arrayKey]][] = $element;
                    }
                } else if (is_object($element)) {
                    if (isset($element->$arrayKey)) {
                        $map[$element->$arrayKey][] = $element;
                    }
                }
            }
        }
        return $map;
    }

    public static function toMemberArray($arrayOrObject, $member, $case_sensitive = true, $filter_unique = false) {
        $arr = array();
        foreach ($arrayOrObject as $el) {
            $el = APP::get($el, $member, null, false, $case_sensitive);
            if ($el !== null) {
                $arr[] = $el;
            }
        }
        if ($filter_unique) {
            $arr = array_unique($arr);
        }
        return $arr;
    }

    public static function trimArray(&$arr, $in_between = true, $remove_empty_strings = true) {
        if (!$arr) {
            return $arr;
        }
        if (ARR::isAssociativeArray($arr)) {
            throw new CustomException('trimArray does not work for associative arrays');
        }
        if (!$in_between) {
            $max_index = count($arr) - 1;
            for ($i = 0; $i <= $max_index; $i++) {
                if (empty($arr[$i]) || $remove_empty_strings && is_string($arr[$i]) && strlen(trim($arr[$i])) == 0) {
                    unset($arr[$i]);
                } else {
                    break;
                }
            }
            $arr = array_values($arr);
            for ($i = count($arr) - 1; $i >= 0; $i--) {
                if (empty($arr[$i]) || $remove_empty_strings && is_string($arr[$i]) && strlen(trim($arr[$i])) == 0) {
                    unset($arr[$i]);
                } else {
                    break;
                }
            }
            $arr = array_values($arr);
        }
        if ($in_between) {
            $max_index = count($arr) - 1;
            for ($i = 0; $i <= $max_index; $i++) {
                if (empty($arr[$i]) || $remove_empty_strings && is_string($arr[$i]) && strlen(trim($arr[$i])) == 0) {
                    unset($arr[$i]);
                }
            }
        }
        return array_values($arr);
    }

    public static function trim($str, $additional_characters) {
        $characters = " \t\n\r\0\x0B" . $additional_characters;
        return trim($str, $characters);
    }

    public static function strrtrim($str, $remove = null) {
        if (empty($remove)) {
            return rtrim($str);
        }

        $len = strlen($remove);
        $offset = strlen($str) - $len;
        while ($offset > 0 && $offset == strpos($str, $remove, $offset)) {
            $str = substr($str, 0, $offset);
            $offset = strlen($str) - $len;
        }

        return rtrim($str);
    }

    public static function getHostName() {
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            return gethostname();
        } else {
            return php_uname('n');
        }
    }

    public static function isValidEmail($email_address) {
        return filter_var($email_address, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function getFirstName($name) {
        if (!$name) {
            return $name;
        }
        $name = trim($name);
        if (!$name) {
            return $name;
        }
        if (($pos = strripos($name, ' ')) === false) {
            return '';
        }
        return trim(substr($name, 0, $pos));
    }

    public static function getLastName($name) {
        if (!$name) {
            return $name;
        }
        $name = trim($name);
        if (!$name) {
            return $name;
        }
        if (($pos = strripos($name, ' ')) === false) {
            return $name;
        }
        return trim(substr($name, $pos));
    }

    public static function requireDir($dir) {
        if (!file_exists($dir)) {
            throw new CustomException('Directory ', $dir, ' not exists');
        }
        $dh = opendir($dir);
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $path_or_uri = PATH::combine($dir, $file);
            if (is_dir($path_or_uri)) {
                self::requireDir($path_or_uri);
            } else if (PATH::matchAny($path_or_uri, "*.php")) {
                require_once($path_or_uri);
            }
        }
        closedir($dh);
    }

    public static function equals($lhs, $rhs, $names = false, $strict = false, $ignore_null = false) {
        if ($names === false) {
            $names = array_keys((array) $lhs);
        }
        if (!$names) {
            return true;
        }
        $names = APP::toArray($names);
        foreach ($names as $name) {
            if ((!$strict && APP::get($lhs, $name) != APP::get($rhs, $name)) || ($strict && APP::get($lhs, $name) !== APP::get($rhs, $name))) {
                if (!$ignore_null || ($ignore_null && (APP::get($lhs, $name) !== null && APP::get($rhs, $name) !== null))) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function cast($object, $target_type, $recursive = false) {
        $instance = new $target_type();
        foreach ($instance as $name => $value) {
            if (APP::exists($object, $name)) {
                if ($recursive && class_exists($name)) {
                    $instance->$name = new $name();
                    APP::cast($instance->$name, $value);
                } else {
                    $instance->$name = APP::get($object, $name);
                }
            } else if (stripos($name, '_') !== false && APP::exists($object, STRING::until($name, '_'))) {
                $object_value = APP::get($object, STRING::until($name, '_'));
                if ($object_value && ARR::isAssociativeArray($object_value)) {
                    if (APP::exists($object, array(STRING::until($name, '_'), STRING::inBetween($name, '_')))) {
                        $instance->$name = APP::get($object, array(STRING::until($name, '_'), STRING::inBetween($name, '_')));
                    }
                }
            }
        }
        return $instance;
    }

}

