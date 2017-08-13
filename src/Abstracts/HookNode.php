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
     * main entry pointer
     */
    abstract function entry_point(array $parameters);

    /**
     * definition match callback function
     *
     */
    final public function __construct() {
    }
}
