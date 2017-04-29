<?php
/**
 * PHP Router Module
 *
 * @package   Router
 * @author    ShadowMan <shadowman@shellboot.com>
 * @copyright Copyright (C) 2016-2017 ShadowMan
 * @license   MIT License
 * @link      https://github.com/shadow-share/php-router
 */

namespace Router\Abstracts;


abstract class HookNode {
    /**
     * definition hook name
     *
     * @return string
     */
    abstract public function hook_name();

    /**
     * definition match callback function
     *
     */
    public function __construct() {
    }

    /**
     * current request url path
     *
     * @return string
     */
    public static function get_current_url(array $parameters) {
        return $parameters['__url__'];
    }

    /**
     * current request method
     *
     * @return string
     */
    public function get_current_method(array $parameters) {
        return $parameters['__method__'];
    }

    /**
     * main entry pointer
     */
    abstract function entry_point(array $parameters);
}
