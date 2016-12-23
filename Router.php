<?php
/**
 * PHP Router Module
 * 
 * @package   Router
 * @author    ShadowMan <shadowman@shellboot.com>
 * @copyright Copyright (C) 2016 ShadowMan
 * @license   MIT License
 * @link      
 */

class Router {
    private $_tree = array();
    private $_error = array();
    private $_hooks = array();
    private $_variable = array();
    private $_callback_params = array();
    
    private $_validate = array('A' => 'alnum', 'a' => 'alpha', 'd' => 'digit', 'l' => 'lower', 'u' => 'upper');

    private static $_request_methods = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'ALL');

    public function __construct($router_file = null) {
        $this->_tree = array();
        $this->_error = array();
        $this->_hooks = array();

        $this->_variable['request_method'] = $_SERVER['REQUEST_METHOD'];
        $this->_variable['current_url'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    # add new router node
    public function __call($call_name, $args) {
        $call_name = strtoupper($call_name);

        if (in_array($call_name, self::$_request_methods)) {
            if ($call_name == 'ALL') {
                $methods = self::$_request_methods;
                array_pop($methods);
            } else {
                $methods = array($call_name);
            }

            # filter urls
            $urls = array_shift($args);
            if (!is_array($urls)) {
                $urls = array($urls);
            }
            $urls = array_filter($urls, function($url) {
                return is_string($url);
            });

            # filter callback
            $callback = array_shift($args);
            if (!is_array($callback)) {
                $callback = array($callback);
            }
            $callback = array_filter($callback, function($cb) {
                return is_callable($cb);
            });

            # filter hook
            $hook = array_filter($args, function($hook) {
                return is_string($hook) && array_key_exists($hook, $this->_hooks);
            });

            $this->_create_router($methods, $urls, $callback, $hook);
        } else if (in_array($call_name, array('ERROR', 'HOOK'))) {
            $handler_key = array_shift($args);
            $handler = array_shift($args);

            if (is_callable($handler)) {
                if ($call_name == 'ERROR') {
                    $this->_create_error_handler($handler_key, $handler);
                } else {
                    $this->_create_hook_handler($handler_key, $handler);
                }
            }
        } else {
            throw new Exception("fatal error: anyonmous call({$call_name}) not found");
        }

        return $this;
    }

    public function entry($request_method = null, $request_url = null) {
        $request_method = strtoupper($request_method ? $request_method : $this->_variable['request_method']);
        $request_url = $request_url ? $request_url : $this->_variable['current_url'];

        $this->_callback_params = array(
            '__url__' => $request_url,
            '__method__' => $request_method,
        );

        list($callback, $hooks) = $this->_reslove($request_method, $request_url, $this->_callback_params);

       if (($callback == null && $hooks == null) || (empty($callback) && empty($hooks))) {
           if (array_key_exists('errno', $this->_callback_params)) {
               $this->emit_error($this->_callback_params['errno'], array_key_exists('error', $this->_callback_params) ? $this->_callback_params['error'] : '');
           } else {
               $this->emit_error('500', 'server internal error');
           }

           if (_here_hook_error_after_exit_ == true) {
               exit();
           }
       }

        if (!empty($hooks)) {
            array_map(function($hook) {
                if ($this->_emit_hook($hook, $this->_callback_params) == false) {
                    $this->emit_error(_here_hook_emit_error_);
                    if (_here_hook_error_after_exit_ == true) {
                        exit();
                    }
                }
            }, $hooks);
        }

        if (!empty($callback)) {
            array_map(function($cb) {
                call_user_func_array($cb, array($this->_callback_params));
            }, $callback);
        }
    }

    public function emit_error($error_code/* ... other args ... */) {
        if (!array_key_exists($error_code, $this->_error)) {
            throw new Exception('error handler not found', 1996);
        }

        $args = func_get_args();
        return call_user_func_array($this->_error[$error_code], $args);
    }

    public function request_method() {
        return $this->_variable['request_method'];
    }

    public function current_url() {
        return $this->_variable['current_url'];
    }

    private function _emit_hook($hook_name, $params) {
        return $this->_hooks[$hook_name]($params);
    }

    private function _create_router($methods, $urls, $callback, $hook) {
        foreach ($methods as $method) {
            if (!array_key_exists($method, $this->_tree)) {
                $this->_tree[$method] = array();
            }

            foreach ($urls as $url) {
                $trim_url = trim($url, self::$URL_SEPARATOR);
                $new_node = explode(self::$URL_SEPARATOR, str_replace('.', self::$URL_SEPARATOR, $trim_url));
                $this->_create_router_node($this->_tree[$method], $new_node, $callback, $hook);
            }
        }
        return $this;
    }

    private function _create_router_node(&$tree, $new_node, $callback, $hook) {
        $current_node = array_shift($new_node);

        # variable router node
        if ($current_node && $current_node[0] == self::$VAR_ROUTER) {
            if (!array_key_exists(self::$VAR_ROUTER, $tree)) {
                $tree[self::$VAR_ROUTER] = array();
            }

            $var_router_name = substr($current_node, 1);
            if (!array_key_exists($var_router_name, $tree[self::$VAR_ROUTER])) {
                $tree[self::$VAR_ROUTER][$var_router_name] = array();
            }
            return self::_create_router_node($tree[self::$VAR_ROUTER][$var_router_name], $new_node, $callback, $hook);
        }

        # re match router node
        if ($current_node && $current_node[0] == self::$RE_ROUTER) {
            if (!array_key_exists(self::$RE_ROUTER, $tree)) {
                $tree[self::$RE_ROUTER] = array();
            }

            $re_router_name = substr($current_node, 1);
            if (!array_key_exists($re_router_name, $tree[self::$RE_ROUTER])) {
                $tree[self::$RE_ROUTER][$re_router_name] = array();
            }
            return self::_create_router_node($tree[self::$RE_ROUTER][$re_router_name], $new_node, $callback, $hook);
        }

        if ($current_node && array_key_exists($current_node, $tree)) {
            return self::_create_router_node($tree[$current_node], $new_node, $callback, $hook);
        } else if ($current_node) {
            $tree[$current_node] = array();
            return self::_create_router_node($tree[$current_node], $new_node, $callback, $hook);
        }

        // create handler node
        if (!array_key_exists(self::$HANDLE, $tree)) {
            $tree[self::$HANDLE] = array(
                    self::$CALLBACK => array(),
                    self::$HOOK => array()
            );
        }

        # write router handler
        if ($new_node == null) {
            $tree[self::$HANDLE][self::$CALLBACK] = array_merge($tree[self::$HANDLE][self::$CALLBACK], $callback);
            $tree[self::$HANDLE][self::$HOOK] = array_merge($tree[self::$HANDLE][self::$HOOK], $hook);
        }
    }

    private function _create_error_handler($error, $handler) {
        $this->_error[$error] = $handler;
    }

    private function _create_hook_handler($hook_name, $handler) {
        $this->_hooks[$hook_name] = $handler;
    }

    private function _reslove($request_method, $request_url, &$params) {
        if (!array_key_exists($request_method, $this->_tree)) {
            $params['errno'] = '404';
            $params['error'] = 'router not found this request method';

            return array(null, null);
        }

        $trim_url = trim($request_url, self::$URL_SEPARATOR);
        $nodes = explode(self::$URL_SEPARATOR, str_replace('.', self::$URL_SEPARATOR, $trim_url));

        return $this->_search_router($this->_tree[$request_method], $nodes, $params);
    }

    private function _search_router($tree, $nodes, &$params) {
        $require_node = array_shift($nodes);

        # search complete
        if ($require_node == null) {
            if (array_key_exists(self::$HANDLE, $tree)) {
                return array($tree[self::$HANDLE][self::$CALLBACK], $tree[self::$HANDLE][self::$HOOK]);
            } else {
                $params['errno'] = '404';
                $params['error'] = 'router handler not defined';

                return array(null, null);
            }
        }

        # First, full matching router
        foreach ($tree as $node => $value) {
            if ($node == $require_node) {
                return $this->_search_router($tree[$node], $nodes, $params);
            }
        }

        # no variable routing
        if (empty($tree[self::$RE_ROUTER]) && empty($tree[self::$VAR_ROUTER])) {
            $params['errno'] = '404';
            $params['error'] = 'no matching router';

            return array(null, null);
        }

        # second, re matching routing
        if (!empty($tree[self::$RE_ROUTER])) {
            $re_nodes = $tree[self::$RE_ROUTER];

            foreach ($re_nodes as $re => $node) {
                $test_re = self::$RE_ROUTER . $re . self::$RE_ROUTER;
                if (preg_match($test_re, $require_node, $matches)) {
                    if (array_key_exists('errno', $params)) {
                        unset($params['errno']);
                        unset($params['error']);
                    }
                    $params['re'] = array_key_exists('re', $params) ? array_merge($params['re'], $matches) : $matches;
                    var_dump($params);
                } else {
                    $params['errno'] = '404';
                    $params['error'] = 'no re-matching routing';

                    continue;
                }

                return $this->_search_router($re_nodes[$re], $nodes, $params);
            }
        }

        if (empty($tree[self::$VAR_ROUTER])) {
            $params['errno'] = '404';
            $params['error'] = 'no var-matching routing';

            return array(null, null);
        } else {
            $var_node = $tree[self::$VAR_ROUTER];
            foreach ($var_node as $key => $val) {
                if ($pos = strpos($key, ':')) {
                    if (array_key_exists($key[$pos + 1], $this->_check) && !call_user_func('ctype_' . $this->_check[$key[$pos + 1]], $require_node)) {
                        $params['errno'] = '404';
                        $params['error'] = 'var-matching validate failure';

                        continue;
                    } else {
                        unset($params['errno']);
                        unset($params['error']);
                    }
                }

                $params[$pos ? substr($key, 0, $pos) : $key] = $require_node;
                list($callback, $hooks) = $this->_search_router($var_node[$key], $nodes, $params);

                if ($callback != null && empty($callback)) {
                    return array($callback, $hooks);
                }
                unset($params[$key]);
            }
        }

        return array(null, null);
    }

    # url separator
    private static $URL_SEPARATOR = '/';

    # handler
    private static $HANDLE = '=';

    # variable router match flag
    private static $VAR_ROUTER = '$';

    # re match node
    private static $RE_ROUTER = '@';

    # callback node
    private static $CALLBACK = '__cb__';

    # hook node
    private static $HOOK = '__hk__';

    # excetipn node
    private static $EXCEPTION = '__ep__';
}
