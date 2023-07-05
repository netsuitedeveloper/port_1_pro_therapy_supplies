<?php



/////////////////////////////////////////////////////////////////////////////////////////////////////

// -- if (!@class_exists("{$__PHP_LIB_NAMESPACE__}PDEBUG")) {
// -- 
// --     class PDEBUG {
// -- 
// --     }
// -- 
// -- }

/////////////////////////////////////////////////////////////////////////////////////////////////////

function getClassConstant($classNameOrObject, $name, $default = null) {
    try {
        $reflection = new \ReflectionClass($classNameOrObject);
        return ($reflection && $reflection->hasConstant($name)) ? $reflection->getConstant($name) : $default;
    } catch (\Exception $class_not_found) {
        return $default;
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////

if (!getClassConstant(__NAMESPACE__ . '\\' . "PDEBUG", 'NO_GLOBALS')) {

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    function is_alpha($value) { 
        $o = ord($value);

        return $o >= 97 && $o <= 122 || $o >= 65 && $o <= 90;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    function is_assoc($value) { 
        return is_array($value) && ( ($count = count($value)) == false || array_key_exists(0, $value) == false || array_key_exists($count-1, $value) == false);
    }

    function is_num($value) { 
        return is_array($value) && ($value == false || (array_key_exists(0, $value) && array_key_exists(count($value)-1, $value)) );
    }

    function is_empty_string($value) { 
        return strlen(strval($value)) == 0;
    }

    function is_empty_or_whitespace_string($value) { 
        return strlen(trim(strval($value))) == 0;
    }

    function is_whitespace($value) { 
        static $whitespaces = array(
            ' ',
            "\t",
            "\r",
            "\n",
        );
        return in_array($value, $whitespaces);
    }

    function is_entity($value) {
        return is_object($value) && $value instanceof IEntity || is_string($value) && is_subclass_of($value, IEntity);
    }

    function is_empty_object($instance) { 
        if (is_null($instance)) { 
            return true;
        } else {
            $data = get_object_vars($instance);
            foreach($data as $key => $value) { 
                if (is_null($value) == false) { 
                    return false;
                }
            }
            return true;
        }
    }

    function is_identifier($value) { 
        return preg_match('/[a-zA-Z_\\\\][a-zA-Z_0-9\\\\]*/', $value) === 1;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    function array_back($arr) {
        return $arr ? $arr[count($arr)-1] : null;
    }

    function array_front($arr) {
        return $arr ? $arr[0] : null;
    }

    function array_wrap($arr) {
        return ARR::wrapToNumericArray($arr);
    }

    function array_num($value) { 
        if (is_null($value)) { 
            return array();
        } else if (is_num($value)) { 
            return $value;
        } else {
            return array($value);
        }
    }

    function array_split($arr, $group_size) { 
        return ARR::split($arr, $group_size);
    }

    function array_remove($arr, $el, $options = null) { 
        return ARR::remove($arr, $el, $options);
    }

    function array_merge_assoc($target, $source, $options_or_replace = true) { 
        if (is_null($target)) { 
            return $source;
        } else if (is_null($source)) { 
            return $target;
        } else if (is_array($target) == false) { 
            throw new CustomException('Can not merge assoc arrays: target value is not an array ', $target);
        } else if (is_array($source) == false) { 
            throw new CustomException('Can not merge assoc arrays: source value is not an array ', $source);
        } else {
            $append_num_arrays = get_or_default($options_or_replace, 'append_num_arrays', false);
            $replace = is_bool($options_or_replace) ? $options_or_replace : get_or_default($options_or_replace, 'replace', true);
            if (is_assoc($target)) { 
                if (is_assoc($source) == false) { 
                    throw new CustomException('Can not merge assoc arrays: both arrays must be associative if target is associative');
                }
                foreach($source as $name => $value) { 
                    if (isset($target[$name]) == false) { 
                        $target[$name] = $value;
                    } else if (isset($target[$name]) && $replace) { 
                        if (is_array($target[$name]) == false) { 
                            $target[$name] = $value;
                        } else if (is_num($target[$name])) {
                            if (is_num($value) && $append_num_arrays) { 
                                $target[$name] = array_merge($target[$name], $value);
                            } else { 
                                $target[$name] = $value;
                            }
                        } else { /// if assoc
                            if (is_assoc($value) == false) { 
                                throw new CustomException('Can not merge assoc arrays: both arrays must be associative if target is associative at key ', $name, ' with ', $source, ' and ', $target);
                            }
                            $target[$name] = $value;
                        }
                    }
                }
            } else if (is_num($target)) { 
                if ($append_num_arrays) {
                    if (is_num($source) == false) { 
                        throw new CustomException('Can not merge assoc arrays: both arrays must be numeric if target is numeric');
                    }
                    $target = array_merge($target, $source);
                }
            }
            return $target;
        }
    }

    function array_merge_assoc_recursive($target, $source, $options_or_replace = true) { 
        if (is_null($target)) { 
            return $source;
        } else if (is_null($source)) { 
            return $target;
        } else if (is_array($target) == false) { 
            throw new CustomException('Can not merge assoc arrays: target value is not an array ', $target);
        } else if (is_array($source) == false) { 
            throw new CustomException('Can not merge assoc arrays: source value is not an array ', $source);
        } else {
            $append_num_arrays = get_or_default($options_or_replace, 'append_num_arrays', false);
            if (is_assoc($target)) { 
                if (is_assoc($source) == false) { 
                    throw new CustomException('Can not merge assoc arrays: both arrays must be associative if target is associative');
                }
                $replace = is_bool($options_or_replace) ? $options_or_replace : get_or_default($options_or_replace, 'replace', true);
                foreach($source as $name => $value) { 
                    if (isset($target[$name]) == false) { 
                        $target[$name] = $value;
                    } else if (isset($target[$name]) && $replace) { 
                        if (is_array($target[$name]) == false) { 
                            $target[$name] = $value;
                        } else if (is_num($target[$name])) {
                            if (is_num($value)) { 
                                if ($append_num_arrays) { 
                                    $target[$name] = array_merge($target[$name], $value);
                                }
                            } else {
                                $target[$name][] = $value;
                            }
                        } else { /// if assoc
                            if (is_assoc($value) == false) { 
                                throw new CustomException('Can not merge assoc arrays: both arrays must be associative if target is associative at key ', $name, ' with ', $source, ' and ', $target);
                            }
                            $target[$name] = array_merge_assoc($target[$name], $value, $options_or_replace);
                        }
                    }
                }
            } else if (is_num($target)) { 
                if ($append_num_arrays) {
                    if (is_num($source) == false) { 
                        throw new CustomException('Can not merge assoc arrays: both arrays must be numeric if target is numeric');
                    }
                    $target = array_merge($target, $source);
                }
            } 
            return $target;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    function array_merge_unique($target, $source) { 
        if (!$target) { 
            return $source;
        } else if (!$source) { 
            return $target;
        } else { 
            $target = array_merge($target, $source);

            return is_num($target) ? array_values(array_unique($target)) : array_unique($target);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function array_unique_ex($value) { 
        if (is_null($value)) {
            return array();
        } else if (is_num($value)) { 
            return array_values(array_unique($value));
        } else { 
            return array_unique($value);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function array_merge_num() { 
        $data = array();

        $args = func_get_args();
        foreach($args as $arg) { 
            if (is_null($arg)) { 
                continue;
            } else if (is_num($arg)) { 
                $data = array_merge($data, $arg);
            } else { 
                throw new TypeException('array_merge_num', $arg);
            }
        }

        return $data;
    }

    function array_has_intersect() { 
        $args = func_get_args();

        if (!$args) { 
            return false;
        } else {
            $arg = array_front($args);
            if (is_num($arg)) { 
                for ($i = 1; $i < count($args); $i++) { 
                    $_arg = $args[$i];

                    foreach($arg as $value) { 
                        if (in_array($value, $_arg)) { 
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function array_values_recursive($input) { 
        if (is_null($input)) {
            return array();
        } else if (is_array($input) == false) { 
            throw new CustomException('|array_values_recursive| expects an array: ', $input);
        }
        $array_values = array_values($input);

        foreach($array_values as $key => & $array_value) { 
            if (is_array($array_value)) { 
                $array_value = array_splice($array_values, $key, 1, array_values_recursive($array_value));
            }
        }

        return $array_values;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function in_array_ex($value, $container, $container_key1 = null) { 
        if (is_null($container_key1)) { 
            $container = array_num($container);

            if ($container == false) { 
                return false;
            } else {
                return in_array($value, $container);
            }
        } else { 
            $container_keys = array_slice(func_get_args(), 2);

            $container = get($container, $container_keys);

            return in_array_ex($value, $container);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function array_values_ex($value) { 
        if (is_null($value)) { 
            return $value;
        } else if (is_array($value)) { 
            return array_values($value);
        } else { 
            return array($value);
        }
    }

    function array_merge_ex() {
        $arguments = func_get_args();

        $result = array();
        if ($arguments) { 
            foreach($arguments as $argument) {
                if (is_null($argument)) { 
                    continue;
                } else if (is_array($argument)) { 
                    $result = array_merge($result, $argument);
                } else { 
                    $result = array_merge($result, array_wrap($argument));
                }
            }
        }
        return $result;
    }

    function array_merge_recursive_ex() {
        $arguments = func_get_args();

        $result = array();
        if ($arguments) { 
            foreach($arguments as $argument) {
                if (is_null($argument)) { 
                    continue;
                } else if (is_array($argument)) { 
                    $result = array_merge_recursive($result, $argument);
                } else { 
                    $result = array_merge_recursive($result, array_wrap($argument));
                }
            }
        }
        return $result;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function array_sum_ex($container) {
        if (is_null($container)) { 
            return 0;
        } else if (is_array($container) == false) {
            return CONVERT::toInt($container);
        } else {
            return array_sum($container);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function array_reduce_ex($container, $callable, $initial_value = null) { 
        if (is_null($container)) { 
            return $initial_value;
        } else if (is_num($container)) { 
            return array_reduce($container, $callable, $initial_value);
        } else if (is_assoc($container)) { 
            $result = $initial_value;
            foreach($container as $key => $value) { 
                $callable($result, $value, $key);
            }
            return $result;
        } else { 
            throw new TypeException($container);
        }
    }

    function array_diff_ex($container1, $container2) {
        if (is_null($container1)) {
            return array();
        } else if (is_null($container2)) {
            return $container1;
        } else if (is_num($container1)) {
            if (is_array($container2) == false) { 
                $container2 = array($container2);
            }
            return array_values(array_diff($container1, $container2));
        } else { 
            if (is_array($container2) == false) { 
                $container2 = array($container2);
            }
            return array_diff($container1, $container2);
        }
    }

    function array_filter_ex($container, $callable) {
        if (is_null($container)) {
            return $container;
        } else if (is_num($container)) {
            return array_values(array_filter($container, $callable));
        } else {
            return array_filter($container, $callable);
        }
    }

    function array_keys_ex($container, $search_values = null, $strict = false) {
        if (is_null($container)) {
            return array();
        } else if (func_num_args() == 1) {
            return array_keys($container);
        } else { 
            return array_keys($container, $search_values, $strict);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    function require_arg($argument_value, $argument_name = null) { 
        if (is_null($argument_value)) { 
            throw new CustomException('Missing argument', is_null($argument_name) ? '' : ': ' . $argument_name);
        } else if (is_numeric($argument_value)) { 
            return;
        } else if (is_array($argument_value) || is_object($argument_value)) { 
            return;
        } else if ($argument_value == false) { 
            throw new CustomException('Missing argument', is_null($argument_name) ? '' : ': ' . $argument_name);
        }
    }
    function require_value($argument_value, $argument_name = null) { 
        if (is_null($argument_value)) { 
            throw new CustomException('Missing value', is_null($argument_name) ? '' : ': ' . $argument_name);
        } else if (is_numeric($argument_value)) { 
            return;
        } else if (is_array($argument_value) || is_object($argument_value)) { 
            return;
        } else if ($argument_value == false) { 
            throw new CustomException('Missing value', is_null($argument_name) ? '' : ': ' . $argument_name);
        }
    }
    function require_array($value, $name = null) { 
        if (is_array($value) == false) { 
            if ($name) { 
                throw new CustomExeption($name . ' must be an array!');
            } else { 
                throw new CustomException('Require failed for array!');
            }
        }
    }
    function require_string($value, $name = null) { 
        if (is_string($value) == false) { 
            if ($name) { 
                throw new CustomExeption($name . ' must be a string');
            } else { 
                throw new CustomException('Require string: ', $value);
            }
        }
    }
    function require_assoc($value, $name = null) { 
        if (is_assoc($value) == false) { 
            if ($name) { 
                throw new CustomExeption($name . ' must be an associative array!');
            } else { 
                throw new CustomException('Require failed for associative array!');
            }
        }
    }
    function require_num($value, $name = null) { 
        if (is_num($value) == false) { 
            if ($name) { 
                throw new CustomExeption($name . ' must be a numeric array!');
            } else { 
                throw new CustomException('Require failed for numeric array!');
            }
        }
    }
    function require_dir($path, $name = null) { 
        if (is_dir($path) == false) {
            throw new CustomException(($name ? $name . ': ' : ''), $path, ' does not exists or is not a directory');
        }
    }
    function require_file($path, $name = null) { 
        if (file_exists($path) == false) { 
            throw new CustomException(($name ? $name . ': ' : ''), $path, ' does not exist');
        }
        if (is_dir($path)) {
            throw new CustomException(($name ? $name . ': ' : ''), $path, ' exists but as a directory');
        }
    }
    function require_class($class_name) { 
        if (class_exists($class_name) == false) { 
            throw new CustomException('class does not exist: ', $class_name);
        }
    }
    function require_type($obj, $type) {
        if (is_a($obj, $type) == false) {
            throw new CustomException('Object must be of type ', $type, ': ', $obj);
        }
    }
    function require_callable($value, $name = null) { 
        if (is_callable($value) == false) { 
            if ($name) { 
                throw new CustomExeption($name . ' must be callable!');
            } else { 
                throw new CustomException('No callable');
            }
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    function make_dir($path, $permissions = 0777, $recursive = true) {
        return FILE::createDirectory($path, $permissions, $recursive);
    }

    function compare($lhs, $rhs, $options = null) { 
        return APP::compare($lhs, $rhs, $options);
    }

    function compare_strict($lhs, $rhs, $options = null) { 
        $options['strict'] = true;
        return APP::compare($lhs, $rhs, $options);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function safe_unlink($path) { 
        if (is_array($path)) {
            foreach($path as $path_) {
                safe_unlink($path_);
            }
        } else {
            @unlink($path);
        }
    }

    function safe_include_once($path) { 
        if (file_exists($path)) { 
            include_once $path;
        }
    }

    function safe_serialize($data, $path = null) {
        $data = serialize($data);
        if (is_null($path) == false) { 
            make_dir(dirname($path));

            $retry = true;
            for ($i = 0; $i < 5 && $retry; $i++) { 
                if ($i > 0) { 
                    usleep($i*$i);
                }
                FILE::write($path, $data);
                $text = FILE::read($path);
                $retry = strlen($data) !== strlen($text);
            }
            if ($retry) {
                throw new CustomException('Can not safe serialize to ', $path);
            }
        }
        assert($data !== false);
        return $data;
    }

    function safe_unserialize($path, & $byte_string = false) { 
        $data = false;
        for ($i = 0; $i < 5 && $data === false; $i++) { 
            if ($i > 0) { 
                usleep($i*$i);
            }
            $data = FILE::read($path);
            if ($byte_string !== false) {
                $byte_string = $data;
            }
            $data = @unserialize($data);
        }
        if ($data === false) { 
            throw new CustomException('Can not safe unserialize from ', $path);
        }
        return $data;
    }

    function safe_file_exists($path) { 
        if (file_exists($path) == false) {
            usleep(1);
            if (file_exists($path) == false) {
                usleep(1);
                if (file_exists($path) == false) {
                    return false;
                }
            }
        }
        return true;
    }

    function safe_require_dir($path, $name = null) { 
        if (is_dir($path) == false) {
            usleep(1);
            if (is_dir($path) == false) {
                usleep(1);
                if (is_dir($path) == false) {
                    throw new CustomException(($name ? $name . ': ' : ''), $path, ' does not exists or is not a directory');
                }
            }
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    function get() {
        return call_user_func_array(__NAMESPACE__ . '\\' . 'APP::get', func_get_args());
    }

    function iget() {
        return call_user_func_array(__NAMESPACE__ . '\\' . 'APP::iget', func_get_args());
    }

    function get_or_null($data, $name, $n1 = null, $n2 = null, $n3 = null, $n4 = null, $n5 = null) { 
        if (is_array($data)) { 
            if (isset($data[$name]) == false) { 
                return null;
            }

            $data = $data[$name];
        } else if (is_object($data)) { 
            if (!isset($data->$name)) { 
                return null;
            }
            $data = $data->$name;
        } else {
            return null;
        }

        if (is_null($n1)) { 
            return $data;
        }

        return get_or_null($data, $n1, $n2, $n3, $n4, $n5);
    }

    function get_as_num_or_null($data, $name, $n1 = null, $n2 = null, $n3 = null, $n4 = null, $n5 = null) { 
        $data = get_or_null($data, $name, $n1, $n2, $n3, $n4, $n5);

        return is_null($data) ? $data : array_wrap($data);
    }

    function get_as_array_or_null($data, $name, $n1 = null, $n2 = null, $n3 = null, $n4 = null, $n5 = null) { 
        $data = get_or_null($data, $name, $n1, $n2, $n3, $n4, $n5);

        if (is_null($data)) { 
            return $data;
        } else if (is_array($data)) { 
            return $data;
        } else { 
            return array($data);
        }
    }

    function get_or_array() { 
        $args = func_get_args();
        $args[] = array();

        return call_user_func_array(__NAMESPACE__ . '\\' . 'get_or_default', $args);
    }

    function get_or_default() { 
        $args = func_get_args();
        if (count($args) < 3) { 
            throw new CustomException('get_or_default requires at least three parameters');
        }

        $data = array_shift($args);
        $name = array_shift($args);
        $default = array_pop($args);

        if (is_array($name)) { 
            $args = $name;
            $name = array_shift($args);
        }

        if (is_array($data)) { 
            if (isset($data[$name]) == false) { 
                return $default;
            }

            $data = $data[$name];
        } else if (is_object($data)) { 
            if (!isset($data->$name)) { 
                return $default;
            }
            $data = $data->$name;
        } else {
            return $default;
        }

        if (count($args) <= 0) { 
            return $data;
        }

        return get_or_default($data, $args, $default);
    }

    function get_and_default() { 
        $args = func_get_args();
        if (count($args) < 3) { 
            throw new CustomException('|get_and_default| requires at least three parameters');
        }

        $data = array_shift($args);
        $name = array_shift($args);
        $default = array_pop($args);

        if (is_array($name)) { 
            $args = $name;
            $name = array_shift($args);
        }

        if (is_array($data)) { 
            if (isset($data[$name]) == false) { 
                return null;
            }

            $data = $data[$name];
        } else if (is_object($data)) { 
            if (!isset($data->$name)) { 
                return null;
            }
            $data = $data->$name;
        } else {
            return null;
        }

        if (count($args) <= 0) { 
            return $data == true ? $default : null;
        }

        return get_and_default($data, $args, $default);
    }

    function get_and_default_or_default() { 
        $args = func_get_args();
        if (count($args) < 4) { 
            throw new CustomException('|get_and_default_or_default| requires at least four parameters');
        }

        $data = array_shift($args);
        $or_default = array_pop($args);
        $and_default = array_pop($args);

        $data = get_and_default($data, $args, $and_default);

        $data = is_null($data) ? $or_default : $data;

            return $data;
    }

    function get_and_require() {
        $args = func_get_args();
        if (count($args) < 2) { 
            throw new CustomException('get_and_require requires at least two parameters');
        }

        $data = array_shift($args);
        $name = array_shift($args);

        if (is_array($name)) { 
            $args = $name;
            $name = array_shift($args);
        }

        if (is_array($data)) { 
            if (isset($data[$name]) == false) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
            }

            $data = $data[$name];
        } else if (is_object($data)) { 
            if (!isset($data->$name)) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
            }
            $data = $data->$name;
        } else if ($name) { 
            throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
        } else { 
            throw new CustomException('Missing name');
        }

        if (!$args) { 
            return $data;
        }

        return get_and_require($data, $args);
    }


    function iget_and_require() {
        $args = func_get_args();
        if (count($args) < 2) { 
            throw new CustomException('iget_and_require requires at least two parameters');
        }

        $data = array_shift($args);
        $name = array_shift($args);

        if (is_array($name)) { 
            $args = $name;
            $name = array_shift($args);
        }

        if (is_array($data)) { 
            $name = strtolower($name);
            $data = ARR::keysToLowercase($data, true);

            if (isset($data[$name]) == false) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
            }

            $data = $data[$name];
        } else if (is_object($data)) { 
            $property = APP::getClassProperty($data, $name, false);
            if (!$property->name) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
            }
            $name = $property->name;
            $data = $data->$name;
        } else if ($name) { 
            throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
        } else { 
            throw new CustomException('Missing name');
        }

        if (!$args) { 
            return $data;
        }

        return iget_and_require($data, $args);
    }

    function set(&$arr_or_object, $names, $value, $case_sensitive = true) {
        return APP::set($arr_or_object, $names, $value, $case_sensitive);
    }

    function iset(&$arr_or_object, $names, $value) { 
        return APP::iset($arr_or_object, $names, $value);
    }

    function has() {
        return call_user_func_array(__NAMESPACE__ . '\\' . 'APP::has', func_get_args());
    }

    function ihas() {
        return call_user_func_array(__NAMESPACE__ . '\\' . 'APP::ihas', func_get_args());
    }

    function get_with_prefix() { 
        $args = func_get_args();
        if (count($args) < 3) { 
            throw new CustomException('|get_with_prefix| requires at least three parameters');
        }

        $prefix_data = array_pop($args);

        $data = call_user_func_array(__NAMESPACE__ . '\\' . 'get_or_null', $args);

        $data = is_null($data) ? null : $prefix_data . $data;

        return $data;
    }

    function get_and_append_or_null() {
        $args = func_get_args();
        if (count($args) < 3) { 
            throw new CustomException('|get_and_append_or_null| requires at least three parameters');
        }
        $append_data = array_pop($args);

        $data = call_user_func_array(__NAMESPACE__ . '\\' . 'get_or_null', $args);

        $data = is_null($data) ? null : $data . $append_data;

        return $data;
    }

    function append_if_set() {
        $args = func_get_args();
        if (count($args) < 3) { 
            throw new CustomException('|append_if_set| requires at least three parameters');
        }
        $append_data = array_pop($args);

        $data = call_user_func_array(__NAMESPACE__ . '\\' . 'get_or_null', $args);

        $data = is_null($data) ? null : $data . $append_data;

        return $data;
    }

    function append() {
        $args = func_get_args();
        if (count($args) < 3) { 
            throw new CustomException('|append| requires at least three parameters');
        }
        $append_data = array_pop($args);
        if (is_null($append_data) == false) { 
            $data = call_user_func_array(__NAMESPACE__ . '\\' . 'get_or_null', $args);

            $data = $data . $append_data;

            $object = array_shift($args);

            set($object, $args, $data);
        } else { 
            $object = array_shift($args);
        }

        return $object;
    }

    function append_with_whitespace() { 
        $args = func_get_args();
        if (count($args) < 3) { 
            throw new CustomException('|append_with_withespace| requires at least three parameters');
        }
        $append_data = array_pop($args);

        if (is_null($append_data) == false) { 
            $data = call_user_func_array(__NAMESPACE__ . '\\' . 'get_or_null', $args);

            $data = ($data ? $data . ' ' : $data) . $append_data;

            $object = array_shift($args);

            set($object, $args, $data);
        } else { 
            $object = array_shift($args);
        }

        return $object;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    function any($container, $predicate) { 
        if (!$container) { 
            return false;
        }

        foreach($container as $key => $value) { 
            $result = $predicate($value, $key, $container);
            if ($result == true) { 
                return true;
            }
        }
        return false;
    }

    function strict_any($container, $predicate) { 
        if (!$container) { 
            return false;
        }

        foreach($container as $key => $value) { 
            $result = $predicate($value, $key, $container);
            if ($result === true) { 
                return true;
            }
        }
        return false;
    }

    function all($container, $predicate) { 
        if (!$container) { 
            return true;
        }

        foreach($container as $key => $value) { 
            $result = $predicate($value, $key, $container);
            if ($result != true) { 
                return false;
            }
        }
        return true;
    }

    function strict_all($container, $predicate) { 
        if (!$container) { 
            return true;
        }

        foreach($container as $key => $value) { 
            $result = $predicate($value, $key, $container);
            if ($result !== true) { 
                return false;
            }
        }
        return true;
    }

    function for_each(& $container, $action, $this_ = null) { 
        if (!$container) { 
            return;
        }

        if (is_null($this_)) { 
            foreach($container as $key => $value) { 
                $result = $action($value, $key, $container);
            }
        } else { 
            foreach($container as $key => $value) { 
                $result = $action($this_, $value, $key, $container);
            }
        }

        return $result;
    }

    function for_each_recursive(& $container, $action, $this_ = null) { 
        if (!$container) { 
            return;
        }
        if (is_null($this_)) { 
            foreach($container as $key => $value) { 
                if (is_array($value) || is_object($value)) { 
                    $result = for_each_recursive($value, $action, $this_);
                } else { 
                    $result = $action($value, $key, $container);
                }
            }
        } else { 
            foreach($container as $key => $value) { 
                if (is_array($value) || is_object($value)) { 
                    $result = for_each_recursive($value, $action, $this_);
                } else { 
                    $result = $action($this_, $value, $key, $container);
                }
            }
        }
        return $result;
    }

    function array_walk_ex(& $container, $action, $this_ = null) { 
        if (!$container) { 
            return;
        }

        if (is_null($this_)) { 
            foreach($container as $key => & $value) { 
                $result = $action($value, $key, $container);
            }
        } else { 
            foreach($container as $key => & $value) { 
                $result = $action($this_, $value, $key, $container);
            }
        }

        return $result;
    }

    function array_walk_recursive_ex(& $container, $action, $this_ = null) { 
        if (!$container) { 
            return;
        }
        if (is_null($this_)) { 
            foreach($container as $key => & $value) { 
                if (is_array($value) || is_object($value)) { 
                    $result = array_walk_recursive_ex($value, $action, $this_);
                } else { 
                    $result = $action($value, $key, $container);
                }
            }
        } else { 
            foreach($container as $key => & $value) { 
                if (is_array($value) || is_object($value)) { 
                    $result = array_walk_recursive_ex($value, $action, $this_);
                } else { 
                    $result = $action($this_, $value, $key, $container);
                }
            }
        }
        return $result;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function boolval($value) { 
        return empty($value) ? false : true;
    }
    function decimalval($value) { 
        return round(floatval($value), 4);
    }
    function dateval($value) { 
        return $value instanceof Date ? $value : new Date($value);
    }
    function timeval($value) { 
        return $value instanceof Time ? $value : new Time($value);
    }
    function datetimeval($value) { 
        return $value instanceof \DateTime ? $value : new DateTime($value);
    }
    function objectval($value) { 
        if (is_object($value) || is_null($value)) { 
            return $value;
        } else { 
            throw new CustomException('Can not execute |objectval|: value is not an object', $value);
        }
    }
    function entityval($value) { 
        if (is_object($value) || is_null($value) || is_string($value)) { 
            return $value;
        } else if (is_numeric($value)) { 
            return intval($value);
        } else { 
            throw new CustomException('Can not execute |entityval|: value is not a recognized entity value ', $value);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function parse_float($value) { 
        $value = explode('.', strval($value));

        $value[0] = floatval($value[0]);
        if (count($value) > 0) {
            $value[1] = floatval('0.' . $value[1]);
        }
        return $value;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function print_n($data = null, $return_result = false) { 
        if (func_num_args() > 2 || func_num_args() > 1 && is_bool(func_get_arg(1)) == false) {
            $last_argument = func_get_arg(func_num_args()-1);
            $data = func_get_args();
            if (is_bool($last_argument)) {
                array_pop($data);
            }
            $result = null;
            foreach($data as $data_) {
                if (is_bool($last_argument)) {
                    $result .= print_n($data_, $last_argument);
                } else { 
                    print_n($data_);
                }
            }
            if (is_bool($last_argument) && $last_argument) {
                return $result;
            }
        } else { 
            if (is_array($data) || is_object($data)) { 
                $result = print_r($data, $return_result);
            } else { 
                if (is_null($data)) { 
                    $data = 'NULL';
                } else if ($data === false) { 
                    $data = 'bool(false)';
                } else if ($data === true) { 
                    $data = 'bool(true)';
                }
                $result = print_r($data, $return_result);
                if ($return_result == false) { 
                    print_r(PHP_EOL);
                } else { 
                    $result .= PHP_EOL;
                }
            }
            return $result;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function object($data = null) { 
        return is_null($data) ? new stdClass() : (object)$data;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    function stdData($data = null) {
        return new stdData($data);
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class NotImplementedException extends \Exception 
{

}

class CustomException extends \Exception 
{

    public $wrapped;

    public function __construct() {
        if (func_num_args() == 0) {

        } else if (func_num_args() == 1) {
            $ex = func_get_arg(0);
            if ($ex instanceof \Exception) {
                $this->wrapped = $ex;
            } else {
                $this->message = $ex;
            }
        } else {
            $ex = func_get_arg(0);
            if ($ex instanceof \Exception) {
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
            throw new \InvalidArgumentException('Missing exception as first parameter');
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

class TypeException extends CustomException
{
    public function __construct($context, $data = null) { 
        parent::__construct('Invalid data type for |', $context, '|: ', $data);
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class APP 
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    private static $_NAMESPACE;
    private static $_app_mode;

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static $initialized;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function init($config) {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        error_reporting(E_ERROR);

        self::setAPPMode(get_or_null($config, 'mode'));

        self::$_NAMESPACE = __NAMESPACE__  ? __NAMESPACE__. '\\' : null;

        $configs = array(
            'log' => 'Log',
            'serializer' => 'SERIALIZER',
            'curl' => 'CURL',
            'ftp' => 'FTP',
            'mail' => 'MailClient',
            'temp' => 'TEMP',
            'login' => 'Login',
            'dt'=> 'DT',
            'auth'=> 'Auth',
            'assertions' => 'Assertions',
            'site' => 'Site',
            'entities' => 'Entities',
        );
        foreach ($configs as $name => $class_name) {
            if (APP::get($config, $name) !== null) {
                $class_name = __NAMESPACE__ . '\\' . $class_name; 

                $class_name::init(APP::get($config, $name));
            }
        }
        self::$initialized = true;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function setAPPMode($mode) { 
        self::$_app_mode = $mode;

        if (self::in_development()) { 
            Assertions::init(true);
            error_reporting(E_ALL);
        }
    }

    public static function getAPPMode() { 
        return self::$_app_mode;
    }

    public static function getNamespace($sub_namespace = null) {
        if (!$sub_namespace) { 
            return self::$_NAMESPACE;
        } else if (self::hasNamespace()) { 
            return self::getNamespace() . $sub_namespace;
        } else {
            return $sub_namespace;
        }
    }

    public static function isNamespace($namespace)  {
        return $namespace == self::$_NAMESPACE;
    }

    public static function hasNamespace()  {
        return __NAMESPACE__ ? true : false;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function in_development() { 
        return self::$_app_mode && self::$_app_mode[0] == 'd';
    }

    public static function in_production() { 
        return self::$_app_mode && self::$_app_mode[0] == 'p';
    }

    public static function in_test() { 
        return self::$_app_mode && self::$_app_mode[0] == 't';
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function isCLI() {
        $is_cli = get($_SERVER, 'argc') > 0 ? true : false;

        return $is_cli;
    }

    public static function is_windows() { 
        return PHP_OS == 'WINNT';
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function setArguments($args) {
        $_POST = $args;
    }

    public static function getArgument($argument_path, $include_cmdline = true) {
        $args = self::getArguments($include_cmdline);

        return get($args, $argument_path);
    }

    public static function getArguments($include_cmdline_or_options = true) {
        static $arguments;

        if (is_null($arguments)) {
            $arguments = $_REQUEST; 

            $include_cmdline = is_bool($include_cmdline_or_options) ? $include_cmdline_or_options : get_or_null($include_cmdline_or_options, 'include_cmdline');

            if (APP::isCli() && $include_cmdline) {
                $argv = array_slice($_SERVER['argv'], 1);
                if ($argv) {
                    foreach ($argv as $arg) {
                        parse_str($arg, $arguments_);

                        $arguments = array_merge_assoc($arguments, $arguments_);
                    }
                }
            }
        } else if (get_or_null($include_cmdline_or_options, 'clear')) {
            $arguments = array();
        }

        return $arguments;
    }

    public static function clearArguments() {
        static $options = array(
            'clear' => true,
        );
        self::getArguments($options);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function where($datum, $value_or_values, $strict_or_options = true) {
        $strict = get_or_default($strict_or_options, 'strict', true);

        if (is_null($datum)) {
            return null;
        } else if (ARR::isNumericArray($value_or_values)) {
            $_values = array();

            $any = get_or_null($strict_or_options, 'any');

            foreach($value_or_values as $value) {
                $_value = self::where($datum, $value, $strict);

                if ($any == false && is_null($_value)) { // requires all valid values
                    return null;
                }

                $_values[] = $value;
            }
            return $_values;
        } else if (is_object($datum) || ARR::isAssociativeArray($datum) || $datum instanceof COLLECTION) {
            foreach($datum as $_name => $_value) {
                // PHP is converting numeric string keys to integers, either set strict default to false or make an exception here (?)
                if ($strict && is_numeric($_name) && strval($_name) === $value_or_values || $strict && $_name === $value_or_values || $strict == false && $_name == $value_or_values) {
                    return $_value;
                } else if ($strict == true && $_value === $value_or_values || $strict == false && $_value == $value_or_values) {
                    return $_name;
                }
            }
        } else if (ARR::isNumericArray($datum)) {
            $_arr = array();
            foreach($datum as $_datum) {
                $_where = self::where($_datum, $value_or_values, $strict);
                if (is_null($_where) == false && is_null($_datum) == false) {
                    $_arr[] = $_datum;
                } else if (is_null($_where) && is_null($_datum)) {
                    $_arr[] = $_datum;
                }
            }
            return $_arr;
        } else if ($strict && $datum === $value_or_values || $strict == false && $datum == $value_or_values) {
            return $datum;
        } else {
            return null;
        }
    }

    public static function exists($arr, $names, $ignore_case = false) {
        $default = false;
        if (is_array($arr)) {
            if (!is_array($names)) {
                $names = array($names);
            }
            if ($ignore_case) {
                $arr = ARR::toLowercase($arr);
                $names = ARR::toLowercase($names);
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
            return APP::exists((array) $arr, $names, $ignore_case);
        } else {
            return false;
        }
    }

    public static function iset(&$arr_or_object, $names, $value) { 
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
                    $arr_or_object = isset($arr_or_object->$name) ? $arr_or_object->$name : new stdClass();

                    self::set($arr_or_object, $names, $value);
                } else {
                    $_arr_or_object = isset($arr_or_object[$name]) ? $arr_or_object[$name] : array();

                    self::set($_arr_or_object, $names, $value);

                    $arr_or_object[$name] = $_arr_or_object;
                }
            }
        }
        return $arr_or_object;
    }

    public static function ihas($arr_or_object, $names) {
        return is_null(iget($arr_or_object, $names)) == false ? true : false;
    }

    public static function has($arr_or_object, $names) {
        return is_null(get($arr_or_object, $names)) == false ? true : false;
    }

    public static function iget($arr, $names, $default = null, $req_is_not_empty = false) {
        return self::get($arr, $names, $default, $req_is_not_empty, false);
    }

    public static function get_and_require() {
        $args = func_get_args();
        if (count($args) < 2) { 
            throw new CustomException('get_and_require requires at least two parameters');
        }

        $data = array_shift($args);
        $name = array_shift($args);

        if (is_array($name)) { 
            $args = $name;
            $name = array_shift($args);
        }

        if (is_array($data)) { 
            if (isset($data[$name]) == false) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
            }

            $data = $data[$name];
        } else if (is_object($data)) { 
            if (!isset($data->$name)) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
                }
                $data = $data->$name;
            } else if ($name) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
            } else { 
                throw new CustomException('Missing name');
            }

            if (!$args) { 
                return $data;
            }

            return self::get_and_require($data, $args);
        }

    public static function iget_and_require() {
        $args = func_get_args();
        if (count($args) < 2) { 
            throw new CustomException('iget_and_require requires at least two parameters');
        }

        $data = array_shift($args);
        $name = array_shift($args);

        if (is_array($name)) { 
            $args = $name;
            $name = array_shift($args);
        }

        if (is_array($data)) { 
            $name = strtolower($name);
            $data = ARR::keysToLowercase($data, true);

            if (isset($data[$name]) == false) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
            }

            $data = $data[$name];
        } else if (is_object($data)) { 
            $property = APP::getClassProperty($data, $name, false);
            if (!$property->name) { 
                throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
            }
            $name = $property->name;
            $data = $data->$name;
        } else if ($name) { 
            throw new CustomException('No ', STRING::toLowercaseSeparatedBy($name));
        } else { 
            throw new CustomException('Missing name');
        }

        if (!$args) { 
            return $data;
        }

        return self::iget_and_require($data, $args);
    }

    /**
    public static function get($arr, $names, $default = null, $req_is_not_empty = false, $case_sensitive = true) {
        if (is_array($arr)) {
            if (!$case_sensitive) {
                $arr = ARR::keysToLowercase($arr, true);
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

    **/

    public static function get($arr, $names, $default = null, $req_is_not_empty = false, $case_sensitive = true) {
        if (is_array($arr)) {
            if (!$case_sensitive) {
                $arr = ARR::keysToLowercase($arr, true);
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
            throw new \InvalidArgumentException("Value for $name must be a associative array");
        }
    }

    public static function reqNumericArray($value, $name = "parameter") {
        if (!ARR::isNumericArray($value)) {
            throw new \InvalidArgumentException("Value for $name must be a numeric array");
        }
    }

    public static function reqValue($value, $name = "parameter") {
        if (APP::isEmpty($value)) {
            throw new \InvalidArgumentException("Missing value for $name");
        }
    }

    public static function ireq($arrOrObj, $names, $reqNotEmpty = false) {
        return self::req($arrOrObj, $names, $reqNotEmpty, true);
    }

    public static function req($arrOrObj, $names, $reqNotEmpty = false, $ignore_case = false) {
        $names = APP::toArray($names);
        $avail = false;
        foreach ($names as $name) {
            $avail = APP::exists($arrOrObj, $name, $ignore_case);
            if ($avail && $reqNotEmpty) {
                $avail = APP::isEmpty(APP::get($arrOrObj, $name)) === false;
            }
            if (!$avail && defined('__APP_FAIL_STOP__') && __APP_FAIL_STOP__) {
                die('req failed for ' . $name . ' in data structure ' . print_r($arrOrObj, true));
            }
            if (!$avail) {
                throw new \InvalidArgumentException("Missing value " . $name);
            }
        }
        return $avail;
    }

    public static function reqThis($obj, $reqNotEmpty = false) {
        $ref_class = new \ReflectionClass($obj);
        if ($ref_class->getProperties(ReflectionProperty::IS_PUBLIC)) {
            foreach ($ref_class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $name = $property->getName();

                APP::req($obj, $name, $reqNotEmpty);
            }
        }
    }

    public static function now() {
        return strftime('%y-%m-%dT%H:%M:%S');
    }

    public static function isEmpty($var) {
        return empty($var) && $var !== 0 || is_object($var) && !get_object_vars($var);
    }

    public static function objectToArray(&$object) {
        $object = (array) $object;
        if ($object) {
            foreach ($object as &$value) {
                if (is_object($value)) {
                    $value = self::objectToArray($value);
                } else if (is_array($value)) {
                    $value = self::objectToArray($value);
                }
            }
        }
        return $object;
    }

    public static function toArray($value, $ignoreNull = true) {
        if (!$ignoreNull || is_null($value) == false) {
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
        if (is_array($arrayKey)) {
            return self::_toMap($twoDimArray, $arrayKey);
        }
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
        if (is_array($arrayKey)) {
            return self::_toMapArray($twoDimArray, $arrayKey);
        }
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

    private static function _toMap($arr, $keys) {
        if (!$keys) {
            return array('0' => $arr);
        }
        $arr = ARR::wrapToNumericArray($arr);

        $map = array();
        if ($arr) {
            foreach ($arr as $element) {
                $index = '';
                foreach($keys as $key) {
                    $value = APP::get($element, $key);
                    if (is_null($value)) {
                        break;
                    }

                    $index .= '[';
                    $index .= "'" . strval($value) . "'";
                    $index .= ']';
                }

                $code = '$map' . $index . ' = $element;';
                eval($code);
            }
        }
        return $map;
    }

    private static function _toMapArray($arr, $keys) {
        if (!$keys) {
            return array('0' => $arr);
        }

        $arr = ARR::wrapToNumericArray($arr);

        $map = array();
        if ($arr) {
            foreach ($arr as $element) {
                $index = '';
                foreach($keys as $key) {
                    $value = APP::get($element, $key);
                    if (is_null($value)) {
                        $index = null;
                        break;
                    }
                    $index .= '[';
                    $index .= "'" . strval($value) . "'";
                    $index .= ']';
                }
                if (is_null($index) == false) { 
                    $index .= '[]';

                    $code = '$map' . $index . ' = $element;';

                    eval($code);
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

    public static function requireDir($dir, $recursive = true) {
        if (!file_exists($dir)) {
            throw new CustomException('Directory ', $dir, ' not exists');
        }
        $dh = opendir($dir);
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $path_or_uri = PATH::combine($dir, $file);
            if (is_dir($path_or_uri) && $recursive) {
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

    public static function getGUID($enclose_with_braces = false) {
        if (function_exists('com_create_guid')) {
            $guid = com_create_guid();
            if ($enclose_with_braces == false) {
                $guid = substr($guid, 1, strlen($guid)-2);
            }
            return $guid;
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = ($enclose_with_braces ? chr(123) : '') // "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12)
                . ($enclose_with_braces ? chr(125) : ''); // "}"
            return $uuid;
        }
    }

    public static function chained_call_user_func($callables, $root) {
        ##########################################################################################
        # $callables = array(
        #       $callable,
        #       ...,
        # );
        # $root = [ mixed ] 
        ##########################################################################################
        for ($i = 0; $i < count($callables); $i++) {
            $callable = $callables[$i];

            if ($i == 0) {
                if (is_null($root) == false) {
                    $value = call_user_func(array($root, $callable));
                } else {
                    $value = call_user_func($callable);
                }
            } else {
                $value = call_user_func($callable, $value);
            }
        }

        return $value;
    }

    public static function throwIfFalse($value) {
        if ($value == false) {
            throw new CustomException('Operation required a valid return value: ', $value, ' given');
        }
        return $value;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    //public static function toJSON($value) {
        //$json = json_encode($value);
        //$json = preg_replace("/[\r\n]/", '', $json);
        //$json = iconv("utf-8", "utf-8//IGNORE", $json);
        //return $json;
    //}
    //public static function toXML($value) {
        //$xml = XML::toXml($value); // replace newline?
        //$xml = iconv("utf-8", "utf-8//IGNORE", $xml);
        //return $xml;
    //}

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getCombinations($values, $options = null) {
        require_num($values);

        return self::_getCombinations($values, count($values), $options);
    }

    private static function _getCombinations($values, $len, $options) { 
        if ($len == 0) { 
            return array();
        } else if ($len == 1) { 
            if (get_or_null($options, 'ordered')) { 
                sort($values);
            }
            $_values = array();
            foreach($values as $v) { 
                $_values[] = array($v);
            }
            return $_values;
        } else {
            $_values = self::_getCombinations($values, $len-1, $options);

            $allow_repetition = get_or_null($options, 'allow_repetition');
            $ordered = get_or_null($options, 'ordered');

            $c = array();
            $c = array_merge($c, $_values);

            for ($i = 0; $i < count($c); $i++) { 
                $_c = $c[$i];

                for ($j = 0; $j < count($values); $j++) { 
                    if ($allow_repetition == false && in_array($values[$j], $_c)) { 
                        continue;
                    }
                    $__c = $_c;
                    $__c[] = $values[$j];

                    if ($ordered) { 
                        sort($__c);

                        if (in_array($__c, $_values)) { 
                            continue;
                        }
                    }
                    $_values[] = $__c;
                }
            }

            return $_values;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getBackTrace() { 
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        return $trace;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function compare($lhs, $rhs, $options = null) { 
        if ($lhs === $rhs) { 
            return 0;
        } else if (get_or_null($options, 'strict') == false && $lhs == $rhs) { 
            return 0;
        } else if (is_array($lhs) && is_array($rhs)) { 
            return ARR::compare($lhs, $rhs, $options);
        } else if (is_object($lhs) && is_object($rhs)) { 
            return ARR::compare((array)$lhs, (array)$rhs);
        } else { 
            return $lhs < $rhs ? -1 : 1;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function toHashKey($data) { 
        $key = self::_toHashKey($data);

        $hash_key = md5(implode('|', $key));

        return $hash_key;
    }

    private static function _toHashKey($data) { 
        if (is_null($data)) { 
            return array('__null__');
        } else if (is_num($data)) { 
            $key = array();
            foreach($data as $idx => $datum) { 
                $key[] = $idx;

                $key = array_merge($key, self::_toHashKey($datum));
            }
            return $key;
        } else if (is_assoc($data)) { 
            $key = array();
            foreach($data as $name => $datum) { 
                $key[] = $name;

                $key = array_merge($key, self::_toHashKey($datum));
            }
            return $key;
        } else if (is_object($data)) { 
            $key = array($data->toHashKey());

            return $key;
        } else {
            return array($data);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class ARR 
{

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createArray($count, $default_value = null) {
        $arr = array();
        for ($i = 0; $i < $count; $i++) {
            $arr[] = $default_value;
        }
        return $arr;
    }

    public static function createObjectArray($count, $class_name) {
        $arr = array();
        for ($i = 0; $i < $count; $i++) {
            $arr[] = new $class_name();
        }
        return $arr;
    }

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

    public static function stripos($needle, $haystack) {
        if (!$haystack) {
            return false;
        }
        if (!$needle) {
            return false;
        }
        if (is_array($haystack) == false) {
            $haystack = array($haystack);
        }
        if (is_string($needle) == false) {
            $needle = strval($needle);
        }
        foreach($haystack as $index => $value) {
            if (is_string($value) == false) {
                $value = strval($value);
            }
            if (stripos($value, $needle) !== false) {
                return $index;
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

    public static function keysToLowercase(&$arr, $ignore_numeric_arrays = false) {
        if (!$arr) {
            return $arr;
        }
        if (!ARR::isAssociativeArray($arr) && $ignore_numeric_arrays == false) {
            throw new CustomException('Array must be associative: ', $arr);
        }
        if (ARR::isAssociativeArray($arr)) {
            foreach ($arr as $name => $value) {
                if (strtolower($name) != $name) {
                    $arr[strtolower($name)] = $value;
                    unset($arr[$name]);
                }
            }
        }
        return $arr;
    }

    public static function toLowercase(&$arr) {
        if (!$arr) {
            return $arr;
        }
        if (ARR::isAssociativeArray($arr)) {
            return self::keysToLowercase($arr);
        } else if (ARR::isNumericArray($arr)) {
            if (array_walk($arr, function (&$element, $idx) {
                        if (is_string($element)) {
                            $element = strtolower($element);
                        }
                    }) === false) {
                throw new CustomException('Unable to lowercase array');
            }
        }
        return $arr;
    }

    public static function isAssociativeArray($var) {
        return is_array($var) && ( ($count = count($var)) == false || array_key_exists(0, $var) == false || array_key_exists($count-1, $var) == false);
        /*
        if (!is_array($var)) {
            return false;
        }
        if (!$var) {
            return true;
        }
        //return is_array($var) && array_keys($var) !== range(0, sizeof($var) - 1); 
        return self::isNumericArray($var) == false;
         */
    }

    public static function isNumericArray($var) {
        return is_array($var) && ($var == false || (array_key_exists(0, $var) && array_key_exists(count($var)-1, $var)) );
    }

    public static function wrapToNumericArray(&$arr) {
        if (is_object($arr)) {
            return array($arr);
        } else if (is_array($arr) == false) {
            return array($arr);
        } else if (!$arr) { 
            return $arr; // -- do not wrap empty array
        } else if (ARR::isAssociativeArray($arr)) {
            if (array_key_exists(0, $arr)) {
                foreach (array_keys($arr) as $index) {
                    if (is_numeric($index)) {
                        unset($arr[$index]);
                    }
                }
            }
            return array($arr);
        }
        return $arr;
    }

    public static function toNumericArrays($arr) {
        $_arr = array();
        if (ARR::isNumericArray($arr)) {
            for ($i = 0; $i < count($arr); $i++) {
                $entry = self::toNumericArray($arr[$i]);

                $_arr[$i] = $entry;
            }
        }
        return $_arr;
    }

    public static function toNumericArray($arr) {
        if (is_object($arr)) {
            $arr = (array) $arr;
        } else if (is_array($arr) == false) {
            $arr = array($arr);
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

    public static function toAssociativeArray($arr_or_object) {
        if (is_object($arr_or_object)) {
            $arr_or_object = (array) $arr_or_object;
        }
        if (ARR::isNumericArray($arr_or_object)) {
            foreach ($arr_or_object as $index => &$value) {
                $value = ARR::toAssociativeArray($value);
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

    public static function toArray($arr_or_object) {
        if (is_object($arr_or_object)) {
            $arr_or_object = (array) $arr_or_object;
        }
        if (ARR::isNumericArray($arr_or_object)) {
            for ($i = 0; $i < count($arr_or_object); $i++) {
                $_value = ARR::toArray($arr_or_object[$i]);

                $arr_or_object[$i] = $_value;
            }
        } else if (is_array($arr_or_object)) {
            foreach ($arr_or_object as $index => &$value) {
                if (is_object($value) || is_array($value)) {
                    $value = ARR::toArray($value);
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

    public static function project($arr, $columns, $singletize = true) {
        if (is_null($arr)) {
            return null;
        } else if (!$arr) {
            return $arr;
        } else if (ARR::isAssociativeArray($arr) || is_object($arr)) {
            $_arr = array();
            foreach($arr as $name => $value) {
                if (ARR::inArray($name, $columns)) {
                    $_arr[$name] = $value;
                }
            }
            if (count($_arr) == 1) {
                $_arr = self::singletize($_arr);
            }
            return $_arr;
        } else if (ARR::isNumericArray($arr)) {
            $_arr = array();
            for ($i = 0; $i < count($arr); $i++) {
                $_arr[] = self::project($arr[$i], $columns, $singletize);
            }
            if ($_arr && $singletize) {
                $_arr = self::singletize($_arr);
            }
            return $_arr;
        } else {
            throw new CustomException('Unsupported value type: ', $arr);
        }
    }

    public static function singletize($arr) {
        if (is_null($arr)) {
            return null;
        } else if (!$arr) {
            return $arr;
        } else if (ARR::isAssociativeArray($arr)) {
            if (count($arr) == 1) {
                $keys = array_keys($arr);
                return $arr[$keys[0]];
            } else {
                throw new CustomException('Expected associative array with 1 key: ', $arr);
            }
        } else if (ARR::isNumericArray($arr)) {
            $_arr = array();
            for ($i = 0; $i < count($arr); $i++) {
                $_arr[] = self::singletize($arr[$i]);
            }
            return $_arr;
        } else {
            return $arr;
        }
    }

    public static function each($arr, $callable) {
        if (is_null($arr)) {
            return null;
        } else if (!$arr) {
            return $arr;
        } else if (ARR::isNumericArray($arr)) {
            $_arr = array();
            foreach($arr as $index => $value) {
                $_arr[] = call_user_func($callable, $value);
            }
            return $_arr;
        } else if (ARR::isAssociativeArray($arr)) {
            $_arr = array();
            foreach($arr as $name => &$value) {
                $value = call_user_func($callable, $value);
            }
            return $_arr;
        } else {
            return $arr;
        }
    }

    public static function walk($arr, $callable) {
        ################################################################################
        # useful if
        # (1) array has collection of objects and callable is a method of these objects
        # (2) array has collection of values and callable takes an argument of these values
        #
        # walk is recursive while each is not
        ################################################################################
        if (is_null($arr)) {
            return null;
        } else if (!$arr) {
            return $arr;
        } else if (!$callable) {
            return null;
        } else if (ARR::isAssociativeArray($arr)) {
            foreach($arr as $name => &$value) {
                $value = self::walk($value, $callable);
            }
        } else if (ARR::isNumericArray($arr)) {
            $_arr = array();
            for ($i = 0; $i < count($arr); $i++) {
                $_arr[] = self::walk($arr[$i], $callable);
            }
            return $_arr;
        } else if (is_object($arr)) {
            return call_user_func(array($arr, $callable));
        } else {
            return call_user_func($callable, $arr);
        }
    }

    public static function chained_walk($arr, $callables) {
        if (is_null($arr)) {
            return null;
        } else if (!$arr) {
            return $arr;
        } else if (!$callables) {
            return null;
        } else if (is_object($arr)) {
            return APP::chained_call_user_func($callables, $arr);
        } else if (ARR::isAssociativeArray($arr)) {
            foreach($arr as $name => &$value) {
                $value = APP::chained_call_user_func($callables, $value);
            }
        } else if (ARR::isNumericArray($arr)) {
            $_arr = array();
            for ($i = 0; $i < count($arr); $i++) {
                $_arr[] = self::chained_walk($arr[$i], $callables);
            }
            return $_arr;
        } else {
            return $arr;
        }
    }

    public static function toPaths($arr, $options = null) {
        ########################################################################
        # $options = array(
        #       'exclude_trailing_numeric_indices' => bool,
        # )
        ########################################################################
        $paths = array();

        if (is_null($arr)) {
            return $paths;
        } else if (is_array($arr) == false) {
            return $paths;
        } else if (ARR::isAssociativeArray($arr) || is_object($arr)) {
            foreach($arr as $name => $value) {
                $value_paths = ARR::toPaths($value, $options);

                if ($value_paths) {
                    foreach($value_paths as $value_path) {
                        $_path = array_merge(array($name), $value_path);

                        $paths[] = $_path;
                    }
                } else {
                    $paths[] = array($name);
                }
            }
        } else if (ARR::isNumericArray($arr)) {
            foreach($arr as $index => $value) {
                $value_paths = ARR::toPaths($value, $options);

                if ($value_paths) {
                    foreach($value_paths as $value_path) {
                        if (get($options, 'exclude_trailing_numeric_indices') == false || $value_path) {
                            $_path = array_merge(array(strval($index)), $value_path);

                            $paths[] = $_path;
                        }
                    }
                } else {
                    if (get($options, 'exclude_trailing_numeric_indices')) {
                        $paths[] = array(strval($index));
                    }
                }
            }
        }
        return $paths;
    }

    public static function getKey($arr_or_object, $key, $ignore_case = true) {
        if (is_null($arr_or_object)) { 
            return null;
        } else if (is_object($arr_or_object) || is_array($arr_or_object)) { 
            $_key = $ignore_case ? strtolower($key) : $key;
            foreach($arr_or_object as $name => $value) {
                $_name = $ignore_case ? strtolower($name) : $name;
                if ($_name == $_key) {
                    return $name;
                }
            }
        } else {
            throw new CustomException('Invalid data type for getKey: ', $arr_or_object);
        }
        return null;
    }

    public static function destruct($arr_or_object, $keys, $ignore_case = true) {
        if (is_array($keys)) {
            $is_num = false;

            foreach($keys as $key) {
                $key = self::getKey($arr_or_object, $key, $ignore_case);

                if (is_null($key) == false) {
                    if (is_object($arr_or_object)) {
                        unset($arr_or_object->$key);
                    } else {
                        if ($is_num == false && is_num($arr_or_object)) { 
                            $is_num = true;
                        }
                        unset($arr_or_object[$key]);

                        $arr_or_object = $arr_or_object;
                    }
                }
            }

            if ($is_num) { 
                $arr_or_object = array_values($arr_or_object);
            }
        } else {
            $key = self::getKey($arr_or_object, $keys, $ignore_case);

            if (is_null($key) == false) {
                if (is_object($arr_or_object)) {
                    unset($arr_or_object->$key);
                } else {
                    $is_num = is_num($arr_or_object);

                    unset($arr_or_object[$key]);

                    if ($is_num) { 
                        $arr_or_object = array_values($arr_or_object);
                    }
                }
            }
        }

        return $arr_or_object;
    }

    public static function merge($target, $source, $replace = true) { 
        //-- the default array_merge_recursive appends two existing key values (making them arrays if both are not)

        assert(is_array($target));
        assert(is_array($source));

        if (ARR::isAssociativeArray($target)) { 
            if (ARR::isAssociativeArray($source) == false) { 
                throw new CustomException('Both arrays must be associative');
            }

            foreach($source as $name => $value) { 
                if (isset($target[$name]) && $replace) { 
                    if (is_array($target[$name])) { 
                        if (is_array($value)) { 
                            //-- this throws an exception if the array types differ
                            $target[$name] = self::merge($target[$name], $value);
                        }  else if (ARR::isNumericArray($target[$name])) { 
                            $target[$name][] = $value;
                        }
                    } else if ($replace) { 
                        $target[$name] = $value;
                    } else { 
                        //-- keep original
                    }
                } else { 
                    $target[$name] = $value;
                }
            }
        } else if (ARR::isNumericArray($target)) { 
            if (ARR::isNumericArray($source) == false) { 
                throw new CustomException('Both arrays must be numeric');
            }

            $target = array_merge($target, $source);
        }

        return $target;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////


    public static function remove($arr, $el, $options = null) { 
        if (!$arr) { 
            return $arr;
        }
        if (is_null($el)) { 
            return $arr;
        }

        $key = self::search($arr, $el, $options);
        if ($key !== false) { 
            unset($arr[$key]);

            if (is_int($key)) { 
                $arr = array_values($arr);
            }
        }

        return $arr;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function search($arr, $el, $options = null) { 
        if (!$arr) { 
            return false;
        }
        foreach($arr as $key => $value) { 
            $result = APP::compare($value, $el, $options);
            if ($result === 0) {
                return $key;
            }
        }
        return false;
    }
    
    public static function search_strict($arr, $el, $options = null) { 
        if (!$arr) { 
            return false;
        }
        foreach($arr as $key => $value) { 
            $result = APP::compare_strict($value, $el, $options);
            if ($result === 0) {
                return $key;
            }
        }
        return false;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function compare($lhs, $rhs, $options = null) {
        if (is_array($lhs) == false) { 
            return -1;
        } else if (is_array($rhs) == false) {
            return 1;
        } else if (count($lhs) != count($rhs)) { 
            return count($lhs) < count($rhs) ? -1 : 1;
        } else if (is_num($lhs) && is_num($rhs)) { 
            for ($i = 0; $i < count($lhs); $i++) { 
                $lhs_ = $lhs[$i];
                $rhs_ = $rhs[$i];

                $result = APP::compare($lhs_, $rhs_, $options);
                if ($result != 0) { 
                    return $result;
                }
            }
            return 0;
        } else if (is_assoc($lhs) && is_assoc($rhs)) { 
            $lhs_keys = array_keys($lhs);
            $rhs_keys = array_keys($rhs);

            $result = APP::compare($lhs_keys, $rhs_keys);
            if ($result != 0) { 
                return $result;
            }
            for ($i = 0; $i < count($lhs_keys); $i++) { 
                $key = $lhs_keys[$i];

                $lhs_ = $lhs[$key];
                $rhs_ = $rhs[$key];

                $result = APP::compare($lhs_, $rhs_, $options);
                if ($result != 0) { 
                    return $result;
                }
            }
            return 0;
        } else if (is_num($lhs)) { 
            return -1;
        } else { 
            return 1;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class CONVERT 
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function typify(& $value, $options = null) { 
        if (is_null($value)) { 
            return $value;
        } else if (is_string($value)) { 
            if (is_numeric($value)) { 
                if (self::isFloat($value)) { 
                    return $value = self::toFloat($value);
                } else if (self::isInt($value)) { 
                    return $value = self::toInt($value);
                } else { 
                    throw new CustomException('Unrecognized numeric value: ', $value);
                }
            } else if (self::isBool($value)) { 
                return self::toBool($value);
            } else if ($value === '' && get($options, 'nullify_empty_strings')) { 
                return null;
            } else if ($value === 'null' && get($options, 'nullify_null_strings')) { 
                return null;
            } else { 
                if (get_or_null($options, 'urldecode')) { 
                    return urldecode($value);
                } else { 
                    return $value;
                }
            }
        } else if (is_array($value) || is_object($value)) { 
            foreach($value as $key => & $_value) { 
                $_value = self::typify($_value, $options);
            }
            return $value;
        } else { 
            return $value;
        }
    }

    public static function isBool($value) { 
        return is_bool($value) || in_array($value, array('true', 'false', '1', '0', 'yes', 'no'));
    }

    public static function isFloat($value) { 
        return is_float($value) || is_numeric($value) && stripos(strval($value), '.') !== false;
    }

    public static function isInt($value) { 
        return is_int($value) || is_numeric($value) && stripos(strval($value), '.') === false;
    }

    public static function toBool($value) {
        if (is_null($value)) {
            return $value;
        } else if (is_bool($value)) {
            return $value;
        } else if (is_string($value)) {
            $value = strtolower($value);

            if ($value == 'no' || $value == 'false') {
                return false;
            } else if ($value == 'yes' || $value == 'true') {
                return true;
            } else {
                return $value != 0 ? true : false;
            }
        } else if (is_numeric($value)) {
            return $value != 0 ? true : false;
        } else if (ARR::isNumericArray($value)) {
            for ($i = 0; $i < count($value); $i++) {
                $value[$i] = self::toBool($value[$i]);
            }
            return $value;
        } else {
            return self::toBool(strval($value));
        }
    }

    public static function toInt($value, $options = null) {
        if (is_null($value)) {
            return $value;
        } else if (is_int($value)) {
            return $value;
        } else if (is_bool($value)) {
            return intval($value);
        } else if (is_numeric($value)) {
            return intval($value);
        } else if (is_string($value)) {
            return get_or_default($options, 'default', null);
        } else if (is_num($value)) {
            for ($i = 0; $i < count($value); $i++) {
                $value[$i] = self::toInt($value[$i], $options);
            }
            return $value;
        } else {
            $invalid_callback = get_or_null($options, 'invalid_callback');
            if ($invalid_callback) { 
                return $invalid_callback($value, $options);
            }
            throw new TypeException($value);
        }
    }

    public static function toFloat($value) {
        if (is_null($value)) {
            return $value;
        } else if (is_float($value)) {
            return $value;
        } else if (is_numeric($value)) {
            return floatval($value);
        } else if (ARR::isNumericArray($value)) {
            for ($i = 0; $i < count($value); $i++) {
                $value[$i] = self::toFloat($value[$i]);
            }
            return $value;
        } else if (is_string($value)) {
            return null;
        } else {
            throw new CustomException('Either not implemented or invalid!');
        }
    }

    public static function toString($value) {
        if (is_null($value)) {
            return $value;
        } else if (is_string($value)) {
            return $value;
        } else if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else if (is_numeric($value)) {
            return strval($value);
        } else if (is_num($value)) {
            for ($i = 0; $i < count($value); $i++) {
                $value[$i] = self::toString($value[$i]);
            }
            return $value;
        } else {
            return strval($value);
        }
    }

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class PATH 
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

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
        static $ds = '/';

        $path = '';
        for ($i = 0; $i < func_num_args(); $i++) {
            $arg = func_get_arg($i);
            if (is_null($arg)) { 
                continue;
            } else if (is_num($arg)) { 
                foreach($arg as $arg_) { 
                    $path = $path == '' ? $arg_ : self::combine($path, $arg_);
                }
            } else { 
                $arg = rtrim($arg, '/\\');
                if ($path) { 
                    if ($arg[0] === '/' || $arg[0] === '\\') { 
                        $arg = substr($arg, 1);
                    }
                    $path .= $ds;
                }
                $path .= $arg;
            }
        }
        return $path;
    }

    public static function enumerateFiles($path, $filter = '*', $recursive = false) {
        if (is_array($path)) { 
            $paths = array();
            foreach($path as $path_) { 
                $paths = array_merge($paths, self::enumerateFiles($path_, $filter, $recursive));
            }
            return $paths;
        } else { 
            APP::reqValue($path);
            if (!safe_file_exists($path)) {
                return array();
                //throw new CustomException('Directory ', $path, ' not exists');
            }
            if ($recursive) { 
                $dirs = self::enumerate_directories($path);
                array_unshift($dirs, $path);

                $paths = array();
                foreach($dirs as $dir) { 
                    $paths = array_merge($paths, self::enumerateFiles($dir, $filter));
                }
                return $paths;
            } else { 
                $dh = false;
                for ($i = 0;$i < 3 && $dh == false; $i++) { 
                    if ($i > 0) { 
                        usleep($i*$i);
                    }
                    $dh = @opendir($path);
                }
                if (!$dh) {
                    throw new CustomException('Unable to open directory handle to ', $path);
                }
                $uris = array();
                while (($filename = readdir($dh)) !== false) {
                    if ($filename == '.' || $filename == '..') {
                        continue;
                    }
                    if (PATH::matchAny($filename, $filter)) {
                        $uri = PATH::combine($path, $filename);

                        if (is_dir($uri)) { 
                            continue;
                        }
                        $uris[] = $uri;
                    }
                }
                closedir($dh);
                return $uris;
            }
        }
    }

    public static function enumerate_directories($path, $filter = '*', $recursive = true) {
        if (is_num($path)) { 
            $result = array();
            foreach($path as $path_) { 
                $result = array_merge($result, self::enumerate_directories($path_, $filter, $recursive));
            }
            return $result;
        } else { 
            safe_require_dir($path);

            $dh = false;
            for ($i = 0;$i < 3 && $dh == false; $i++) { 
                if ($i > 0) { 
                    usleep($i*$i);
                }
                $dh = @opendir($path);
            }
            if (!$dh) {
                throw new CustomException('Unable to open directory handle to ', $path);
            }
            $directories = array();
            while (($filename = readdir($dh)) !== false) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }
                $path_ = PATH::combine($path, $filename);
                if (is_dir($path_) == false) { 
                    continue;
                }
                if (PATH::matchAny($filename, $filter)) {
                    $directories[] = $path_;
                }
            }
            closedir($dh);

            if ($recursive) { 
                for ($i = 0; $i < count($directories); $i++) { 
                    $directories_ = self::enumerate_directories($directories[$i], $filter, $recursive);
                    array_splice($directories, $i+1, 0, $directories_);
                    $i += count($directories_);
                }
            }

            return $directories;
        }
    }

    public static function deleteFiles($path, $filter = '*') {
        foreach (PATH::enumerateFiles($path, $filter) as $uri) {
            if (unlink($uri) === false) {
                throw new CustomException('Unable to delete ', $uri);
            }
        }
    }

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class STRING 
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    const SINGLE_NEWLINE = "\n";

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function equals($lhs, $rhs, $ignore_case = false) {
        if (is_null($lhs) && is_null($rhs)) {
            return true;
        } else if (is_null($lhs) || is_null($rhs)) {
            return false;
        } else if ($ignore_case == false) {
            return $lhs === $rhs;
        } else {
            return strtolower($lhs) === strtolower($rhs);
        }
    }

    public static function cast($str) {
        if (is_string($str) == false) {
            throw new CustomException('Cast for strings only supports input values with type string');
        }
        if (!is_numeric($str)) {
            return $str;
        }

        if (stripos($str, '.') !== false) {
            return floatval($str);
        } else if (strtoupper($str) === 'TRUE' || strtoupper($str) === 'FALSE') {
            return boolval($str);
        } else {
            return intval($str);
        }
    }

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

    public static function startsWith($str, $sub_string, $case_sensitive = true) {
        return self::indexOf($str, $sub_string, $case_sensitive) === 0;
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

    public static function toLowercaseSeparatedBy($str, $separator = ' ') {
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
        $str = strtolower(preg_replace('/(.)([A-Z])/', '$1' . $separator . '$2$3', $prepared));

        return $str;
    }

    public static function toCamelCase($str, $uppercase_first_character = false, $options = null) {
        if (!$str) {
            return $str;
        }
        $separator = get($options, 'separator', '');

        $separators = array(
            '.',
            ' ',
        );
        $removals = array(
            '-',
            '_',
        );

        $start_of_word = $uppercase_first_character;
        for ($i = 0; $i < strlen($str); $i++) { 
            $chr = $str[$i];

            if ($start_of_word) { 
                $str[$i] = ucfirst($chr);
                if ($i > 0) { 
                    $str = substr_replace($str, $separator, $i, 0);
                }
            }
            if (in_array($chr, $separators)) { 
                $start_of_word = true;
            } else { 
                $start_of_word = false;
            }
            if (in_array($chr, $removals)) { 
                $str = substr_replace($str, '', $i, 1);

                //if ($i < strlen($str)) { 
                    //$str[$i] = ucfirst($str[$i]);
                //}
                $start_of_word = true;

                $i--;
            }
        }

        return $str;
    }

    public static function from($str, $end, $case_sensitive = true) {
        $index_of = self::indexOf($str, $end, $case_sensitive);
        if ($index_of !== false) {
            return substr($str, $index_of);
        }
        return '';
    }

    public static function fromLast($str, $end, $case_sensitive = true) {
        $last_index_of = self::lastIndexOf($str, $end, $case_sensitive);
        if ($last_index_of !== false) {
            return substr($str, $last_index_of);
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

    public static function after($str, $end, $case_sensitive = true) {
        return substr(self::from($str, $end, $case_sensitive), strlen($end));
    }

    public static function afterLast($str, $end, $case_sensitive = true) {
        return substr(self::fromLast($str, $end, $case_sensitive), strlen($end));
    }

    public static function until($str, $end, $case_sensitive = true) {
        if (is_null($str)) { 
            return '';
        } else { 
            $splits = self::splitBy($str, $end, $case_sensitive);
            return $splits ? $splits[0] : $str;
        }
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
        return strrpos($str, $sub_string);
    }

    public static function indexOf($str, $sub_string, $case_sensitive = true) {
        return $case_sensitive ? strpos($str, $sub_string) : stripos($str, $sub_string);
    }

    public static function splitBy($str, $bys, $case_sensitive = true, $remove_empty_parts = false, &$set_of_used_bys = false) {
        if (!is_string($str)) {
            throw new \InvalidArgumentException('Invalid str');
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

    public static function untemplatize($template, $entity, $variable_map) {
        if (ARR::isNumericArray($variable_map)) {
            $variables = $variable_map;
        } else if (ARR::isAssociativeArray($variable_map)) {
            $variables = array_keys($variable_map);
        } else {
            throw new CustomException('Unrecognized variable map: ', $variable_map);
        }
        foreach ($variables as $var) {
            $value = APP::get($entity, $var, '');
            $template = str_ireplace('{{' . $var . '}}', $value, $template);
        }
        if (($missing_variable = STRING::inBetween($template, '{{', '}}'))) {
            throw new CustomException('Missing variable for ', $missing_variable);
        }
        return $template;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getSizeInBytes($string) { 
        return strlen($string);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getRandomText($length = null) { 
        $len = is_null($length) ? (rand() % 20)+1 : $length;

        $len = max($len, 0);

        $text = '';

        for ($i = 0; $i < $len; $i++) { 
            $ch = chr(ord('a') + rand()%27);
            if (rand()%3 == 1) { 
                $ch = strtoUpper($ch);
            }
            $text .= $ch;
        }

        return $text;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getIndent($indent = 0) { 
        return str_pad('', $indent);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getLine($string, $offset = 0, $options = null, & $info = false) { 
        $include_newline = get_or_null($options, 'include_newline');

        if (is_empty_string($string)) { 
            if ($include_newline) { 
                return PHP_EOL;
            } else { 
                return '';
            }
        }

        $i = $offset >= 0 ? $offset : strlen($string)-$offset;

        if ($i < strlen($string)) { 
            if ($string[$i] === self::SINGLE_NEWLINE) {
                $i--; /// i at single newline returns current line
            }
            while ($i > 0 && $string[$i] !== self::SINGLE_NEWLINE) $i--;
            if ($i > 0) { 
                $i++; 
            }
        }

        $offset = $i;

        if ($info !== false) {
            $info['offset'] = $offset;
        }

        while ($i < strlen($string) && $string[$i] !== self::SINGLE_NEWLINE) $i++;

        if ($i >= strlen($string)) { 
            if ($include_newline) { 
                return substr($string, $offset, $i-$offset) . PHP_EOL;
            } else { 
                return substr($string, $offset, $i-$offset);
            }
        } else { 
            if ($include_newline) { 
                return substr($string, $offset, $i+1-$offset);
            } else { 
                if ($i > 0 && $string[$i-1] === "\r") { 
                    $i--; 
                }
                return substr($string, $offset, $i-$offset);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function quote($string, $options = null) { 
        if (is_empty_string($string)) { 
            return '';
        }
        static $symbols = array(
            "'",
            '"',
            '\\',
        );

        $quote_escaped_symbols = get_or_default($options, 'quote_escaped_symbols', false);  /// escaped symbols are two characters e.g. \a, \', they are both not escaped to prevent double quotes

        if (get_or_null($options, 'quote_single_quotes')) { 
            $targets = array("'");
        } else if (get_or_null($options, 'quote_double_quotes')) { 
            $targets = array('"');
        } else { 
            $targets = $symbols;
        }
        for ($i = 0; $i < strlen($string); $i++) { 
            $c = $string[$i];

            if (in_array($c, $targets)) { 
                if ($quote_escaped_symbols == false) {
                    if ($i > 0 && $string[$i-1] === '\\') { 
                        continue;
                    } else if ($c === '\\' && $i < strlen($string)-1 && in_array($string[$i+1], $targets)) { 
                        continue;
                    }
                }
                $string = substr_replace($string, '\\', $i, 0);

                $i++;
            }
        }
        return $string;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function quoteRegex($string, $options = null) { 
        if (is_empty_string($string)) { 
            return '//';
        } else if (is_array($string)) { 
            foreach($string as & $string_) { 
                $string_ = self::quoteRegEx($string_, $options);
            }
            return $string;
        } else { 
            static $safe_symbols = array(
                "<",
                '>',
                '\\',
                '/',
            );
            static $all_symbols = array(
                '<',
                '>',
                '\\',
                '/',
                '$',
                '^',
                '[',
                ']',
                '{',
                '}',
                '(',
                ')',
                '?',
                '.',
            );

            //TODO

            $offset = 0;
            $len = strlen($string);

            if ($string[0] === '/' && $string[$len-1] === '/' && $len > 1) {
                $targets = $safe_symbols;
                $quote_escaped_symbols = false;
                $offset = 1;
                $len--;
            } else { 
                $targets = $all_symbols;
                $quote_escaped_symbols = false;
                $wrap = get_or_null($options, 'wrap');
                if ($wrap) { 
                    $string = '/' . $string . '/';
                    $offset = 1;
                    $len++;
                }
            }

            for ($i = $offset; $i < $len; $i++) { 
                $c = $string[$i];

                if (in_array($c, $targets)) { 
                    if ($quote_escaped_symbols == false) {
                        if ($i > 0 && $string[$i-1] === '\\') { 
                            continue;
                        } else if ($c === '\\' && $i < strlen($string)-1 && in_array($string[$i+1], $targets)) { 
                            continue;
                        }
                    }
                    $string = substr_replace($string, '\\', $i, 0);

                    $i++;
                    $len++;
                }
            }

            return $string;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class RANGE
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public $s;
    public $e;
    public $l;

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function make($s = 0, $l = 0) { 
        return new RANGE($s, $l);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($s = 0, $l = 0) { 
        $this->set($s, $l);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function end($e = null) { 
        if (is_null($e)) { 
            return $this->e;
        } else { 
            return $this->set($this->s, max(0, $e-$this->s+1));
        }
    }

    public function start($s = null) { 
        if (is_null($s)) { 
            return $this->s;
        } else { 
            return $this->set($s);
        }
    }

    public function len($l = null) { 
        if (is_null($l)) { 
            return $this->l;
        } else { 
            return $this->set($this->s, $l);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function set($s, $l = null) { 
        $this->s = $s;
        if (is_null($l) == false) { 
            $this->l = $l;
        }
        $this->e = max($this->s, $this->s+$this->l-1);

        return $this;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function toNum() { 
        return array($this->s, $this->e, $this->l);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function is_empty() { 
        return $this->l == 0;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function prepends($range) { 
        if ($this->l > 0) { 
            return $this->e+1 == $range->s;
        } else { 
            return $this->e == $range->s;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function join($range) { 
        if ($range->l == 0) { 
            return $this;
        } else { 
            $this->e = $range->e;
            $this->l = $this->e-$this->s+1;

            return $this;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function contains($value) { 
        return $this->s <= $value && $this->e >= $value;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class DT 
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    const UTC = 'utc';

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    private static $_config;
    private static $_now = 'now';
    private static $_today = 'today';

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function init($config) {
        self::$_config = $config;
        if ($_now = APP::iget(self::$_config, 'now')) {
            self::$_now = $_now;
        }
        if ($_today = APP::iget(self::$_config, 'today')) {
            self::$_today = $_today;
        }
        if ($_timezone = APP::iget(self::$_config, 'timezone')) {
            date_default_timezone_set($_timezone);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function make($date_or_str, $date_time_zone = null) {
        if (is_string($date_time_zone)) {
            $date_time_zone = new \DateTimeZone($date_time_zone);
        }
        if (is_string($date_or_str)) {
            $dt = new \DateTime($date_or_str, $date_time_zone);
        } else if ($date_or_str instanceof DateTime) {

        } else { 
            throw new TypeException($date_or_str);
        }
        return $dt;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function parseUnixTime($unix_time) {
        return new \DateTime(date('c', $unix_time));
    }

    public static function getNow($date_time_zone = null) {
        if (is_string($date_time_zone)) {
            $date_time_zone = new \DateTimeZone($date_time_zone);
        }
        $dt = new \DateTime(self::$_now, $date_time_zone);
        return $dt;
    }

    public static function getToday($date_time_zone = null) {
        if (is_string($date_time_zone)) {
            $date_time_zone = new \DateTimeZone($date_time_zone);
        }
        $dt = new \DateTime(self::$_today, $date_time_zone);
        return $dt;
    }

    public static function getDateTime($date_or_str, $date_time_zone = null) {
        if (is_string($date_time_zone)) {
            $date_time_zone = new \DateTimeZone($date_time_zone);
        }
        if (is_string($date_or_str)) {
            $dt = new \DateTime($date_or_str, $date_time_zone);
        } else if ($date_or_str instanceof DateTime) {
            $dt = clone $date_or_str;
        }
        return $dt;
    }

    public static function isLastWeekOfMonth($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_month = $date->format('j');
        $number_of_days_in_month = $date->format('t');

        return $day_of_month >= $number_of_days_in_month-6;
    }

    public static function isWeekend($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_week = $date->format('N');

        return $day_of_week >= 6;
    }

    public static function isSunday($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_week = $date->format('N');

        return $day_of_week == 7;
    }

    public static function isSaturday($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_week = $date->format('N');

        return $day_of_week == 6;
    }

    public static function isFriday($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_week = $date->format('N');

        return $day_of_week == 5;
    }

    public static function isFSS($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_week = $date->format('N');

        return $day_of_week >= 5;
    }

    public static function isFirstDayOfMonth($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_month = $date->format('j');

        return $day_of_month == 1;
    }

    public static function isLastDayOfMonth($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_month = $date->format('j');
        $number_of_days_in_month = $date->format('t');

        return $day_of_month == $number_of_days_in_month;
    }

    public static function getMonday($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_week = $date->format('N');
        $days = $day_of_week-1;
        if ($days > 0) {
            $date->modify("-$days days");
        }
        return $date;
    }

    public static function getFriday($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_of_week = $date->format('N');
        $days = 5-$day_of_week;
        if ($days > 0) {
            $date->modify("$days days");
        } else if ($days < 0) {
            $date->modify("$days days");
        }
        return $date;
    }

    public static function getFirstDayOfPrevMonth($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $year = $date->format('y');
        $month = $date->format('m')-1;
        if ($month < 1) {
            $month = 12;
            $year--;
        }
        $date = new DateTime("$year-$month-01", $date_time_zone);

        return $date;
    }

    public static function getLastDayOfPrevMonth($date = null, $date_time_zone = null) {
        $first_day_of_prev_month = self::getFirstDayOfPrevMontH($date, $date_time_zone);

        return self::getLastDayOfMonth($first_day_of_prev_month, $date_time_zone);
    }

    public static function getFirstDayOfMonth($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_in_month = $date->format('j');
        $days = $day_in_month-1;
        if ($days > 0) {
            $date->modify("-$days days");
        }
        return $date;
    }

    public static function getLastDayOfMonth($date = null, $date_time_zone = null) {
        if (is_null($date)) {
            $date = DT::getNow($date_time_zone);
        }
        $day_in_month = $date->format('j');
        $days_in_month = $date->format('t');
        $days = $days_in_month-$day_in_month;
        if ($days > 0) {
            $date->modify("+$days days");
        }
        return $date;
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

    public static function getTomorrow($date_time_zone = null) {
        return self::getAfter('1 day', $date_time_zone);
    }

    public static function getYesterday($date_time_zone = null) {
        return self::getBefore('1 day', $date_time_zone);
    }

    public static function isAfter($dt, $dt_or_date_interval_str, $date_time_zone = null) {
        $before = $dt_or_date_interval_str instanceof DateTime ? $dt_or_date_interval_str : self::getByDateIntervalString($dt_or_date_interval_str, $date_time_zone);

        return $dt > $before;
    }

    public static function isBefore($dt, $dt_or_date_interval_str, $date_time_zone = null) {
        $before = $dt_or_date_interval_str instanceof DateTime ? $dt_or_date_interval_str : self::getByDateIntervalString($dt_or_date_interval_str, $date_time_zone);

        return $dt < $before;
    }

    public static function getByDateIntervalString($date_interval_str, $date_time_zone = null) {
        $dt = self::getNow($date_time_zone);
        $dt->add(\DateInterval::createFromDateString($date_interval_str));
        return $dt;
    }

    public static function getAfter($date_interval_str, $date_time_zone = null) {
        $dt = self::getNow($date_time_zone);
        $dt->add(\DateInterval::createFromDateString($date_interval_str));
        return $dt;
    }

    public static function getBefore($date_interval_str, $date_time_zone = null) {
        $dt = self::getNow($date_time_zone);
        $dt->sub(\DateInterval::createFromDateString($date_interval_str));
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
            $data_time = new \DateTime($date_time);
        }
        return $date_time->format('Y-m-d H:i:s');
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function max() { 
        $num_args = func_num_args();
        if ($num_args == 0) { 
            return null;
        }
        $max = DT::make(func_get_arg(0));

        for ($i = 1; $i < $num_args; $i++) { 
            $arg = DT::make(func_get_arg($i));

            $max = $arg > $max ? $arg : $max;
        }

        return $max;
    }

    public static function min() { 
        $num_args = func_num_args();
        if ($num_args == 0) { 
            return null;
        }
        $min = DT::make(func_get_arg(0));

        for ($i = 1; $i < $num_args; $i++) { 
            $arg = DT::make(func_get_arg($i));

            $min = $arg < $min ? $arg : $min;
        }

        return $min;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class Date extends \DateTime
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($date = 'now', $timezone = null) { 
        if ($date instanceof \DateTime) { 
            parent::__construct($date->format('Y-m-d'), $date->getTimezone());
        } else { 
            parent::__construct($date, $timezone);
            $this->setTime(0, 0, 0);
        }
    }

    public static function make($date, $timezone = null) { 
        return new Date($date, $timezone);
    }

    public static function getNow() { 
        return new Date(DT::getToday());
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function toString() { 
        return parent::format('Y-m-d');
    }

    public function __toString() { 
        return parent::format('Y-m-d');
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function modify($modify) { 
        parent::modify($modify);
        $this->setTime(0, 0, 0);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function format($format) { 
        if ($format == 'c') { 
            return $this->format('Y-m-d');
        } else { 
            $recognized = array(
                'Y',
                'm',
                'd',
            );
            $ignore = array(
                'H',
                'i',
                's',
                // -- TODO
            );
            $str = '';
            for ($i = 0; $i < strlen($format); $i++) { 
                $chr = $format[$i];

                if (in_array($chr, $recognized)) { 
                    $str .= parent::format($chr);
                } else if (in_array($chr, $ignore) || $chr == '\\') { 
                    // -- ignore alpha character
                } else { 
                    $str .= $chr;
                }
            }

            return $str;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class Time extends \DateTime
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($date = 'now', $timezone = null) { 
        if ($date instanceof \DateTime) { 
            parent::__construct($date->format('H:i:s'), $date->getTimezone());
        } else { 
            parent::__construct($date, $timezone);
            $this->setDate(1, 1, 1);
        }
    }

    public static function make($date, $timezone = null) { 
        return new Time($date, $timezone);
    }

    public static function getNow() { 
        return new Time(DT::getNow());
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function toString() { 
        return parent::format('H:i:s');
    }

    public function __toString() { 
        return parent::format('H:i:s');
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public function modify($modify) { 
        parent::modify($modify);
        $this->setDate(1, 1, 1);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function format($format) { 
        if ($format == 'c') { 
            return $this->format('H:i:s');
        } else { 
            $recognized = array(
                'H',
                'i',
                's',
            );
            $ignore = array(
                'Y',
                'm',
                'd',
                // -- TODO
            );
            $str = '';
            for ($i = 0; $i < strlen($format); $i++) { 
                $chr = $format[$i];

                if (in_array($chr, $recognized)) { 
                    $str .= parent::format($chr);
                } else if (in_array($chr, $ignore) || $chr == '\\') { 
                    // -- ignore alpha character
                } else { 
                    $str .= $chr;
                }
            }

            return $str;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class FILE 
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function toName($name) {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function touch($uri, $must_exist = true) {
        if ($must_exist && !file_exists($uri)) {
            throw new CustomException('Trying to touch a non-existend file ', $uri, ' with must_exist set');
        }
        if (touch($uri) === false) {
            throw new CustomException('Unable to touch ', $uri);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getLastModified($path) {
        $time = DT::toString(DT::parseUnixTime(filemtime($path)));

        if ($time === false) {
            throw new CustomException('Can not get last modified time: ', $path);
        }

        return $time;
    }

    public static function filemtime($path, $options = null) {
        $fail_safe = get_or_null($options, 'fail_safe');

        if (!$path) { 
            throw new CustomException('Can not get filemtime: no path');
        }
        $exists = file_exists($path);
        if ($exists == false) { 
            if ($fail_safe == false) { 
                throw new CustomException('Can not get filemtime: path does not exist at ', $path);
            } else { 
                return false;
            }
        }
        $time = false;
        for ($i = 0; $i < 3 && $time === false; $i++) { 
            if ($i > 0) { 
                usleep($i*$i);
            }
            $time = @filemtime($path);
        }
        if ($time === false && $fail_safe == false) {
            throw new CustomException('Can not get filemtime: ', $path);
        }
        return $time;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getAccessTime($uri) {
        if (!($fp = fopen($uri, 'r'))) {
            throw new CustomException('Unable to open ', $uri, ' for reading');
        }
        $stat = fstat($fp);
        $last_ex = false;
        try {
            $time = DT::toString(DT::parseUnixTime($stat['atime']));
        } catch (\Exception $ex) {
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
        $size = $stat['size'];
        fclose($fp);
        return $size;
    }

    public static function getCreatedAt($uri) {
        if (!($fp = fopen($uri, 'r'))) {
            throw new CustomException('Unable to open ', $uri, ' for reading');
        }
        $stat = fstat($fp);
        $last_ex = false;
        try {
            $time = DT::toString(DT::parseUnixTime($stat['ctime']));
        } catch (\Exception $ex) {
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function delete($path, $options = null) {
        if (is_array($path)) {
            foreach($path as $path_) {
                self::delete($path_);
            }
        } else {
            $result = unlink($path);

            if ($result === false) {
                usleep(10);

                $result = unlink($path);
            }
            if ($result === false) {
                throw new CustomException('Can not delete file: ', $path);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

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

        $lines = array();

        $pos = 0;
        $separator_len = strlen($separator);
        $len = strlen($data);
        if ($len > 0) { 
            while (true) {
                $_pos = stripos($data, $separator, $pos);

                if ($_pos === false) { 
                    $lines[] = substr($data, $pos);

                    break;
                }

                $lines[] = substr($data, $pos, $_pos-$pos);

                $_pos += $separator_len;

                $pos = $_pos;
            }
        }

        return $lines;
    }

    public static function readLineAt($path, $line) {
        require_arg($path);
        require_arg($line);

        if ($line < 1) { 
            throw new CustomException('|readLineAt| starts at line 1: ', $line);
        }

        require_file($path);

        $fp = fopen($path, 'r');

        require_value($fp, 'file pointer');

        $str = null;

        try { 
            if ($line == 1) { 
                $str = fgets($fp);
            } else { 
                $i = 1;

                while (true) { 
                    do { 
                        $ch = fgetc($fp);
                    } while ($ch !== false && $ch !== "\n");

                    if ($ch === "\n") { 
                        $i++;

                        if ($i == $line) { 
                            $str = fgets($fp);

                            break;
                        }
                    } else { 
                        break;
                    }
                }
            }
        } catch(Exception $ex) { 
            if ($fp) { 
                fclose($fp);
            }

            throw $ex;
        }

        return $str;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createDirectory($path, $mode = 0777, $recursive = true) { 
        if (!$path) { 
            throw new CustomException('No path');
        }
        if (file_exists($path)) { 
            return;
        }
        $result = mkdir($path, $mode, $recursive);
        if ($result === false) { 
            throw new CustomException('Can not create directory: ', $path);
        }
    }

    public static function removeDirectory($path, $recursive = true) { 
        if (is_empty_or_whitespace_string($path)) { 
            throw new CustomException('No directory path');
        }

        static $forbidden_paths = array(
            'c:',
            'c:/',
            'c:/',
            'c:\\',
            'C:',
            'C:/',
            'C:\\',
        );

        if (file_exists($path) == false) { 
            return;
        }
        if (is_dir($path) == false) { 
            throw new CustomException('Path is not a directory: ', $path);
        }
        if (in_array($path, $forbidden_paths)) { 
            throw new CustomException('Can not remove directory: ', $path);
        }

        foreach(PATH::enumerateFiles($path) as $path_) {
            self::delete($path_);
        }
        if ($recursive) { 
            foreach(PATH::enumerate_directories($path) as $dir) { 
                self::removeDirectory($dir, $recursive);
            }
        }
        require_value(rmdir($path));
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getExtension($path) { 
        $pos = strrpos($path, '.');

        return $pos === false ? null : substr($path, $pos+1);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function removeExtension($path) { 
        $pos = strrpos($path, '.');

        return $pos === false ? $path : substr($path, 0, $pos);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function replaceExtension($path, $extension) { 
        if ($extension && $extension[0] !== '.') {
            $extension = '.' . $extension;
        }
        return self::removeExtension($path) . $extension;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function read($path, $options = null) { 
        require_file($path, 'path for read');

        $text = false;
        for ($i = 0; $i < 3 && $text === false; $i++) { 
            if ($i > 0) { 
                usleep($i*$i);
            }
            $text = @file_get_contents($path);
        }
        if ($text === false) {
            throw new CustomException('Can not read file at ', $path);
        }
        return $text;
    }

    public static function unsafe_read($path, $options = null) { 
        $text = false;
        for ($i = 0; $i < 3 && $text === false; $i++) { 
            if ($i > 0) { 
                usleep($i*$i);
            }
            $text = @file_get_contents($path);
        }
        if ($text === false) {
            throw new CustomException('Can not read file at ', $path);
        }
        return $text;
    }

    public static function write($path, $text, $options = null) { 
        require_value($path, 'path');

        make_dir(dirname($path));

        if (get_or_null($options, 'require_change')) {
            if (file_exists($path)) {
                if (md5(file_get_contents($path)) === md5($text)) {
                    return;
                }
            }
        } 
        $result = false;
        for ($i = 0; $i < 3 && $result === false; $i++) { 
            if ($i > 0) { 
                usleep($i*$i);
            }
            $result = file_put_contents($path, $text);
        }
        if ($result === false) { 
            throw new CustomException('Can not write to file at ', $path);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function append($path, $text, $options = null) {
        require_value($path, 'path');

        make_dir(dirname($path));

        require_value(file_put_contents($path, $text, FILE_APPEND), 'append to path: ' . $path);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function find($path, $base_paths = null, $options = null) { 
        if (is_null($base_paths)) { 
            $base_paths = FILE::getIncludePaths();
        } else { 
            $base_paths = array_num($base_paths);
        }
        foreach($base_paths as $base_path) { 
            $path_ = PATH::combine($base_path, $path);

            if (file_exists($path_)) { 
                $normalize_ex = get_or_null($options, 'normalize_ex');

                if ($normalize_ex) { 
                    return URI::normalize_ex($path_);
                } else { 
                    return $path_;
                }
            }
        }
        return null;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getIncludePaths() { 
        $paths = get_include_path();
        $paths = explode(';', $paths); //?
        if ($paths && $paths[0] === '.') { 
            $paths[0] = getcwd();
        }
        return $paths;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class Log 
{
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    const LEVEL_NONE = 0;
    const LEVEL_ERROR = 1; //includes exceptions
    const LEVEL_WARNING = 2;
    const LEVEL_INFO = 3;
    const LEVEL_DEBUG = 4;
    const LEVEL_NETWORK = 5;
    const LEVEL_DB = 6;
    const LEVEL_DATA = 7;
    const LEVEL_ALL = 99;

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    private static $_handle;
    private static $_config; // level, base, append (default true), limit (in MB)
    private static $_level = Log::LEVEL_ERROR;
    private static $_path;

    private static $_ex_callback;

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function init($config) {
        self::$_config = $config;
        self::$_level = APP::get($config, 'level');
        self::$_path = self::_getURI();
        if (!APP::get(self::$_config, 'append')) {
            self::clear();
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

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

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function setExCallback($callback) { 
        self::$_ex_callback = $callback;
    }

    public static function clearExCallback() { 
        self::$_ex_callback = null;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

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
                $ex = func_get_arg(0);

                $msg .= $ex->getMessage();
            }
            for ($i = 2; $i < func_num_args(); $i++) {
                $msg .= print_r(func_get_arg($i), true);
            }
            self::_write($msg);
            if (func_num_args() > 0) {
                //self::_write(print_r(func_get_arg(0)->getTrace(), true));
                self::_write(func_get_arg(0)->getTraceAsString());
            }

            if (self::$_ex_callback && $ex) { 
                $callback = self::$_ex_callback;

                $callback($ex);
            }
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    private static function _prepareAndWrite($type, $args) {
        $msg = $type . ' ';
        for ($i = 0; $i < count($args); $i++) {
            $msg .= print_r($args[$i], true);
        }
        self::_write($msg);
    }

    public static function getPath() {
        return self::$_path;
    }

    private static function _getURI() {
        $path = APP::get(self::$_config, 'path');
        if ($path) { 
            return $path;
        }
        $name = APP::get(self::$_config, 'name', 'log.txt');
        $dir = APP::get(self::$_config, 'dir', __DIR__);

        return PATH::combine($dir, $name);
    }

    private static function _write($msg, $clear = false) {
        $uri = self::$_path;
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

class TREE
{
    public $root;

    public function __construct() {

    }

    public function setRoot($root) {
        $this->root = $root;
    }


}

class TREE_NODE
{
    const TYPE_LEAF = 'leaf';
    const TYPE_ROOT = 'root'; 

    public $name; //OPTIONAL
    public $type;
    public $value;
    public $children;
    public $parent;
    public $is_binary;

    public function __construct($value, $name = null) {
        $this->value = $value;
        $this->name = $name;
        $this->type = self::TYPE_LEAF;
        $this->children = array();
        $this->is_binary = false;
    }

    public function left($node = null) {
        if (is_null($node)) {
            return $this->first();
        }
        $this->type = self::TYPE_ROOT;

        if (is_array($node) && count($node) == 1) { 
            $node = array_shift($node);
        }

        if (is_array($node)) { 
            $_node = new TREE_NODE('');
            $_node->parent = $this;
            $_node->add($node);

            $node = $_node;
        }

        $this->children[0] = $node; 
        $node->parent = $this;

        $this->is_binary = true;
    }

    public function right($node = null) {
        if (is_null($node)) {
            return $this->last();
        }

        $this->type = self::TYPE_ROOT;

        if (is_array($node) && count($node) == 1) { 
            $node = array_shift($node);
        }

        if (is_array($node)) { 
            $_node = new TREE_NODE('');
            $_node->parent = $this;
            $_node->add($node);

            $node = $_node;
        }

        $this->children[1] = $node; 
        $node->parent = $this;

        $this->is_binary = true;
    }

    public function count() {
        return count($this->children);
    }

    public function first() {
        return $this->children ? $this->children[0] : null;
    }

    public function last() {
        return $this->children ? $this->children[count($this->children)-1] : null;
    }

    public function add($node) {
        $this->type = self::TYPE_ROOT;

        if (is_array($node)) {
            foreach($node as $_node) {
                $_node->parent = $this;
            }
            if ($this->is_binary) { 
                if ($this->count() == 0) { 
                    $this->left($node);
                } else { 
                    assert($this->count() == 1);

                    $this->right($node);
                }
            } else { 
                $this->children = array_merge($this->children, $node);
            }
        } else {
            $node->parent = $this;

            if ($this->is_binary) { 
                if ($this->count() == 0) { 
                    $this->left($node);
                } else { 
                    assert($this->count() == 1);

                    $this->right($node);
                }
            } else { 
                array_push($this->children, $node);
            }
        }

        return $this;
   }

    public function replace($child, $replacement) {
        for ($i = 0; $i < count($this->children); $i++) {
            if ($this->children[$i] === $child) {
                $this->children[$i]->parent = null;

                $this->children[$i] = $replacement;

                $replacement->parent = $this;

                break;
            }
        }

        /**
        for ($i = 0; $i < count($this->children); $i++) {
            $is_array = is_array($this->children[$i]);

            $children = ARR::wrapToNumericArray($this->children[$i]);

            $found = false;

            for ($j = 0; $j < count($children); $j++) { 
                if ($children[$j] === $child) {
                    $children[$j]->parent = null;

                    $children[$j] = $replacement;

                    $replacement->parent = $this;

                    $found = true;

                    break;
                }
            }

            if ($found) { 
                if ($is_array) { 
                    $this->children[$i] = $children;
                } else { 
                    $this->children[$i] = $children ? $children[0] : $children;
                }
            }
        }
        **/

    }

    public function remove($node) {
        for ($i = 0; $i < count($this->children); $i++) {
            if ($this->children[$i] === $node) {
                $this->children[$i]->parent = null;

                unset($this->children[$i]);

                break;
            }
        }

        /**
        for ($i = 0; $i < count($this->children); $i++) {
            $children = ARR::wrapToNumericArray($this->children[$i]);

            $found = false;

            for ($j = 0; $j < count($children); $j++) { 
                if ($children[$j] === $node) {
                    $children[$j]->parent = null;

                    unset($children[$j]);

                    $found = true;

                    break;
                }
            }

            if ($found) { 
                if ($children) { 
                    $this->children[$i] = $children;
                } else { 
                    unset($this->children[$i]);
                }
            }
        }
        **/

        $this->children = array_values($this->children);

        return $this;
    }

    public function remove_next_sibling($node) {
        $next_sibling = $this->get_next_sibling();

        if ($next_sibling) { 
            $this->parent->remove($next_sibling);
        }

        return $this;
   }

    public function get_next_sibling() { 
        if (!$this->parent) { 
            return null;
        }
        for ($i = 0; $i < count($this->parent->children); $i++) {
            if ($this->parent->children[$i] === $this && $i < count($this->parent->children)-1) { 
                return $this->parent->children[$i+1];
            }
        }

        return null;
    }

    public function getValues() {
        $values = array();

        if (count($this->children) == 2) {
            $values = array_merge($values, $this->children[0]->getValues());
            $values[] = $this->value;
            $values = array_merge($values, $this->children[1]->getValues());
        } else {
            $values[] = $this->value;

            foreach($this->children as $child) {
                $values = array_merge($values, $child->getValues());
            }
        }

        return $values;
    }

}

class Assertions 
{
    public static function init($config) {
        if (is_array($config) == false) {
            if ($config) {
                assert_options(ASSERT_ACTIVE, 1);
                assert_options(ASSERT_BAIL, 1);
            } else {
                assert_options(ASSERT_ACTIVE, 0);
                assert_options(ASSERT_WARNING, 0);
            }
        } else {
            throw new NotImplementedException();
        }
    }

}



class CSV {

    public static function getEmpty() {
        return array('header' => array(), 'rows' => array());
    }

    public static function read($uri, $separator = ',', $with_header = true) {
        if (!file_exists($uri)) {
            throw new \InvalidArgumentException('Input URI ' . $uri . ' does not exist');
        }
        $stream = fopen($uri, 'r');
        if (!$stream) {
            throw new \InvalidArgumentException('Unable to open read stream for ' . $uri);
        }

        $header = false;
        $rows_before_header = array();

        if ($with_header) {
            if (is_numeric($with_header)) {
                for ($i = 0; $i < intval($with_header) - 1; $i++) {
                    $rows_before_header[] = fgetcsv($stream, NULL, $separator);
                }
            }
            $header = fgetcsv($stream, NULL, $separator);
            if (!$header) {
                throw new \InvalidArgumentException('Missing header in CSV file');
            }
        }

        $rows = array();

        do {
            $row = fgetcsv($stream, NULL, $separator);
            if ($row && $row[0] === NULL) {
                continue;
            }
            if ($row !== false) {
                $row_count = count($row);
                for ($i = 0; $i < count($header); $i++) {
                    $row[$header[$i]] = $row_count > $i ? $row[$i] : null;
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
            }
            $csv['rows'] = $rows;
        }
        return $csv;
    }

    public static function readAssociativeArrays($uri, $separator = ',', $with_header = true) {
        $csv = self::read($uri, $separator, $with_header);
        if ($csv['rows']) {
            $rows = $csv['rows'];
            $csv['rows'] = ARR::toAssociativeArray($rows);
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
            throw new \InvalidArgumentException('Unable to open write stream for ' . $uri);
        }
        if ($rows_before_header = APP::get($csv, 'rows_before_header')) {
            foreach ($rows_before_header as $row_before_header) {
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



/////////////////////////////////////////////////////////////////////////////////////////////////////

class CURLException extends CustomException
{
    public $status_code;
    public $response;

    public function __construct($status_code, $response) { 
        $this->status_code = $status_code;
        $this->response = $response ? $response : 'no-response';
        $this->message = "$status_code {$this->response}";

        $remaining_arguments = array_slice(func_get_args(), 2);

        array_unshift($remaining_arguments, $this);

        call_user_func_array('parent::__construct', $remaining_arguments);
    }

}

/////////////////////////////////////////////////////////////////////////////////////////////////////

class CURL 
{
    const AUTH_BASIC = 'basic';
    const AUTH_DIGEST = 'digest';
    const AUTH_NTLM = 'ntlm';

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static $last_http_code;
    public static $last_response;
    public static $last_content_type;
    public static $use_fiddler;

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    private static $_config;
    private static $_retries;

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function init($config) {
        self::$_config = $config;

        self::$_retries = get_or_default($config, 'retries', false);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    private static function _init_request($url, $options, $additional_options) {
        $options = array_merge_assoc($options, $additional_options);

        $query_arguments = get($options, 'query_arguments');
        $auth = get($options, 'auth');
        $headers = get($options, 'headers');
        $curlopt = get($options, 'curlopt');

        $ch = curl_init();

        $query_url = self::$use_fiddler ? 'http://localhost:8888' : $url;
        if ($query_arguments) {
            $query_url .= STRING::endsWith($url, '?') ? '' : '?' . http_build_query($query_arguments);
        }

        if ($auth) {
            $auth_username = get_and_require($auth, 'username');
            $auth_password = get_and_require($auth, 'password');
            $auth_method = get_and_require($auth, 'method');

            $auth_value = $auth_username . ':' . $auth_password;

            switch($auth_method) { 
            case self::AUTH_BASIC:
                $auth_method = CURLAUTH_BASIC;
                break;
            case self::AUTH_DIGEST:
                $auth_method = CURLAUTH_DIGEST;
                break;
            case self::AUTH_NTLM:
                $auth_method = CURLAUTH_NTLM;
                break;
            default:
                throw new \CURLException('Unsupported HTTP authentication code (supported are: basic, digest, NTML)');
            }
        }

        curl_setopt($ch, CURLOPT_URL, $query_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth_value);
            curl_setopt($ch, CURLOPT_HTTPAUTH, $auth_method);
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, self::_prepareHeaders($headers));
        }
        if ($curlopt) { 
            foreach($curlopt as $name => $value) { 
                curl_setopt($ch, $name, $value);
            }
        }

        Log::network('|CURL::init_request| url=', $query_url, ' headers=', $headers, ' auth=', $auth);

        return $ch;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function init_post_request($url, $data, $options, $additional_options = null) { 
        $ch = self::_init_request($url, $options, $additional_options);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        if ($data) {
            if (is_array($data) || is_object($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else if (is_string($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                throw new CURLException(null, null, 'Invalid parameter type for request: ', $data);
            }
        }
        return $ch;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function init_delete_request($url, $data, $options, $additional_options = null) { 
        $options['query_arguments'] = array_merge_ex(get_or_null($options, 'query_arguments'), $data);

        $ch = self::_init_request($url, $options, $additional_options);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        return $ch;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    private static function _do_request($ch, $options) {
        $retry = 0;

        $response = null;

        $exponential_backoff = get_or_default(self::$_config, 'exponential_backoff', false);

        do {
            $response = curl_exec($ch);

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            Log::network('|CURL::do_request| retry=', $retry, ' http_code=', $http_code, ' response=', $response);

            if (self::_do_retry($http_code, $retry)) {
                ++$retry;

                if ($exponential_backoff) { 
                    sleep(exp(2, $retry));
                } else { 
                    sleep($retry);
                }
            } else {
                break;
            }
        } while (true);

        $response = self::_end_request($ch, $http_code, $response, $content_type, $options);

        return $response;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private static function _do_retry($http_code, $retry) {
        if (is_null(self::$_retries) && $retry >= 3) { 
            return false;
        } else if (self::$_retries == false) { 
            return false;
        } else if ($retry >= self::$_retries) { 
            return false;
        }

        if ($http_code && substr($http_code, 0, 2) == '20') {
            return false;
        }
        if ($http_code && substr($http_code, 0, 2) == '40') {
            return false;
        }
        return true;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private static function _end_request($ch, $http_code, $response, $content_type, $options) {
        curl_close($ch);

        self::$last_http_code = $http_code;
        self::$last_response = $response;
        self::$last_content_type = $content_type;

        $ignore_http_code = get_or_null($options, 'ignore_http_code');

        if ((!$http_code || substr($http_code, 0, 2) != '20') && ($ignore_http_code == false || in_array($http_code, $ignore_http_code) == false)) {
            // json message might be json encoded again (creating an invalid json)?
            if (stripos($content_type, 'json') !== false) {
                $response = preg_replace('/[{}\[\]"\']/', '', $response);
            }
            throw new CURLException($http_code, $response);
        }

        if (get_or_null($options, 'as_array')) { 
            if (stripos($content_type , 'json') !== false) { 
                $response = json_decode($response, true); 
            } else if (stripos($content_type , 'xml') !== false) { 
                $response = XML::toArray($response); 
            } else { 
                throw new CustomException('Response type not implemented');
            }

            Log::network('|CURL| as_array=', $response);
        }

        return $response;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private static function _prepareHeaders($headers) {
        if (is_assoc($headers)) {
            $_headers = array();
            foreach($headers as $name => $value) {
                $_headers[] = "$name: $value";
            }
            $headers = $_headers;
        }
        return $headers;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function get($url, $data = null, $options = null) {
        $options['query_arguments'] = array_merge_ex($data, get_or_null($options, 'query_arguments'));
        $additional_options = null;

        $ch = self::_init_request($url, $options, $additional_options);

        $response = self::_do_request($ch, $options);

        return $response;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    /*

    public static function putXml($url, $xml, $params = null, $headers = null, $auth = null) {
        $ch = self::initRequest($url, $params, $headers, $auth);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        if (is_null($headers)) {
            $headers = array();
        }
        if (ARR::stripos('Content-Type', $headers) === false) {
            $headers[] = 'Content-Type: application/xml';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, self::_prepareHeaders($headers));

        Log::network("[REQUEST][PUT] url=", $url, ' params=', $params, ' headers=', $headers, ' auth=', $auth, ' xml=', $xml);

        $response = self::_do_request($ch);

        return $response;
    }

    public static function postXml($url, $xml, $params = null, $headers = null, $auth = null) {
        $ch = self::initRequest($url, $params, $headers, $auth);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        if (is_null($headers)) {
            $headers = array();
        }
        if (ARR::stripos('Content-Type', $headers) === false) {
            $headers[] = 'Content-Type: application/xml';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, self::_prepareHeaders($headers));

        Log::network("[REQUEST][POST] url=", $url, ' params=', $params, ' headers=', $headers, ' auth=', $auth, ' xml=', $xml);

        $response = self::_do_request($ch);

        return $response;
    }

    public static function deleteXml($url, $xml, $params = null, $headers = null, $auth = null) {
        $ch = self::initRequest($url, $params, $headers, $auth);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        if (is_null($headers)) {
            $headers = array();
        }
        if (ARR::stripos('Content-Type', $headers) === false) {
            $headers[] = 'Content-Type: application/xml';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, self::_prepareHeaders($headers));

        Log::network("[REQUEST][DELETE] url=", $url, ' params=', $params, ' headers=', $headers, ' auth=', $auth, ' xml=', $xml);

        $response = self::_do_request($ch);

        return $response;
    }

    public static function deleteJson($url, $json = null, $params = null, $headers = null, $auth = null) {
        $ch = self::initRequest($url, $params, $headers, $auth);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if ($json && is_string($json) == false) {
            $json = json_encode($json);
        }
        if ($json) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        if (is_null($headers)) {
            $headers = array();
        }
        if ($json) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, self::_prepareHeaders($headers));

        Log::network("[REQUEST][DELETE] url=", $url, ' params=', $params, ' headers=', $headers, ' auth=', $auth, ' json=', $json);

        $response = self::_do_request($ch);

        return $response;
    }

    public static function putJson($url, $json, $params = null, $headers = null, $auth = null) {
        $ch = self::initRequest($url, $params, $headers, $auth);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($json && is_string($json) == false) {
            $json = json_encode($json);
        }
        if ($json) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }
        if (is_null($headers)) {
            $headers = array();
        }
        if ($json) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, self::_prepareHeaders($headers));

        Log::network("[REQUEST][PUT] url=", $url, ' params=', $params, ' headers=', $headers, ' auth=', $auth, ' json=', $json);

        $response = self::_do_request($ch);

        return $response;
    }
     */

    /////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public static function post($url, $data, $options = null) {
        require_value($url, 'url');

        $ch = self::init_post_request($url, $data, $options);

        Log::network('|CURL::post| url=', $url, ' options=', $options, ' data=', $data);

        $response = self::_do_request($ch, $options);

        return $response;
    }

    public static function post_xml($url, $xml, $options = null) {
        require_value($url, 'url');
        require_value($xml, 'xml');

        if ($xml && is_string($xml) == false) {
            $xml = xml_encode($xml);
        }

        $ch = self::init_post_request($url, $xml, $options, array(
            'headers' => array(
                'Content-Type' => 'text/xml'
            ),
        ));

        Log::network('|CURL::post_xml| url=', $url, ' options=', $options, ' xml=', $xml);

        $response = self::_do_request($ch, $options);

        return $response;
    }

    public static function post_json($url, $json, $options = null) {
        require_value($url, 'url');
        require_value($json, 'json');

        if ($json && is_string($json) == false) {
            $json = json_encode($json);
        }

        $ch = self::init_post_request($url, $json, $options, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
        ));

        Log::network('|CURL::post_json| url=', $url, ' options=', $options, ' json=', $json);

        $response = self::_do_request($ch, $options);

        return $response;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function delete($url, $data, $options = null) {
        require_value($url, 'url');

        $ch = self::init_delete_request($url, $data, $options);

        Log::network('|CURL::delete| url=', $url, ' options=', $options, ' data=', $data);

        $response = self::_do_request($ch, $options);

        return $response;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    /*
    public static function put($url, $params = null, $headers = null, $auth = null) {
        $ch = self::initRequest($url, null, $headers, $auth);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        Log::network("[REQUEST][PUT] url=", $url, ' params=', $params, ' headers=', $headers, ' auth=', $auth);

        $response = self::_do_request($ch);

        return $response;
    }

    public static function post($url, $post_params = null, $get_params = null, $headers = null, $auth = null) {
        $ch = self::initPOSTRequest($url, $post_params, $get_params, $headers, $auth);

        Log::network("[REQUEST][POST] url=", $url, ' post params=', $post_params, ' get params= ', $get_params, ' headers=', $headers, ' auth=', $auth);

        $response = self::_do_request($ch);

        return $response;
    }
     */

}

///////////////////////////////////////////////////////////////////////////////////////////////////

if (getClassConstant('PDEBUG', 'USE_FIDDLER')) { 
    CURL::$use_fiddler = true;
}

///////////////////////////////////////////////////////////////////////////////////////////////////



class MailClient {

    private static $_config; // array('log' => true, 'do_not_send' => true)

    public static function init($config) {
        self::$_config = $config;
    }

    public static function sendMany($mail) {
        $mail = self::toMIME($mail, true);

        if (APP::get(self::$_config, 'log')) {
            Log::debug($mail);
        }

        if (!APP::get(self::$_config, 'do_not_send')) {
            $tos = APP::toArray($mail['to']);

            foreach($tos as $to) {
                for ($i = 0; $i < 3; $i++) {
                    if (($sent = mail($to, $mail['subject'], $mail['body'], $mail['headers'])) !== false) {
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

    public static function send($mail) {
        $mail = self::toMIME($mail);

        if (APP::get(self::$_config, 'log')) {
            Log::debug($mail);
        }

        if (!APP::get(self::$_config, 'do_not_send')) {
            $to = $mail['to'];
            if (is_array($to)) {
                $to = array_shift($to);
            }

            for ($i = 0; $i < 3; $i++) {
                if (($sent = mail($to, $mail['subject'], $mail['body'], $mail['headers'])) !== false) {
                    break;
                }
                sleep(3);
            }
            if (!$sent) {
                throw new CustomException('Unable to send mail to ', $mail['to']);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function sendTemplateMail($mail, $path, $variables = null) { 
        require_file($path);

        $text = file_get_contents($path);
        if (is_empty_or_whitespace_string($text)) {
            throw new CustomException('No template content: ', $path);
        }

        if ($variables) { 
            $text = self::replaceTemplateVariables($text, $variables);
        }
        if (($missing_variable = STRING::inBetween($text, '{{', '}}'))) {
            throw new CustomException('Missing variable for ', $missing_variable);
        }

        $mail['body'] = $text;

        self::send($mail);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function replaceTemplateVariables($text, $variables, $key = null) { 
        if (is_assoc($variables)) { 
            foreach($variables as $key => $value) { 
                $text = self::replaceTemplateVariables($text, $value, $key);
            }
        } else if (is_num($variables)) { 
            require_value($key);

            $begin_key = '{{' . $key . '-BEGIN}}';
            $end_key = '{{' . $key . '-END}}';

            $text_ = STRING::inBetween($text, $begin_key, $end_key);

            $text___ = '';
            foreach($variables as $value) { 
                $text__ = $text_;

                $text__ = self::replaceTemplateVariables($text__, $value);

                $text___ .= $text__;
            }

            $text = substr_replace($text, $text___, strpos($text, $begin_key), strlen($text_)+strlen($begin_key)+strlen($end_key));
        } else { 
            require_value($key);

            $text = str_ireplace('{{' . $key . '}}', $variables, $text);
        }
        return $text;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function sendGroupedTemplateMail($mail, $uri, $group_variables, $variables, $messages) {
        /**
         * group_variables: ERRORS
         * variables: DATETIME, CONTEXT, MESSAGE
         * template: {{ERRORS-BEGIN}} <tr><td style="width:150px">{{DATETIME}}</td><td>{{CONTEXT}}</td><td>{{MESSAGE}}<td></tr> {{ERRORS-END}}
         * */
        $template_content = file_get_contents($uri);
        if (empty($template_content)) {
            throw new CustomException('No mail template content at ', $uri);
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

    public static function toMIME($mail, $exclude_to_in_header = false) {
        if (is_object($mail)) {
            $mail = (array) $mail;
        }
        if (!isset($mail['from'])) {
            throw new \InvalidArgumentException("Missing from for mail");
        }
        if (!isset($mail['to'])) {
            throw new \InvalidArgumentException("Missing to for mail");
        }
        if (!isset($mail['subject'])) {
            $mail['subject'] = '';
        }
        if (!isset($mail['body'])) {
            $mail['body'] = '';
        }
        $body = $mail['body'];
        $body = str_replace("\n.", "\n..", $body);
        $contentType = isset($mail['content-type']) ? $mail['content-type'] : 'text/plain';
        if (!isset($mail['content-type']) && strpos($body, '<html>') !== false) {
            $contentType = 'text/html';
        }

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= "From: " . $mail['from'] . "\r\n";
        if (isset($mail['reply-to'])) {
            $headers .= "Reply-To: " . $mail['reply-to'] . "\r\n";
        }
        if (isset($mail['return-path'])) {
            $headers .= "Return-Path: " . $mail['return-path'] . "\r\n";
        }
        if ($exclude_to_in_header == false) {
            if (isset($mail['to'])) {
                if (is_array($mail['to']) == false) {
                    $mail['to'] = array($mail['to']);
                }
                $headers .= "To: " . implode(',', $mail['to']) . "\r\n";
            }
        }
        if (isset($mail['bcc'])) {
            if (is_array($mail['bcc']) == false) {
                $mail['bcc'] = array($mail['bcc']);
            }
            foreach($mail['bcc'] as $bcc) {
                $headers .= "Bcc: " . $bcc . "\r\n";
            }
        }
        if (isset($mail['cc'])) {
            if (is_array($mail['cc']) == false) {
                $mail['cc'] = array($mail['cc']);
            }
            foreach($mail['cc'] as $cc) {
                $headers .= "Cc: " . $cc . "\r\n";
            }
        }
        $headers .= 'Subject: ' . $mail['subject'] . "\r\n";
        $headers .= 'Date: ' . DT::getNow()->format('r') . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
        if (!APP::get($mail, 'attachments', false)) {
            $headers .= 'Content-type: ' . $contentType . '; charset=UTF-8' . "\r\n";
        } else {
            if (!is_array($mail['attachments'])) {
                $mail['attachments'] = array($mail['attachments']);
            }

            $random_hash = md5(date('r', time()));

            $headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-" . $random_hash . "\"";

            $body = <<<EOS
--PHP-mixed-$random_hash
Content-type: $contentType; charset=UTF-8

$body

EOS;

            foreach ($mail['attachments'] as $attachment) {
                $content = isset($attachment['path']) ? file_get_contents($attachment['path']) : $attachment['content'];
                $attachmentContent = chunk_split(base64_encode($content));
                $attachmentName = $attachment['name'];
                $attachmentContentType = APP::get($attachment, 'content-type', 'application/xlsx');

                $body .= <<<EOS
--PHP-mixed-$random_hash
Content-Type: $attachmentContentType; name=$attachmentName
Content-Transfer-Encoding: base64
Content-Disposition: attachment

$attachmentContent

EOS;
            }

            $body .= <<<EOS
--PHP-mixed-{$random_hash}--

EOS;
        }

        if (APP::get(self::$_config, 'log')) {
            Log::debug($mail);
        }

        $mail['body'] = $body;
        $mail['headers'] = $headers;

        $mail['mime'] = $mail['headers'] . "\r\n" . $mail['body'];

        return $mail;
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
        $dom = new \DOMDocument("1.0");
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

        $iterator = new \SimpleXMLIterator($source->asXML());
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
                $root = new \SimpleXMLElement("<$tag>$arr_or_obj</$tag>");
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
        $root = new \SimpleXMLElement($str);
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

    public static function toXML($obj, $prefix = '', $suffix = '', $start_document = true) {
        $doc = new XmlWriter();
        $doc->openMemory();
        $start_document and $doc->startDocument('1.0');
        $doc->setIndent(true);

        self::_getObject2XML($doc, $obj);

        $doc->endElement();

        $xml = $doc->outputMemory(true);
        $xml = $prefix . $xml . $suffix;
        return $xml;
    }

    public static function fromString($xmlString) {
        return simplexml_load_string($xmlString);
    }

    private static function _getAttribute2XML(XMLWriter $xml, $data) {
        foreach ($data as $key => $value) {
// there should only be one
            $xml->writeAttribute($key, $value);
        }
    }

    private static function _getObject2XML(XMLWriter $xml, $data) {
        $jagged = false;
        foreach ($data as $key => $value) {
            $jagged = true;
            if ($key == "attr") {
// we have an attribute for the current element
                self::_getAttribute2XML($xml, $value);
            } elseif (is_object($value) || ARR::isAssociativeArray($value)) {
                $xml->startElement($key);
                self::_getObject2XML($xml, $value);
                $xml->endElement();
                continue;
            } else if (is_array($value)) {
                self::getArray2XML($xml, $key, $value);
            }

            if (is_null($value)) {
                //write null values?
            } else if (is_string($value)) {
                $xml->writeElement($key, $value);
            } else if (is_numeric($value)) {
                $xml->writeElement($key, $value);
            } else if (is_bool($value)) {
                $xml->writeElement($key, $value ? 'true' : 'false');
            } else if ($value instanceof \DateTime) {
                $xml->writeElement($key, $value->format('c'));
            }
        }

        if (!$jagged) {
            $xml->writeRaw((string) $data);
        }
    }

    private static function getArray2XML(XMLWriter $xml, $keyParent, $data) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $xml->writeElement($keyParent, $value);
                continue;
            }

            if (is_object($value) || ARR::isAssociativeArray($value)) {
                if (is_numeric($key)) {
                    $xml->startElement($keyParent);
                }
                self::_getObject2XML($xml, $value);
                if (is_numeric($key)) {
                    $xml->endElement();
                }
            } else if (is_array($value)) {
                if (is_numeric($key)) {
                    $xml->startElement($keyParent);
                }
                self::getArray2XML($xml, $key, $value);
                //if (is_numeric($key)) {
                //$xml->endElement();
                //}
                continue;
            } else { 
                if (is_numeric($key)) {
                    $xml->writeElement($keyParent, $value);
                } else { 
                    $xml->writeElement($key, $value);
                }
            }
        }
    }

}

