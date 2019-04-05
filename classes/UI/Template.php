<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\UI;

use HereYouGo\Config;
use HereYouGo\Event;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\UI\Exception\TemplateNotFound;

/**
 * Class Template
 *
 * @package HereYouGo\UI
 */
class Template {
    /** @var string */
    private $id = '';
    
    /** @var string */
    private $path = '';
    
    /**
     * Resolve template path
     *
     * @param string $id
     *
     * @return self
     *
     * @throws TemplateNotFound
     */
    public static function resolve($id) {
        $path = (new Event('', $id))->trigger(function($id) {
            $locations = ['templates/default/'];
    
            $skin = Config::get('skin');
            if($skin)
                array_unshift($locations, "templates/$skin/");
    
            array_unshift($locations, 'config/templates/');
    
            foreach($locations as $location) {
                $file = $location.$id.'.php';
                if(file_exists(HYG_ROOT.$file))
                    return $file;
            }
    
            return null;
        });
        
        if(!$path || !file_exists(HYG_ROOT.$path))
            throw new TemplateNotFound($id);
        
        return new self($id, $path);
    }
    
    /**
     * Template constructor.
     *
     * @param string $id
     * @param string $path
     *
     * @throws TemplateNotFound
     */
    private function __construct($id, $path) {
        if(!$id || !$path || !file_exists(HYG_ROOT.$path))
            throw new TemplateNotFound($id);
        
        $this->id = $id;
        $this->path = $path;
    }
    
    /**
     * Process template contents
     *
     * @param array $__vars
     *
     * @return string
     */
    public function process(array $__vars) {
        foreach($__vars as $__k => $__v) {
            if($__k === 'this' || substr($__k, 0, 2) === '__') continue;
            $$__k = $__v;
        }
        
        ob_start();
        
        include $this->path;
        
        $res = ob_get_clean();
        
        $res = preg_replace_callback('`\{tr(?:anslate)?:([^\}]+)\}`', function($match) {
            return Locale::translate($match[1]);
        }, $res);
        
        // TODO conditions and stuff
        
        return $res;
    }
    
    /**
     * Display template
     *
     * @param array $__vars
     */
    public function display(array $__vars) {
        echo $this->process($__vars);
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
        if(in_array($name, ['id', 'path']))
            return $this->$name;
    
        throw new UnknownProperty($this, $name);
    }
}