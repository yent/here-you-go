<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\UI;

use Exception;
use HereYouGo\Auth;
use HereYouGo\Auth\SP\Internal;
use HereYouGo\Config;
use HereYouGo\Exception\Base;
use HereYouGo\HTTP\Request;

include dirname(__FILE__).'/../init.php';

try {
    $sp = Auth::getSP();
    if($sp) {
        Router::addRoute(['get', 'post'], '/login', null, "$sp::doLogin");
        Router::addRoute(['get', 'post'], '/logout', null, "$sp::doLogout");
    }

    // Catch all
    Router::addRoute(['get', 'post'], '/.*', null, function() {
        Template::resolve('main')->display();
    });

    Request::parse();

    Template::resolve('header')->display();

    try {
        Router::route();

    } catch(Exception $e) {
        Template::resolve('exception')->display(['exception' => $e]);
    }

    Template::resolve('footer')->display();

} catch(Exception $e) {
    die($e->getMessage());
}
