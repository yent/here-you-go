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
use HereYouGo\HTTP\Request;

include dirname(__FILE__).'/../init.php';

try {
    $sp = Auth::getSP();
    if($sp) {
        Router::addRoute(['get', 'post'], '/log-in', null, "$sp::doLogin");
        Router::addRoute(['get', 'post'], '/log-out', null, "$sp::doLogout");
    }

    // Catch all
    Router::addRoute(['get', 'post'], '/.*', null, new Page('main'));

    Request::parse();

    try {
        Router::route();

    } catch(Exception $e) {
        (new Page('exception', ['exception' => $e]))->display();
    }

} catch(Exception $e) {
    die($e->getMessage());
}
