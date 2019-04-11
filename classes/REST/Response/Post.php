<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Response;


use HereYouGo\Config;
use HereYouGo\HTTP\Response;

/**
 * Class Post
 *
 * @package HereYouGo\REST\Response
 */
class Post {
    /** @var string */
    private $location = null;
    
    /** @var mixed */
    private $data = null;
    
    /**
     * Constructor
     *
     * @param string $location
     * @param mixed $data
     */
    public function __construct($location, $data) {
        $this->location = $location;
        $this->data = $data;
    }
    
    /**
     * Output data
     */
    public function prepare() {
        Response::sendCode(201);
        
        $path = $this->location;
        if(substr($path, 0, 1) != '/') $path = '/'.$path;
        
        header('Location: '.Config::get('base_url').'rest.php'.$path);
        
        return $this->data;
    }
    
}