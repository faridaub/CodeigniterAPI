<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// デバッグログ
if (!function_exists('log_d')) {
    function log_d($message, $php_error = FALSE) {
        $_log =& load_class('Log');
        $_log->write_log('DEBUG', $message, $php_error, 'log_api-');
    }
}
// インフォログ
if (!function_exists('log_i')) {
    function log_i($message, $php_error = FALSE) {
        $_log =& load_class('Log');
        $_log->write_log('INFO', $message, $php_error, 'log_api-');
    }
}
// エラーログ
if (!function_exists('log_e')) {
    function log_e($message, $php_error = FALSE) {
        $_log =& load_class('Log');
        $_log->write_log('ERROR', $message, $php_error, 'log_api-');
    }
}
// デバッグログ
if (!function_exists('log_debug')) {
    function log_debug($tag, $message, $php_error = FALSE) {
        $_log =& load_class('Log');
        $_log->write_log('DEBUG', $message, $php_error, 'log_api-', $tag);
    }
}
// インフォログ
if (!function_exists('log_info')) {
    function log_info($tag, $message, $php_error = FALSE) {
        $_log =& load_class('Log');
        $_log->write_log('INFO', $message, $php_error, 'log_api-', $tag);
    }
}
// エラーログ
if (!function_exists('log_error')) {
    function log_error($tag, $message, $php_error = FALSE) {
        $_log =& load_class('Log');
        $_log->write_log('ERROR', $message, $php_error, 'log_api-', $tag);
    }
}
