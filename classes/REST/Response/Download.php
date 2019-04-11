<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Response;


use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\REST\Request;
use HereYouGo\REST\Response;

/**
 * Class Download
 *
 * @package HereYouGo\REST\Response
 */
class Download {
    /** @var string */
    protected $name = 'file';
    
    /** @var mixed */
    protected $data = null;
    
    /**
     * Constructor
     *
     * @param mixed $data data or ready-made response
     * @param string $name downloaded file name
     */
    public function __construct($data, $name = null) {
        if(!$name) {
            $path = Request::getPath();
            $name = $path ? str_replace('/', '_', $path) : 'file';
            
            $format = ($data instanceof Base) ? $data::getExtension() : Response::getFormat();
            
            if($format)
                $name .= '.'.$format;
        }
        
        $this->name = $name;
        $this->data = $data;
    }
    
    /**
     * Output data
     *
     * @throws BadType
     */
    public function output() {
        // At this point data should be a renderer
        if(!($this->data instanceof Base))
            throw new BadType(static::class, 'scalar data');
        
        // UTF8 filename handling
        $ua = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if(preg_match('`msie (7|8)`i', $ua) && !preg_match('`opera`i', $ua)) {
            // IE7, IE8 but not opera that MAY match
            header('Content-Disposition: attachment; filename='.rawurlencode($this->name));
            
        } else if(preg_match('`android`i', $ua)) {
            // Android OS
            $name = preg_replace('`[^a-z0-9\._\-\+,@£\$€!½§~\'=\(\)\[\]\{\}]`i', '_', $this->name);
            header('Content-Disposition: attachment; filename="'.$name.'"');
            
        } else {
            // All others, see RFC 5987
            header('Content-Disposition: attachment; filename="'.$this->name.'"; filename*=UTF-8\'\''.rawurlencode($this->name));
        }
        
        header('Content-Transfer-Encoding: binary');
        
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $this->data->output();
    }
    
    /**
     * Getter
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name) {
        if($name === 'name') return $this->name;
        
        return parent::__get($name);
    }
}