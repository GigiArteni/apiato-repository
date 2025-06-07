<?php
// Polyfill the app() helper for non-Laravel test environment
if (!function_exists('app')) {
    global $__test_container;
    function app($abstract = null) {
        global $__test_container;
        if (!isset($__test_container)) {
            $__test_container = new Illuminate\Container\Container();
        }
        if ($abstract === null) return $__test_container;
        return $__test_container->make($abstract);
    }
}
