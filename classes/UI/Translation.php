<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\UI;


use HereYouGo\Exception\BadType;
use HereYouGo\Logger;

/**
 * Class Translation
 *
 * @package HereYouGo\UI
 */
class Translation {
    /** @var string */
    private $id = '';
    
    /** @var string|array */
    private $contents = null;
    
    /** @var bool */
    private $not_found = false;
    
    /**
     * Translation constructor.
     *
     * @param string $id
     * @param string|array $contents
     * @param bool $not_found
     */
    public function __construct($id, $contents, $not_found = false) {
        $this->id = $id;

        if(is_string($contents)) {
            $this->contents = $contents;

        } else if(is_array($contents)) {
            $this->contents = array_map(function($sub) {
                return new self(null, $sub);
            }, $contents);

        } else {
            Logger::warn(self::class.' content is neither string nor array');
            $not_found = true;
        }

        $this->not_found = $not_found;
    }
    
    /**
     * Replace and get new translation
     *
     * @param array $vars
     *
     * @return self
     *
     * @throws BadType
     */
    public function replace(array $vars) {
        if($this->not_found)
            return $this;
        
        if(is_array($this->contents))
            return array_map(function(self $sub) use($vars) {
                return $sub->replace($vars);
            }, $this->contents);
        
        $out = $this->contents;
        foreach($vars as $k => $v)
            $out = str_replace('{'.$k.'}', $v, $out);
        
        return new self($this->id, $out);
    }
    
    /**
     * Replace shorthand
     *
     * @param array $vars
     *
     * @return self
     *
     * @throws BadType
     */
    public function r(array $vars) {
        return $this->replace($vars);
    }
    
    /**
     * Get contents
     *
     * @return string|array
     */
    public function out() {
        if(is_array($this->contents)) {
            if($this->not_found) {
                return array_combine(array_keys($this->contents), array_map(function($k) {
                    return "{{$this->id}[$k]}";
                }, array_keys($this->contents)));
            }
            
            return array_map(function (self $sub) {
                return $sub->out();
            }, $this->contents);
        }
    
        if($this->not_found)
            return "{{$this->id}}";
        
        return $this->contents;
    }
    
    /**
     * Stringifier
     *
     * @return string
     */
    public function __toString() {
        if($this->not_found)
            return "{{$this->id}}";
        
        $out = $this->out();
        if(is_array($out))
            return (count($out) === 1) ? reset($out) : "{{$this->id}[]}";
        
        return $out;
    }
}