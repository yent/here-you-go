<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Response;


use HereYouGo\REST\Exception;
use HereYouGo\REST\Request;

/**
 * Class Structured
 *
 * @package HereYouGo\REST\Response
 */
abstract class Structured extends Base {
    /**
     * Filter out data based on requested fields
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public static function clean($data) {
        if(is_object($data) && ($data instanceof Exception))
            return $data;
        
        $fields = Request::getFields();
        if(!is_null($fields))
            self::cleanData($data, $fields);
        
        return $data;
    }
    
    /**
     * Allows to clean data to send to the reponse
     *
     * @param mixed $data
     * @param array $fields
     */
    private static function cleanData(&$data, array $fields) {
        if(!is_array($data)) return;
    
        $keys = array_keys($fields);
        
        $assoc = (count(array_filter($keys, 'is_int')) !== count($keys));
        if($assoc) {
            foreach(array_keys($data) as $k)
                if(!in_array($k, $keys))
                    unset($data[$k]);
                
            foreach($fields as $field => $sub){
                if(is_bool($sub)) continue;
                if(!array_key_exists($field, $data)) continue;
                
                self::cleanData($data[$field], $sub);
            }
            
        } else {
            foreach($data as &$item)
                self::cleanData($item, $fields);
        }
        
        $data = array_filter($data);
    }
    
    /**
     * Cast exception
     *
     * @param \Exception $e
     *
     * @return array
     */
    public static function castException(\Exception $e) {
        return [
            'message' => $e->getMessage(),
            'uid' => method_exists($e, 'getId') ? $e->getId() : null,
            'details' => method_exists($e, 'getDetails') ? $e->getDetails() : null
        ];
    }
}