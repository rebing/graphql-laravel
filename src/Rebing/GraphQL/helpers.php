<?php

if (! function_exists('is_lumen')) {
    /**
     * Detect if Lumen installed.
     *
     * @return bool
     */
    function is_lumen() {
        return class_exists('Laravel\Lumen\Application');
    }
}
