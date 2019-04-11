<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Response;


use HereYouGo\Exception\UnknownProperty;

/**
 * Class Base
 *
 * @package HereYouGo\REST\Response
 */
abstract class Base {
    /** @var string */
    protected $data = null;
    
    /**
     * Get returned Mime type
     *
     * @return mixed
     */
    abstract public static function getMimeType();
    
    /**
     * Render exception
     *
     * @param \Exception $e
     */
    abstract public static function renderException(\Exception $e);
    
    /**
     * Get file extension
     *
     * @return string
     */
    abstract public static function getExtension();
    
    /**
     * Constructor
     *
     * @param string $data
     */
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * Output data
     */
    public function output() {
        header('Content-Type: '.static::getMimeType());
        header('Content-Length: '.strlen($this->data));
        
        echo $this->data;
        
        exit;
    }
    
    /**
     * Getter
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if($name === 'data') return $this->data;
        
        throw new UnknownProperty($this, $name);
    }
}