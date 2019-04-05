<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Response;

use HereYouGo\Converter\JSON as JSONConv;
use HereYouGo\Exception\BadType;
use HereYouGo\REST\Response;

class CSV extends Structured {
    /**
     * Get returned Mime type
     *
     * @return mixed
     */
    public static function getMimeType() {
        return 'text/csv';
    }
    
    /**
     * Render exception
     *
     * @param \Exception $e
     *
     * @throws JSONConv\Exception\UnableToEncode
     * @throws BadType
     */
    public static function renderException(\Exception $e) {
        (new static($e))->output();
    }
    
    /**
     * Get file extension
     *
     * @return string
     */
    public static function getExtension() {
        return 'csv';
    }
    
    /**
     * Constructor
     *
     * @param mixed $data
     *
     * @throws JSONConv\Exception\UnableToEncode
     * @throws BadType
     */
    public function __construct($data) {
        if(is_object($data) && ($data instanceof \Exception)) {
            $data = static::castException($data);
            if($data['details']) $data['details'] = JSONConv::encode($data['details']);
            $data = [$data];
            
        } else {
            $data = self::clean($data);
            
            // Cast single instance to single item collection
            if(!count(array_filter(array_keys($data), 'is_int')))
                $data = [$data];
        }
        
        $field_separator = Response::getFormatOptions();
        if(!$field_separator) $field_separator = ',';
        if(strlen($field_separator) !== 1) // Don not throw, use plain rendering to avoid infinite loop
            throw new BadType('format_options', '1 chr long separator');
        
        $lines = self::getTable($data);
        
        $out = array();
        foreach($lines as $line) {
            $out[] = implode($field_separator, array_map(function($v) use($field_separator) {
                return self::encode($v, $field_separator);
            }, $line));
        }
        
        parent::__construct(implode("\n", $out));
    }
    
    /**
     * Build 2 dimension array from N-dimension data
     *
     * @param array $data
     *
     * @return array
     *
     * @throws BadType
     */
    public static function getTable($data) {
        $headers = array();
        $entries = array();
        foreach($data as $entry) {
            if(!$entry) $entry = array();
            if(!is_array($entry))
                throw new BadType($entry, 'array');
            
            $entry = self::flatten($entry);
            $entries[] = $entry;
            
            // Merge headers
            $pos = -1;
            foreach(array_keys($entry) as $h) {
                $pos++;
                
                if(array_key_exists($pos, $headers) && ($headers[$pos] == $h)) continue; // In sync
                if(in_array($h, $headers)) continue; // Not at same pos but well ...
                
                // New column
                array_splice($headers, $pos, 0, array($h));
            }
        }
        
        // Manage useless headers
        $uselessHeaders = array();
        foreach ($headers as $header){
            $header = explode('.', $header);
            if (count($header) === 1) continue;
            array_pop($header);
            while (count($header)){
                $uselessHeaders[] = implode('.', $header);
                array_pop($header);
            }
        }
        $headers = array_filter($headers, function($header) use ($uselessHeaders){
            return !in_array($header, $uselessHeaders);
        });
        
        $lines = array($headers);
        
        while($entry = array_shift($entries)) {
            $line = array();
            foreach($headers as $h) {
                $value = array_key_exists($h, $entry) ? $entry[$h] : '';
                $line[] = $value;
            }
            
            $lines[] = $line;
        }
        
        return $lines;
    }
    
    /**
     * Flatten structured data
     *
     * @param array $value
     * @param string $prefix
     *
     * @return array
     */
    private static function flatten($value, $prefix = '') {
        if(!is_array($value)) return $value;
        
        $cols = array();
        foreach($value as $k => $v) {
            if($prefix) $k = $prefix.'.'.$k;
            
            if(is_array($v)) {
                if(count($v) == count(array_filter(array_keys($v), 'is_int')))
                    $v = array_values($v); // Re-init int keys for later header merge
                
                foreach(self::flatten($v, $k) as $wk => $sv)
                    $cols[$wk] = $sv;
                
            } else {
                $cols[$k] = $v;
            }
        }
        
        return $cols;
    }
    
    /**
     * Encode field
     *
     * @param mixed $value scalar
     * @param string $field_separator
     *
     * @return string
     */
    private static function encode($value, $field_separator = ',') {
        if(is_null($value))
            return '""';
        
        if(!is_scalar($value))
            $value = '{complex:'.gettype($value).'}';
        
        $value = str_replace('"', '""', (string)$value); // Cast to string and encode quotes
        
        // Quote if field separator, new lines, leading or trailing spaces or double quotes
        if(preg_match('`('.preg_quote($field_separator).'|[\n\r"]|^\s|\s$)`', $value))
            $value = '"'.$value.'"';
        
        return $value;
    }
}