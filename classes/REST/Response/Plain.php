<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Response;


use HereYouGo\Exception\BadType;

/**
 * Class Plain
 *
 * @package HereYouGo\REST\Response
 */
class Plain extends Base {
    /**
     * Constructor
     *
     * @param mixed $data
     *
     * @throws BadType
     */
    public function __construct($data) {
        if(is_object($data) && ($data instanceof \Exception)) {
            $msg = $data->getMessage();
            
            if(method_exists($data, 'getUid'))
                $msg .= ' (uid: '.$data->getUid().')';
            
            if(method_exists($data, 'getDetails'))
                $msg .= ', details: '."\n".print_r($data->getDetails(), true);
            
            $data = $msg;
        }
        
        if(!is_scalar($data) && !is_null($data))
            throw new BadType(static::class, 'scalar data');
        
        parent::__construct((string)$data);
    }
    
    /**
     * Exception rendering shorthand
     *
     * @param \Exception $e
     *
     * @throws BadType
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
        return 'text/plain';
    }
    
    /**
     * Get file extension
     *
     * @return string
     */
    public static function getExtension() {
        return 'txt';
    }
}