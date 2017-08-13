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


abstract class RouteNode {
    /**
     * definition match urls
     *
     * @return array
     */
    abstract public function urls();

    /**
     * definition match methods
     *
     * @return array
     */
    abstract public function methods();

    /**
     * definition template before hooks
     *
     * @return array
     */
    abstract public function hooks();

    /**
     * main entry
     *
     * @param array $parameters
     * @return mixed
     */
    abstract function entry_point(array $parameters);

    /**
     * definition match callback function
     *
     */
    final public function __construct() {
    }
}
