<?php
class Router {
    private $_routes = array();

    public function __construct() {
        $this->_routes = $this->_getRoutes();
    }

    public function match($request = '') {
        foreach ($this->_routes as $route => $handler) {
            // Check for a wildcard (matches all)
            $route = null;
            $regex = false;
            $j = 0;
            $n = isset($_route[0]) ? $_route[0] : null;
            $i = 0;

            // Find the longest non-regex substring and match it against the URI
            while (true) {
                if (!isset($_route[$i])) {
                    break;
                } elseif (false === $regex) {
                    $c = $n;
                    $regex = $c === '[' || $c === '(' || $c === '.';
                    if (false === $regex && false !== isset($_route[$i+1])) {
                        $n = $_route[$i + 1];
                        $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
                    }
                    if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
                        continue 2;
                    }
                    $j++;
                }
                $route .= $_route[$i++];
            }

            $regex = $this->_compileRoute($route);
            $match = preg_match($regex, $request, $params);

            if(($match == true || $match > 0)) {

                if($params) {
                    foreach($params as $key => $value) {
                        if(is_numeric($key)) unset($params[$key]);
                    }
                }

                return array(
                    'target' => $target,
                    'params' => $params,
                    'name' => $name
                );
            }
        }
        return false;

    }

    public function generateUrl($alias = null, $params = array()) {
        // Check if named route exists
        if(!isset($this->namedRoutes[$routeName])) {
            throw new \Exception("Route '{$routeName}' does not exist.");
        }

        // Replace named parameters
        $route = $this->namedRoutes[$routeName];
        $url = $route;

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

            foreach($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if ($pre) {
                    $block = substr($block, 1);
                }

                if(isset($params[$param])) {
                    $url = str_replace($block, $params[$param], $url);
                } elseif ($optional) {
                    $url = str_replace($block, '', $url);
                }
            }


        }

        return $url;
    }

    private function _compileRoutes($routes = array()) {
        foreach ($routes as $route => $handler) {
            if (strpos($route, '(') === false) {
                $routes["~" . $route . "~"] = $handler;
            } else {
            }
            list($block, $pre, $type, $param, $optional) = $match;

            if (isset($match_types[$type])) {
                $type = $match_types[$type];
            }
            if ($pre === '.') {
                $pre = '\.';
            }

            //Older versions of PCRE require the 'P' in (?P<named>)
            $pattern = '(?:'
                . ($pre !== '' ? $pre : null)
                . '('
                . ($param !== '' ? "?P<$param>" : null)
                . $type
                . '))'
                . ($optional !== '' ? '?' : null);

            $route = str_replace($block, $pattern, $route);
        }
        return "`^$route$`";
    }


    /**
     * 从配置文件获取路由
     *
     * @access private 
     * @return array
     */
    function _getRoutes() {
        if (file_exists(TMP . 'routes' . EXT)) {
            $routes = require TMP . 'routes' . EXT;
        } else {
            $routes = array();
            foreach (glob(APP . '*') as $app) {
                if (file_exists($app . DS . 'routes' . EXT)) {
                    $routes = array_merge($routes, include($app . DS . 'routes' . EXT));
                }
            }
            file_put_contents(TMP . 'routes' . EXT, compress_php_src(array_to_string($routes)));
        }

        return $this->_compileRoutes($routes);
    }
}
