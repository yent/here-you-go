<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\UI;


use HereYouGo\UI\Exception\TemplateNotFound;

/**
 * Class Router
 *
 * @package HereYouGo\UI
 */
class Router extends \HereYouGo\Router {
    /**
     * Run routed action
     *
     * @param string|callable $action
     * @param array $args
     *
     * @throws TemplateNotFound
     */
    protected static function run($action, $args) {
        if(is_callable($action))
            $action = call_user_func_array($action, $args);

        if($action instanceof Page) {
            $action->display();

        } else if(is_string($action)) {
            Template::resolve($action)->display();
        }
    }
}