<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

use HereYouGo\Config;

define('HYG_ROOT', dirname(__FILE__).'/');
include HYG_ROOT.'classes/autoload.php';

if(file_exists(HYG_ROOT.'preload.php'))
    include HYG_ROOT.'preload.php';

if(!defined('HYG_CONFIG'))
    define('HYG_CONFIG', HYG_ROOT.'config/');

$timezone = Config::get('timezone');
if($timezone)
    date_default_timezone_set($timezone);

// TODO session_set_cookie_params();

session_start();