<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\REST;


use HereYouGo\Exception\BadType;
use HereYouGo\REST\Exception\BadParameter;
use HereYouGo\UI\Exception\TemplateNotFound;

/**
 * Class Router
 *
 * @package HereYouGo\REST
 */
class Router extends \HereYouGo\Router {
    /**
     * Run routed action
     *
     * @param string|callable $action
     * @param array $args
     *
     * @throws BadType
     * @throws BadParameter
     */
    protected static function run($action, $args) {
        if(!is_callable($action))
            throw new BadType($action, 'callable');

        $result = call_user_func_array($action, $args);

        Response::send($result);
    }
}