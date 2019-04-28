<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

//use HereYouGo\Env;
use HereYouGo\Exception\Base;
use HereYouGo\Exception\Detailed;
use HereYouGo\Model\Updater;

include dirname(__FILE__).'/../init.php';

//Env::requireCli();

try {
    Updater::run();

} catch(Exception $e) {
    $message = $e->getMessage();

    if($e instanceof Base)
        $message .= "\nexception id : ".$e->getId();

    if($e instanceof Detailed)
        $message .= "\n".print_r($e->getDetails(), true);

    die($message."\n");
}
