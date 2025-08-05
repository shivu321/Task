<?php

use Illuminate\Support\Facades\Auth;


if (!function_exists('ADMIN_USER_ID')) {
    function ADMIN_USER_ID()
    {
        if (Auth::guard('admin')->check()) {
            return (Auth::guard('admin')->user()) ? Auth::guard('admin')->user()->id : 0;
        }

        return 1;
    }
}
if (!function_exists('USER_ID')) {
    function USER_ID()
    {
        if (Auth::guard('api')->check()) {
            return (Auth::guard('api')->user()) ? Auth::guard('api')->user()->id : 0;
        }

        return null;
    }
}

if (!function_exists('getValue')) {
    function getValue($param, $default = null)
    {
        if (is_null($param) || empty($param)) {
            return $default;
        }

        if (is_string($param)) {
            return trim($param);
        }
        return $param;
    }
}
if (!function_exists('generateTransactionId')) {
    function generateTransactionId($prefix = 'TXN')
    {
        $timestamp = time();
        $randomStr = strtoupper(bin2hex(random_bytes(4))); // 8-char random hex
        return $prefix . $timestamp . $randomStr;
    }
}
