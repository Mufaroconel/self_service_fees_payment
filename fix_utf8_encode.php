<?php
/**
 * This file provides a replacement for the deprecated utf8_encode() function
 * Used by the Paynow SDK
 */

if (!function_exists('utf8_encode')) {
    function utf8_encode($string) {
        return mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }
} 