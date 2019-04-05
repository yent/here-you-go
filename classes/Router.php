<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;


use HereYouGo\HTTP\Request;
use ReflectionException;
use ReflectionFunction;

class Router {
    private static $routes = [];


    public static function addRoute($method, $path, $profile, $action) {
        foreach((array)$method as $m) {
            $m = strtolower($m);

            if (!array_key_exists($m, self::$routes))
                self::$routes[$m] = [];

            foreach ((array)$path as $p) {
                $regexp = '`^'.trim(preg_replace('`:(\w+)`', '(?<$1>[^/]+)', $p), '/').'$`';
                self::$routes[$m][$p] = ['regexp' => $regexp, 'profile' => $profile, 'action' => $action];
            }
        }
    }

    public static function route() {
        $method = Request::getMethod();
        $path = Request::getPath();

        $routes = [];
        if(array_key_exists($method, self::$routes)) {
            $routes = self::$routes[$method];

        } else if(array_key_exists('*', self::$routes)) {
            $routes = self::$routes['*'];
        }

        foreach($routes as $path_match => $route) {
            if(preg_match($route['regexp'], $path, $match)) {
                // TODO check profile(s)

                if(is_callable($route['action'])) {
                    $args = [];
                    try {
                        foreach((new ReflectionFunction($route['action']))->getParameters() as $param) {
                            if(array_key_exists($param->getName(), $match)) {
                                $args[] = $match[$param->getName()];

                            } else if($param->isOptional()) {
                                $args[] = $param->getDefaultValue();

                            } else {
                                throw new MissingArgRouterException($method, $path_match, $param->getName());
                            }
                        }
                    } catch(ReflectionException $e) {
                        throw new CannotAnalyseActionRouterException($method, $path_match);
                    }

                    $route['action'] = call_user_func_array($route['action'], $args);
                }

                if($route['action']) Template::display($route['action']);
            }
        }

        throw new NoMatchingRouterException($method, $path);
    }
}