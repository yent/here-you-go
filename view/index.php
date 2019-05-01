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
    if(Auth::getBackends()) {
        foreach(['/log-in' => 'logIn', '/log-out' => 'logOut', '/register' => 'register'] as $path => $call)
            Router::addRoute(['get', 'post'], $path, null, 'Auth::'.$call);
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
