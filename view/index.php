<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\UI;

use Exception;
use HereYouGo\HTTP\Request;

include dirname(__FILE__).'/../init.php';

//Endpoint::loadAll();
Router::addRoute('get', '/.*', null, 'main');

try {
    Request::parse();
    Router::route();
} catch(Exception $e) {
    die($e->getMessage());
}
