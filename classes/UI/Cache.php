<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\UI;


use HereYouGo\Config;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\UI\Exception\CouldNotCreateCache;
use HereYouGo\UI\Exception\CouldNotReadCache;
use HereYouGo\UI\Exception\CouldNotWriteCache;

/**
 * Class Cache
 *
 * @package HereYouGo\UI
 *
 * @property-read string $base
 */
class Cache {
    /** @var string */
    private $base = '';
    
    /** @var string */
    private $root = '';
    
    /**
     * Cache constructor.
     *
     * @param string $base
     *
     * @throws CouldNotCreateCache
     */
    public function __construct($base) {
        $this->base = Config::get('web.cache_path');
        if($this->base && substr($this->base, 1) !== '/')
            $this->base .= '/';

        $this->base .= $base;
        
        $this->root = HYG_ROOT.'view/cache/'.$base.'/';

        if(!is_dir($this->root) && !mkdir($this->root, 0755, true))
            throw new CouldNotCreateCache($this->root);
    }
    
    /**
     * Check if item is cached
     *
     * @param string $id
     *
     * @return bool
     */
    public function exists($id) {
        return file_exists($this->root.$id);
    }
    
    /**
     * Get minimum date for valid cache items
     *
     * @return int
     */
    public static function minDate() {
        $depends = [HYG_CONFIG.'protected.php', HYG_CONFIG.'overrides.json'];
        
        $mtimes = array_filter(array_map(function($file) {
            return file_exists($file) ? filemtime($file) : null;
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
     * @param string $id
     * @param string $contents
     *
     * @throws CouldNotWriteCache
     */
    public function set($id, $contents) {
        if(!file_put_contents($this->root.$id, $contents))
            throw new CouldNotWriteCache($this, $id);
    }
    
    /**
     * Get cache item
     *
     * @param string $id
     *
     * @return string|false
     *
     * @throws CouldNotReadCache
     */
    public function get($id) {
        if(!$this->isValid($id))
            return false;
        
        $contents = file_get_contents($this->root.$id);
        if($contents === false)
            throw new CouldNotReadCache($this, $id);
        
        return $contents;
    }

    /**
     * Get cache item URL
     *
     * @param string $id
     *
     * @return string|false
     */
    public function getUrl($id) {
        if(!$this->isValid($id))
            return false;

        return "cache/$this->base/$id";
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return string
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if($name === 'base')
            return $this->base;

        throw new UnknownProperty($this, $name);
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