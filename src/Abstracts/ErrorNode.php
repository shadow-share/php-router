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


abstract class ErrorNode {
    /**
     * definition error code
     *
     * @return int
     */
    abstract public function errno();

    /**
     * definition error message
     *
     * @return string
     */
    abstract public function error();

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
