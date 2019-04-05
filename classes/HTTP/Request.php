<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\HTTP;

use HereYouGo\Sanitizer;
use HereYouGo\JSON;

class Request {
    /** @var self */
    private static $analysed = null;

    /** @var string */
    private $method = '';

    /** @var string */
    private $path = '';

    /** @var string */
    private $content_type = '';

    /** @var array */
    private $content_type_params = [];

    /** @var mixed */
    private $body = null;

    /**
     * Analyse the request
     */
    private static function get() {
        if(!self::$analysed)
            self::$analysed = new self();

        return self::$analysed;
    }

    /**
     * Get requested method (lowercase)
     *
     * @return string
     */
    public static function getMethod() {
        return self::get()->method;
    }

    /**
     * Get requested path (from PATH_INFO)
     *
     * @return string
     */
    public static function getPath() {
        return self::get()->path;
    }

    /**
     * Get content type (without parameters)
     *
     * @return string
     */
    public static function getContentType() {
        return self::get()->content_type;
    }

    /**
     * get content type parameters
     *
     * @return array
     */
    public static function getContentTypeParams() {
        return self::get()->content_type_params;
    }

    /**
     * Get parsed request body
     *
     * @return mixed
     *
     * @throws JSON\Exception\UnableToDecode
     */
    public static function getBody() {
        return self::get()->body();
    }

    /**
     * Request constructor.
     */
    private function __construct() {
        $this->method = '';
        foreach (array('REQUEST_METHOD', 'X_HTTP_METHOD_OVERRIDE') as $k) {
            if (!array_key_exists($k, $_SERVER)) continue;
            $this->method = strtolower($_SERVER[$k]);
        }

        if(array_key_exists('PATH_INFO', $_SERVER))
            $this->path = trim(preg_replace('`/\s*/`', '', $_SERVER['PATH_INFO']));

        $type = 'application/binary';
        foreach (array('HTTP_CONTENT_TYPE', 'CONTENT_TYPE') as $k) {
            if (!array_key_exists($k, $_SERVER)) continue;
            $type = $_SERVER[$k];
        }

        $type = array_map('trim', explode(';', $type));
        $this->content_type = array_shift($type);

        foreach ($type as $part) {
            $part = array_map('trim', explode('=', $part));
            if (count($part) < 2) continue;
            $this->content_type_params[$part[0]] = $part[1];
        }
    }

    /**
     * Body getter
     *
     * @return mixed
     *
     * @throws JSON\Exception\UnableToDecode
     */
    private function body() {
        if(is_null($this->body)) {
            $input = file_get_contents('php://input');

            switch($this->content_type) {
                case 'text/plain':
                    $this->body = trim(Sanitizer::sanitizeInput($input));
                    break;

                case 'application/octet-stream':
                    // Don't sanitize binary input
                    $this->body = $input;
                    break;

                case 'application/x-www-form-urlencoded':
                    $data = array();
                    parse_str($input, $data);
                    $this->body = (object)Sanitizer::sanitizeInput($data);
                    break;

                case 'application/json':
                default:
                $this->body = JSON::decode(Sanitizer::sanitizeInput($input), true);
            }
        }

        return $this->body;
    }
}