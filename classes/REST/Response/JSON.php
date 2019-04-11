<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Response;


use HereYouGo\Converter\JSON as JSONConv;
use HereYouGo\Converter\JSON\Exception\UnableToEncode;
use HereYouGo\REST\Exception;

/**
 * Class JSON
 *
 * @package HereYouGo\REST\Response
 */
class JSON extends Structured {
    /**
     * Constructor
     *
     * @param mixed $data
     *
     * @throws UnableToEncode
     */
    public function __construct($data) {
        if(is_object($data) && ($data instanceof Exception)) {
            $data = static::castException($data);
            
        } else {
            $data = self::clean($data);
        }
        
        parent::__construct(JSONConv::encode($data));
    }
    
    /**
     * Exception rendering shorthand
     *
     * @param \Exception $e
     *
     * @throws UnableToEncode
     */
    public static function renderException(\Exception $e) {
        (new static($e))->output();
    }
    
    /**
     * Get returned Mime type
     *
     * @return mixed
     */
    public static function getMimeType() {
        return 'application/json';
    }
    
    /**
     * Get file extension
     *
     * @return string
     */
    public static function getExtension() {
        return 'json';
    }
}