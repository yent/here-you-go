<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

//use HereYouGo\Env;
use HereYouGo\Auth;
use HereYouGo\Auth\Password;
use HereYouGo\Exception\Base;
use HereYouGo\Exception\Detailed;
use HereYouGo\Model\Entity\User;

include dirname(__FILE__).'/../init.php';

//Env::requireCli();

try {
    $options = getopt('i:e:n:a:h');

    if(!$options || array_key_exists('h', $options)) {
        echo "usage : ".basename(__FILE__)." -i <user id> -e <user email> -n <user name> -a <user authentication args>\n";
        exit;
    }

    if(!array_key_exists('i', $options))
        die("missing user id\n");

    if(!array_key_exists('e', $options))
        die("missing user email\n");

    if(!filter_var($options['e'], FILTER_VALIDATE_EMAIL))
        die("bad user email\n");

    if(array_key_exists('n', $options) && !strlen($options['n']))
        die("missing user name\n");

    $user = new User($options['i'], $options['e'], $options['n']);

    $sp = Auth::getSP();
    if(array_key_exists('a', $options)) {
        switch(substr($sp, strrpos($sp, '\\'))) {
            case 'Embedded': $user->auth_args = Password::hash($options['a']); break;
        }
    }

    $user->save();

} catch(Exception $e) {
    $message = $e->getMessage();

    if($e instanceof Base)
        $message .= "\nexception id : ".$e->getId();

    if($e instanceof Detailed)
        $message .= "\ndetails : ".print_r($e->getDetails(), true);

    die($message."\n");
}
