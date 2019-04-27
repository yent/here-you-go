<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\HTTP;

use HereYouGo\Config;
use HereYouGo\Sanitizer;

use HereYouGo\Converter\XML;
use HereYouGo\Converter\JSON;

/**
 * Class Request
 *
 * @package HereYouGo\HTTP
 */
class Request {
    /** @var bool */
    protected static $parsed = false;

    /** @var string */
    protected static $method = '';

    /** @var string */
    protected static $path = '';

    /** @var string */
    protected static $content_type = '';

    /** @var array */
    protected static $content_type_params = [];

    /** @var mixed */
    protected static $body = null;

    /**
     * Get requested method (lowercase)
     *
     * @return string
     */
    public static function getMethod() {
        return self::$method;
    }

    /**
     * Get requested path (from PATH_INFO)
     *
     * @return string
     */
    public static function getPath() {
        return self::$path;
    }

    /**
     * Get content type (without parameters)
     *
     * @return string
     */
    public static function getContentType() {
        return self::$content_type;
    }

    /**
     * get content type parameters
     *
     * @return array
     */
    public static function getContentTypeParams() {
        return self::$content_type_params;
    }

    /**
     * Get parsed request body
     *
     * @return mixed
     *
     * @throws JSON\Exception\UnableToDecode
     */
    public static function getBody() {
        if(is_null(self::$body)) {
            self::parse();
            
            $input = file_get_contents('php://input');
        
            switch(self::$content_type) {
                case 'text/plain':
                    self::$body = trim(Sanitizer::sanitizeInput($input));
                    break;
            
                case 'application/octet-stream':
                    // Don't sanitize binary input, don't prefetch either
                    self::$body = $input;
                    break;
            
                case 'application/x-www-form-urlencoded':
                    $data = array();
                    parse_str($input, $data);
                    self::$body = (object)Sanitizer::sanitizeInput($data);
                    break;
            
                case 'text/xml':
                    self::$body = XML::parse($input); // Do not sanitize because it breaks xml
                    break;
            
                case 'application/json':
                default:
                self::$body = JSON::decode(Sanitizer::sanitizeInput($input), true);
            }
        }
    
        return self::$body;
    }

    /**
     * Request constructor.
     */
    public static function parse() {
        if(self::$parsed) return;
        
        self::$method = '';
        foreach (array('REQUEST_METHOD', 'X_HTTP_METHOD_OVERRIDE') as $k) {
            if (!array_key_exists($k, $_SERVER)) continue;
            self::$method = strtolower($_SERVER[$k]);
        }

        if(Config::get('nice_urls')) {
            if(array_key_exists('PATH_INFO', $_SERVER))
                self::$path = $_SERVER['PATH_INFO'];

        } else if(array_key_exists('path', $_REQUEST)) {
            self::$path = $_REQUEST['path'];
        }

        self::$path = '/'.trim(preg_replace('`/\s*/`', '/', self::$path), '/');

        $type = 'application/binary';
        foreach (array('HTTP_CONTENT_TYPE', 'CONTENT_TYPE') as $k) {
            if (!array_key_exists($k, $_SERVER)) continue;
            $type = $_SERVER[$k];
        }

        $type = array_map('trim', explode(';', $type));
        self::$content_type = array_shift($type);

        foreach ($type as $part) {
            $part = array_map('trim', explode('=', $part));
            if (count($part) < 2) continue;
            self::$content_type_params[$part[0]] = $part[1];
        }
    }
}