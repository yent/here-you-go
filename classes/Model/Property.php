<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\Model;


use DateTime;
use DateTimeZone;
use Exception;
use HereYouGo\Autoloader;
use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Model\Constant\IntSize;
use HereYouGo\Model\Constant\Type;
use HereYouGo\Model\Exception\Broken;
use ReflectionException;

/**
 * Class Property
 *
 * @package HereYouGo\Model
 *
 * @property-read string $class
 * @property-read string $name
 * @property-read string $type
 * @property-read mixed|null $size
 * @property-read bool $unsigned
 * @property-read bool $null
 * @property-read mixed|null $default
 * @property-read bool $primary
 * @property-read bool $auto_increment
 * @property-read bool[] $indexes
 * @property-read Converter|false $converter
 * @property-read string|null $column
 * @property-read string|null $related_class
 */
class Property {
    /** @var string */
    protected $class = '';
    
    /** @var string */
    protected $name = '';
    
    /** @var string */
    protected $type = '';
    
    /** @var mixed|null */
    protected $size = null;
    
    /** @var bool */
    protected $unsigned = false;
    
    /** @var bool */
    protected $null = false;
    
    /** @var mixed|null */
    protected $default = null;
    
    /** @var bool */
    protected $primary = false;
    
    /** @var bool */
    protected $auto_increment = false;
    
    /** @var bool[] */
    protected $indexes = [];
    
    /** @var Converter|false */
    protected $converter = false;
    
    /** @var string|null */
    protected $column = null;

    /** @var string|null */
    protected $related_class = null;
    
    /**
     * Property constructor.
     *
     * @param string $class
     * @param string $name
     * @param string $definition
     * @param mixed|null $default
     *
     * @throws Broken
     * @throws ReflectionException
     */
    public function __construct($class, $name, $definition, $default) {
        $this->class = $class;
        $this->name = $name;
        
        
        // parse definition and try to get type
        
        $parts = preg_split('`\s+`', trim($definition));
        $php_type = explode('|', array_shift($parts));
        
        $definition = [];
        foreach($parts as $part) {
            $part = array_map('trim', explode('=', $part, 2));
            
            if(Type::isValue($part[0])) $part = ['type', $part[0]];
            
            $definition[$part[0]] = (count($part) > 1) ? $part[1] : true;
        }
        
        while(!array_key_exists('type', $definition) && count($php_type)) {
            $type = array_shift($php_type);
            if(!Type::isValue($type)) continue;
            
            $definition['type'] = $type;
        }
        
        
        // check type
        
        if(!array_key_exists('type', $definition))
            throw new Broken("{$this->class}->{$this->name}", 'missing data type');
        
        if(!Type::isValue($definition['type']))
            throw new Broken("{$this->class}->{$this->name}", "unknown data type {$definition['type']}");
        
        $this->type = $definition['type'];
        
        
        // sanity check given size
        
        if(in_array($this->type, [Type::INT, Type::DECIMAL, Type::STRING]) && !array_key_exists('size', $definition))
            throw new Broken("{$this->class}->{$this->name}", $this->type.' type requires size');
        
        if($this->type === Type::INT && !IntSize::isValue($definition['size']))
            throw new Broken("{$this->class}->{$this->name}", 'unknown int size');
        
        if($this->type === Type::DECIMAL) {
            if(!preg_match('`^([1-9][0-9]*),([1-9][0-9]*)$`', $definition['size'], $match))
                throw new Broken("{$this->class}->{$this->name}", 'unknown int size');
            
            $m = (int)$match[1];
            $d = (int)$match[2];
    
            if($m < 1 || $m > 65)
                throw new Broken("{$this->class}->{$this->name}", 'precision is expected to be within 1..65');
            
            if($d < 0 || $d > 30)
                throw new Broken("{$this->class}->{$this->name}", 'number of decimal places is expected to be within 0..30');
            
            $definition['size'] = ['precision' => $m, 'decimal_places' => $d];
        }
        
        if($this->type === Type::STRING && !(!is_int($definition['size']) || ($definition['size'] <= 0) || ($definition['size'] >= 256)))
            throw new Broken("{$this->class}->{$this->name}", 'string size expects int within 1..255');
        
        $this->size = $definition['size'];
        
        
        // check other attributes
        
        if(array_key_exists('unsigned', $definition)) {
            if(!in_array($this->type, [Type::INT, Type::DECIMAL]))
                throw new Broken("{$this->class}->{$this->name}", 'cannot use unsigned on non-int');
            
            $this->unsigned = $definition['unsigned'];
        }
        
        $this->null = array_key_exists('null', $definition) && $definition['null'];
        
        $this->default = $default;
    
        if($this->null && !is_null($this->default))
            throw new Broken("{$this->class}->{$this->name}", 'nullable property cannot have non-null default');
    
        if(!$this->null && is_null($this->default))
            throw new Broken("{$this->class}->{$this->name}", 'non-nullable property cannot have null default');
    
        $this->primary = array_key_exists('primary', $definition) && (bool)$definition['primary'];
        
        if(array_key_exists('auto_increment', $definition) && $definition['auto_increment']) {
            if($this->type !== Type::INT || !$this->primary)
                throw new Broken("{$this->class}->{$this->name}", 'cannot use auto increment on non-primary or non-int property');
            
            $this->auto_increment = true;
        }
    
        if(!array_key_exists('column', $definition))
            $definition['column'] = $this->name;
        
        if(!preg_match('`^[a-z](?:[a-z0-9_]*[a-z0-9])?$`', $definition['column']))
            throw new Broken("{$this->class}->{$this->name}", 'malformed column name');
        
        $this->column = $definition['column'];
    
        if(array_key_exists('index', $definition))
            $this->addIndex($definition['index'], false);
    
        if(array_key_exists('unique', $definition))
            $this->addIndex($definition['unique'], false);

        if(array_key_exists('convert', $definition)) {
            $converter = '\\HereYouGo\\Converter\\'.$definition['convert'];
            if(Autoloader::exists($converter)) {
                if(!method_exists($converter, 'encode') || !method_exists($converter, 'decode'))
                    throw new Broken("{$this->class}->{$this->name}", 'data converter misses either encode or decode method(s)');
                
            } else {
                $method = 'get'.ucfirst($definition['convert']).'Converter';
                if(!method_exists($class, $method))
                    throw new Broken("{$this->class}->{$this->name}", "not standard converter and no $class::$method method");
                
                $converter = call_user_func($class.'::'.$method);
                
                if(!($converter instanceof Converter))
                    throw new Broken("{$this->class}->{$this->name}", "$class::$method did not return an instance of ".Converter::class);
            }
            
            $this->converter = is_object($converter) ? $converter : new class($converter) extends Converter {
                private $class = '';
                
                public function __construct($class) {
                    $this->class = $class;
                }
        
                public function encode($data): string {
                    return call_user_func($this->class.'::encode', $data);
                }
                
                public function decode($data) {
                    return call_user_func($this->class.'::decode', $data);
                }
            };
        }
    }

    /**
     * Add index
     *
     * @param string $name
     * @param bool $unique
     *
     * @throws Broken
     */
    private function addIndex($name, $unique = false) {
        $indexes = is_bool($name) ? [$this->column] : array_map('trim', explode(',', $name));
        foreach($indexes as $index) {
            if (!preg_match('`^[a-z](?:[a-z0-9_]*[a-z0-9])?$`', $index))
                throw new Broken("{$this->class}->{$this->name}", 'malformed index name');

            $this->indexes[$index] = $unique;
        }
    }
    
    /**
     * Clone and tweak property for use as relation key to another class
     *
     * @param Entity|string $other_class
     *
     * @return self
     *
     * @throws Broken
     */
    public function getRelationProperty($other_class) {
        $clone = clone $this; // IMPORTANT
        $clone->makeRelationProperty($other_class);

        return $clone;
    }

    /**
     * Transform property to relation property
     *
     * @param string $other_class
     */
    protected function makeRelationProperty($other_class) {
        $this->name = $this->class.'_'.$this->name;
        $this->column = $this->class.'_'.$this->column;

        $this->null = true; // relation is always optional, it is up to the class to check if mandatory
        $this->default = false;
        $this->primary = false;
        $this->auto_increment = false;
        $this->indexes = [];

        $this->related_class = $other_class;
    }

    /**
     * Cast database value to entity type
     *
     * @param string $value
     *
     * @return mixed
     *
     * @throws BadType
     * @throws Broken
     */
    public function castToEntity($value) {
        if($this->converter)
            $value = $this->converter->decode($value);

        if(is_null($value)) {
            if(!$this->null)
                throw new Broken($this, 'got null value but is not nullable');

            return $value;
        }

        switch($this->type) {
            case Type::BOOL:    return $value === '1';

            case Type::INT:     return (int)$value;
            case Type::DECIMAL: return (float)$value;
            case Type::FLOAT:   return (float)$value;
            case Type::DOUBLE:  return (double)$value;

            case Type::DATE:
            case Type::DATE_TIME:
            case Type::TIME:
                try {
                    $value = (int)(new DateTime($value, new DateTimeZone('UTC')))->format('U');
                } catch(Exception $e) {
                    throw new Broken($this, 'failed to parse to DateTime', $e);
                }

                if($this->type === Type::TIME)
                    $value %= 86400;

                return $value;

            case Type::STRING:
            case Type::TEXT:
            case Type::LONG_TEXT: return (string)$value;

            default: throw new BadType($this, 'One of '.Type::class);
        }
    }

    /**
     * Cast entity value to database type
     *
     * @param mixed $value
     * @return string
     *
     * @throws BadType
     * @throws Broken
     */
    public function castToDatabase($value) {
        if($this->converter) {
            return $this->converter->encode($value);

        } else {
            if(is_null($value)) {
                if(!$this->null)
                    throw new Broken($this, 'got null value but is not nullable');

                return $value;
            }

            switch($this->type) {
                case Type::BOOL:    return $value ? '1' : '0';

                case Type::INT:
                case Type::FLOAT:
                case Type::DOUBLE:  return "$value";

                case Type::DECIMAL:
                    $p = $this->size['precision'];
                    $d = $this->size['decimal_places'];
                    $i = $p - $d;
                    return sprintf("%$i.$d", $value);

                case Type::DATE:
                case Type::DATE_TIME:
                case Type::TIME:
                    try {
                        $format = [Type::DATE => 'Y-m-d', Type::DATE_TIME => 'Y-m-d H:i:s', Type::TIME => 'H:i:s'][$this->type];
                        return (new DateTime('@'.$value, new DateTimeZone('UTC')))->format($format);

                    } catch(Exception $e) {
                        throw new Broken($this, 'failed to format DateTime', $e);
                    }

                case Type::STRING:
                case Type::TEXT:
                case Type::LONG_TEXT: return "$value";

                default: throw new BadType($this, 'One of '.Type::class);
            }
        }
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
        if(in_array($name, [
            'class', 'name', 'type', 'size', 'null', 'default',
            'primary', 'auto_increment', 'indexes', 'uniques',
            'converter', 'column', 'related_class'
        ]))
            return $this->$name;
    
        throw new UnknownProperty($this, $name);
    }
}