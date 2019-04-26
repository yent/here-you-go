<?php


namespace HereYouGo\UI;


use HereYouGo\Config;
use HereYouGo\Converter\JSON;

/**
 * Class Resource
 *
 * @package HereYouGo\UI
 */
class Resource {
    const CACHE_ID = ['styles' => 'styles.css', 'scripts' => 'scripts.js'];

    /**
     * Get resources of a certain type in the right order, packed if not debugging client side
     *
     * @param string $type
     *
     * @return string[]
     *
     * @throws JSON\Exception\UnableToDecode
     */
    public static function gather($type) {
        $cache = new Cache('resources');
        $id = array_key_exists($type, self::CACHE_ID) ? self::CACHE_ID[$type] : $type;

        $debug = Config::get('client_debug');

        if($cache->isValid($id) && !$debug)
            return ["/view/cache/resources/$id"];

        $locations = array_filter(['default', Config::get('skin')]);

        $resources = [];
        foreach($locations as $location) {
            $rel = "/view/resources/skin/$location/$type/";
            $dir = HYG_ROOT.$rel;
            if(!is_dir($dir)) continue;

            if(!file_exists("$dir/order.json"))
                throw new TODO(); // TODO

            $order = JSON::decode(file_get_contents("$dir/order.json"));

            foreach($order as $item)
                $resources[] = $rel.$item;
        }

        if($debug)
            return $resources;

        $packer = 'pack'.ucfirst($type);
        if(method_exists(self::class, $packer)) {
            /**
             * @uses Resource::packScripts()
             * @uses Resource::packStyles()
             */
            $packed = self::$packer($resources);

        } else {
            $packed = implode("\n", array_map(function($file) {
                return file_get_contents(HYG_ROOT.$file);
            }, $resources));
        }

        $cache->set($id, $packed);

        return ["/view/cache/resources/$id"];
    }

    /**
     * Pack C-style files
     *
     * @param string[] $files
     *
     * @return string
     */
    private static function packCStyle($files) {
        return implode("\n", array_map(function($file) {
            return "/*** $file ***/\n".file_get_contents(HYG_ROOT.$file);
        }, $files));
    }

    /**
     * Pack css files
     *
     * @param string[] $files
     *
     * @return string
     */
    private static function packStyles($files) {
        return self::packCStyle($files);
    }

    /**
     * Pack javascript files
     *
     * @param string[] $files
     *
     * @return string
     */
    private static function packScripts($files) {
        return self::packCStyle($files);
    }

    /**
     * Find file
     *
     * @param string $name
     *
     * @return string
     */
    public static function file($name) {
        foreach(array_filter([Config::get('skin'), 'default']) as $location) {
            $rel = "/view/resources/skin/$location/$name";
            if(file_exists(HYG_ROOT.$rel))
                return $rel;
        }

        throw new TODO(); // TODO
    }
}