<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\REST\Endpoint;


use HereYouGo\REST\Endpoint;
use HereYouGo\Router;

class Test extends Endpoint {
    /**
     * Initialize endpoint (register routes ...)
     */
    public static function init() {
        Router::addRoute('get', '/test', null, self::class.'::listAll');
        Router::addRoute('get', '/test/:thing', null, self::class.'::echoBack');
    }

    public static function listAll() {
        return ['foo', 'bar'];
    }

    public static function echoBack($thing) {
        return ['thing' => $thing];
    }
}