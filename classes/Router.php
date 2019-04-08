<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;


use HereYouGo\Exception\BadType;
use HereYouGo\HTTP\Request;
use HereYouGo\Router\Exception\MissingArgument;
use HereYouGo\Router\Exception\NoMatchingRoute;
use HereYouGo\UI\Template;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

abstract class Router {
    /** @var array */
    protected static $routes = [];

    /**
     * Add route
     *
     * @param string|array $method
     * @param string|array $path
     * @param string|array $profile
     * @param string|callable $action
     */
    public static function addRoute($method, $path, $profile, $action) {
        foreach((array)$method as $m) {
            $m = strtolower($m);

            if (!array_key_exists($m, self::$routes))
                self::$routes[$m] = [];

            foreach ((array)$path as $p) {
                if(substr($p, -1) === '/') $p = substr($p, 0, -1);
                $regexp = '`^'.preg_replace('`:(\w+)`', '(?<$1>[^/]+)', $p).'$`';
                self::$routes[$m][$p] = ['regexp' => $regexp, 'profile' => $profile, 'action' => $action];
            }
        }
    }

    /**
     * Route current request
     *
     * @throws MissingArgument
     * @throws BadType
     * @throws NoMatchingRoute
     */
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

                $args = [];
                if(is_callable($route['action'])) {
                    try {
                        if(is_string($route['action']) && strpos($route['action'], '::')) {
                            $action = explode('::', $route['action'], 2);
                            $reflexion = new ReflectionMethod($action[0], $action[1]);

                        } else if(is_array($route['action'])) {
                            $reflexion = new ReflectionMethod($route['action'][0], $route['action'][1]);

                        } else {
                            $reflexion = new ReflectionFunction($route['action']);
                        }

                        foreach($reflexion->getParameters() as $param) {
                            if(array_key_exists($param->getName(), $match)) {
                                $args[] = $match[$param->getName()];

                            } else if($param->isOptional()) {
                                $args[] = $param->getDefaultValue();

                            } else {
                                throw new MissingArgument($method, $path_match, $param->getName());
                            }
                        }
                    } catch(ReflectionException $e) {
                        throw new BadType($route['action'], 'Reflexion compliant callable');
                    }

                }

                static::run($route['action'], $args);

                return;
            }
        }

        (new Event('no_mathing_route', [$method, $path]))->trigger(function($method, $path) {
            throw new NoMatchingRoute($method, $path);
        });
    }

    /**
     * Run routed action
     *
     * @param string|callable $action
     * @param array $args
     */
    abstract protected static function run($action, $args);
}