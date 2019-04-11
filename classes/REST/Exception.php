<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST;


use HereYouGo\Exception\Base;
use HereYouGo\Exception\Detailed;

/**
 * Class Exception
 *
 * @package HereYouGo\REST
 */
class Exception extends Detailed {
    public function __construct(string $message, array $private = [], array $public = [], int $code = 500, Base $previous = null) {
        if(!array_key_exists('method', $public))
            $public['method'] = Request::getMethod();
        
        if(!array_key_exists('path', $public))
            $public['path'] = Request::getPath();
        
        parent::__construct($message, $private, $public, $code, $previous);
    }
}