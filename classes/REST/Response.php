<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST;


use HereYouGo\REST\Exception\BadParameter;
use HereYouGo\REST\Response\Base;
use HereYouGo\REST\Response\JSON;
use HereYouGo\Converter\JSON as JSONConv;
use HereYouGo\REST\Response\Post;

class Response {
    /** @var string */
    protected static $format = 'json';
    
    /** @var bool */
    protected static $download = false;
    
    /** @var string|null */
    protected static $format_options = null;
    
    /** @var array */
    private static $callback = null;
    
    /**
     * Set format from given data
     *
     * @param string $raw
     */
    public static function setFormat($raw) {
        static $equiv = ['txt' => 'plain', 'htm' => 'html', 'jpg' => 'jpeg'];
        
        if(preg_match('`^((?:download:)?)([a-z0-9_-]+)((?::.+)?)$`i', $raw, $m)) {
            $fmt = array_key_exists($m[2], $equiv) ? $equiv[$m[2]] : $m[2];
            
            self::$download = ($m[1] == 'download:');
            self::$format = $fmt;
            self::$format_options = substr($m[3], 1);
        }
    }
    
    /**
     * Get requested format
     *
     * @return string
     */
    public static function getFormat() {
        return self::$format;
    }
    
    /**
     * Get requested format options
     *
     * @return string
     */
    public static function getFormatOptions() {
        return self::$format_options;
    }
    
    /**
     * Set the callback (jsonp, jsonp POST)
     *
     * @param string $type
     * @param string $name
     *
     * @throws BadParameter
     */
    public static function setCallback($type, $name = null) {
        if(!$type) {
            self::$callback = null;
            return;
        }
        
        if(!in_array($type, ['javascript', 'html']))
            throw new BadParameter('callback_type');
        
        $name = preg_replace('`[^a-z0-9_\.-]`i', '', (string)$name);
        if(!$name)
            throw new BadParameter('callback');
        
        self::$callback = ['type' => $type, 'name' => $name];
    }
    
    /**
     * Send response
     *
     * @param mixed $data
     * @param int $code HTTP code
     *
     * @throws BadParameter
     */
    public static function send($data = null, $code = 200) {
        try {
            // Output data
            if(self::$callback) {
                if(is_object($data) && ($data instanceof Exception))
                    $data = JSON::castException($data);
                
                if(!self::$callback['name']) // no callback name : return json exception
                    JSON::renderException(new BadParameter('callback'));
                
                if(self::$callback['type'] === 'javascript') {
                    // script embedding call
                    header('Content-Type: text/javascript');
                    echo self::$callback['name'].'('.JSONConv::encode($data).');';
                    
                } else if(self::$callback['type'] === 'frame') {
                    // Frame embeddeding call
                    header('Content-Type: text/html');
                    echo '<html><body><script type="text/javascript">';
                    echo 'window.parent.'.self::$callback['name'].'('.JSONConv::encode($data).');';
                    echo '</script></body></html>';
                    
                } else {
                    // Unknown call type : return json exception
                    JSON::renderException(new BadParameter('callback type'));
                }
                
                exit;
            }
            
            $format = strtolower(self::$format);
            if(!$format) $format = 'json';
            
            static $equiv = ['json' => 'JSON', 'csv' => 'CSV'];
            
            $renderer = __NAMESPACE__.'\\Response\\'.(array_key_exists($format, $equiv) ? $equiv[$format] : ucfirst($format));
            if(!class_exists($renderer) || (new \ReflectionClass($renderer))->isAbstract() || !is_subclass_of($renderer, Base::class)) {
                // unknown/abstract renderer => fall back to json
                $renderer = __NAMESPACE__.'\\Response\\JSON';
                $data = new BadParameter('format'); // Don't throw
            }
            
            if(is_object($data) && ($data instanceof \Exception)) {
                $code = $data->getCode();
                if($code < 400 || $code >= 600) $code = 500;
            }
        
            if(is_object($data) && ($data instanceof Post)) {
                $data = $data->prepare();
                
            } else {
                \HereYouGo\HTTP\Response::sendCode($code);
            }
            
            if(is_object($data) && ($data instanceof Base)) {
                // Already a renderer
                $renderer = $data;
                
            } else if(is_object($data) && ($data instanceof RestFileDownload)) {
                if (!is_object($data->data) or !($data->data instanceof RestResponseRaw)){
                    // Untyped download
                    $renderer = new RestFileDownload(new $renderer($data->data));
                }else{
                    $renderer = $data;
                }
            } else {
                // Raw data
                $renderer = new $renderer($data);
            }
            
            if(self::$download && !($data instanceof RestFileDownload)) {
                // Download requested, cast to download if needed
                $renderer = new RestFileDownload($renderer);
            }
            
            $renderer->output();
            
        } catch(\Exception $e) {
            $code = $e->getCode();
            self::send($e, $code ? $code : 500);
            return;
        }
    }
}