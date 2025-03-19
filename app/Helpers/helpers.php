<?php

use Illuminate\Support\Str;

if (!function_exists('toSnakeCase')) {
    function toSnakeCase($string)
    {
        // Replace spaces with underscores first
        $string = str_replace(' ', '_', $string);
        // Convert camelCase to snake_case
        $string = preg_replace('/([a-z])([A-Z])/', '$1_$2', $string);
        // Convert to lowercase
        return strtolower($string);
    }
}
if (!function_exists('toCamelCase')) {
    function toCamelCase($string)
    {
        return Str::camel($string);
    }
}
