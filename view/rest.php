<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\REST;

use Exception;

include dirname(__FILE__).'/../init.php';

Endpoint::loadAll();

try {
    Request::parse();
    Router::route();
} catch(Exception $e) {
    try {
        Response::send($e);
    } catch(Exception $e) {
        die($e->getMessage());
    }
}
