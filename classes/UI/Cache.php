<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\UI;


use HereYouGo\Logger;

class Cache {
    /** @var string */
    private $base = '';
    
    /** @var string */
    private $root = '';
    
    /**
     * Cache constructor.
     *
     * @param string $base
     */
    public function __construct($base) {
        $this->base = $base;
        
        $this->root = HYG_ROOT.'view/cache/'.$base.'/';
    }
    
    /**
     * Check if item is cached
     *
     * @param string $id
     *
     * @return bool
     */
    public function exists($id) {
        return is_dir($this->root) && file_exists($this->root.$id);
    }
    
    /**
     * Get minimum date for valid cache items
     *
     * @return int
     */
    public static function minDate() {
        $depends = ['config/protected.php', 'config/overrides.json'];
        
        $mtimes = array_filter(array_map(function($file) {
            return file_exists(HYG_ROOT.$file) ? filemtime(HYG_ROOT.$file) : null;
        }, $depends));
        
        return max($mtimes);
    }
    
    /**
     * Check if cache exists and its mtime is valid
     *
     * @param string $id
     *
     * @return bool
     */
    public function isValid($id) {
        return self::exists($id) && (filemtime($this->root.$id) >= self::minDate());
    }
    
    /**
     * Set cache item
     *
     * @param $id
     * @param $contents
     */
    public function set($id, $contents) {
        file_put_contents($this->root.$id, $contents) || Logger::error("could not write $id to $this");
    }
    
    /**
     * Get cache item
     *
     * @param string $id
     *
     * @return false|string
     */
    public function get($id) {
        if(!$this->isValid($id))
            return false;
        
        $contents = file_get_contents($this->root.$id);
        if($contents === false)
            Logger::error("could not read $id from $this");
        
        return $contents;
    }
    
    /**
     * Get cached if it exists, build it and cache it otherwise
     *
     * @param string $id
     * @param callable $builder
     *
     * @return false|string
     */
    public function getOrSet($id, callable $builder) {
        $contents = $this->get($id);
        if($contents === false) {
            $contents = $builder();
            
            $this->set($id, $contents);
        }
        
        return $contents;
    }
    
    /**
     * Stringifier
     *
     * @return string
     */
    public function __toString() {
        return self::class.'#'.$this->base;
    }
}