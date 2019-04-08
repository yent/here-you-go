<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST;


use HereYouGo\Config;
use HereYouGo\Converter\JSON;
use HereYouGo\REST\Exception\BadMethod;
use HereYouGo\REST\Exception\BadParameter;
use HereYouGo\Sanitizer;

/**
 * Class Request
 * @package HereYouGo\REST
 *
 * @method
 */
class Request extends \HereYouGo\HTTP\Request {
    /** @var int|null */
    protected static $count = null;
    
    /** @var int|null */
    protected static $startIndex = null;
    
    /** @var Filter|null */
    protected static $filter = null;
    
    /** @var array|null */
    protected static $fields = null;
    
    /** @var array */
    protected static $order = [];
    
    /** @var int|null */
    protected static $updatedSince = null;
    
    /**
     * Request constructor.
     *
     * @throws BadMethod
     * @throws BadParameter
     * @throws JSON\Exception\UnableToDecode
     */
    public static function parse() {
        parent::parse();
        
        if(array_key_exists('callback', $_GET)) {
            if(self::$method !== 'get')
                throw new BadMethod(self::$method);
            
            Response::setCallback('javascript', $_GET['callback']);
        }
    
        if(array_key_exists('frame_callback', $_GET)) {
            if(!in_array(self::$method, ['get', 'post']))
                throw new BadMethod(self::$method);
        
            Response::setCallback('frame', $_GET['callback']);
        }

        if(preg_match('`^(.+)\.([A-Za-z0-9]{1,5})$`', self::$path, $match)) {
            self::$path = $match[1];

            $format = $match[2];
            if(array_key_exists('download', $_GET))
                $format = 'download:'.$format;

            if(array_key_exists('format_options', $_GET))
                $format .= ':'.$_GET['format_options'];

            Response::setFormat($format);
        }
        
        // Get response filters
        foreach($_GET as $k => $v) {
            switch($k) {
                case 'count':
                case 'startIndex':
                    if(preg_match('`^[0-9]+$`', $v)) self::$$k = (int)$v;
                    break;
            
                case 'format':
                    Response::setFormat($v);
                    break;
            
                case 'filterOp':
                    if(is_array($v)) {
                        $and = [];
                        foreach($v as $field => $test){
                            foreach(['equals', 'startWith', 'contains', 'present'] as $op){
                                if(array_key_exists($op, $test))
                                    $and[] = [$op => [$field => $test[$op]]];
                            }
                        }
                        self::$filter = new Filter(['and' => $and]);
                    }
                    break;
            
                case 'filter':
                    $filter = JSON::decode((string)$v);
                    if(!$filter)
                        throw new BadParameter('filter');
    
                    self::$filter = new Filter($filter);
                    break;
            
                case 'fields':
                    $fields = array_filter(array_unique(array_map('trim', explode(',', $v))));
                
                    if(!count($fields))
                        throw new BadParameter('fields');
    
                    self::$fields = $fields;
                    break;
            
                case 'sortOrder':
                    if ($v === 'asc') $v = 'ascending';
                    if ($v === 'desc') $v = 'descending';
                
                    if(!in_array($v, array('ascending', 'descending')))
                        throw new BadParameter('sortOrder');
    
                    self::$order['*'] = $v;
                    break;
            
                case 'order':
                    $order = JSON::decode((string)$v);
                    if(!$order)
                        throw new BadParameter('order');
                
                    foreach((array)$order as $sk => $o) {
                        if(!preg_match('`^[a-z][a-z0-9_]*$`i', $sk))
                            throw new BadParameter('order['.$sk.']');
                    
                        if ($o === 'asc') $o = 'ascending';
                        if ($o === 'desc') $o = 'descending';
                        if($o != 'ascending' && $o != 'descending')
                            throw new BadParameter('order['.$sk.'] = '.$o);
    
                        self::$order[$sk] = $o;
                    }
                    break;
            
                case 'updatedSince':
                    // updatedSince takes ISO date, relative N days|weeks|months|years format and epoch timestamp (UTC)
                    $updatedSince = null;
                    if(preg_match('`^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(Z|[+-][0-9]{2}:[0-9]{2})$`', $v)) {
                        // ISO date
                        $localetz = new \DateTimeZone(Config::get('timezone'));
                        $offset = $localetz->getOffset(new \DateTime($v));
                        $updatedSince = strtotime($v) + $offset;
                        
                    }else if(preg_match('`^([0-9]+)\s*(hour|day|week|month|year)s?$`', $v, $m)) {
                        // Relative N day|days|week|weeks|month|months|year|years format
                        $updatedSince = strtotime('-'.$m[1].' '.$m[2]);
                        
                    }else if(preg_match('`^-?[0-9]+$`', $v)) {
                        // Epoch timestamp
                        $updatedSince = (int)$v;
                    }
                
                    if(!$updatedSince || !is_numeric($updatedSince))
                        throw new BadParameter('updatedSince');
    
                    self::$updatedSince = $updatedSince;
                    break;
            }
        }
    }
    
    /**
     * Get given count
     *
     * @return int|null
     */
    public static function getCount() {
        return self::$count;
    }
    
    /**
     * Get given start index
     *
     * @return int|null
     */
    public static function getStartIndex() {
        return self::$startIndex;
    }
    
    /**
     * Get given filter
     *
     * @return Filter
     */
    public static function getFilter() {
        return self::$filter;
    }
    
    /**
     * Get given fields
     *
     * @return array
     */
    public static function getFields() {
        return self::$fields;
    }
    
    /**
     * Get given order
     *
     * @return array
     */
    public static function getOrder() {
        return self::$order;
    }
    
    /**
     * Get given updatedSince
     *
     * @return int|null
     */
    public static function getUpdatedSince() {
        return self::$updatedSince;
    }
}