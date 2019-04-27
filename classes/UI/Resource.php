<?php


namespace HereYouGo\UI;


use HereYouGo\Config;
use HereYouGo\Converter\JSON;
use HereYouGo\Event;
use HereYouGo\Exception\FileNotFound;

/**
 * Class Resource
 *
 * @package HereYouGo\UI
 */
class Resource {
    const LIB = [
        'styles' => [
            'bootstrap-4.3.1-dist/css/bootstrap.css',
            'fontawesome-free-5.8.1-web/css/all.css',
        ],
        'scripts' => [
            'jquery-3.4.0.min.js',
            'bootstrap-4.3.1-dist/js/bootstrap.bundle.js',
            'tooltip.min.js',
        ],
    ];

    const CACHE_ID = ['styles' => 'styles.css', 'scripts' => 'scripts.js'];

    const PACKERS = ['styles' => self::class.'::packStyle', 'scripts' => self::class.'::packScript'];

    /**
     * Get resources of a certain type in the right order, packed if not debugging client side
     *
     * @param string $type
     *
     * @return string[]
     */
    public static function gather($type) {
        $cache = new Cache('resources');
        $id = array_key_exists($type, self::CACHE_ID) ? self::CACHE_ID[$type] : $type;

        $debug = Config::get('debug');

        if($cache->isValid($id) && !$debug)
            return ["cache/resources/$id"];

        $files = (new Event("libraries_$type"))->trigger(function() use($type) {
            return array_map(function($file) {
                return "resources/lib/$file";
            }, self::LIB[$type]);
        });

        $files = array_merge($files, (new Event("libraries_$type"))->trigger(function() use($type) {
            $resources = [];

            $locations = array_filter(['default', Config::get('skin')]);
            foreach($locations as $location) {
                $rel = "resources/skin/$location/$type/";
                $dir = HYG_ROOT.'/view/'.$rel;
                if(!is_dir($dir)) continue;

                if(!file_exists("$dir/order.json"))
                    throw new FileNotFound("$dir/order.json");

                $order = JSON::decode(file_get_contents("$dir/order.json"));

                foreach($order as $item)
                    $resources[] = $rel.$item;
            }

            return $resources;
        }));

        if($debug)
            return $files;

        if(array_key_exists($type, self::PACKERS)) {
            /**
             * @uses Resource::packScript()
             * @uses Resource::packStyle()
             */
            $packer = self::PACKERS[$type];

        } else {
            $packer = function($file) {
                return file_get_contents(HYG_ROOT.'/view/'.$file);
            };
        }

        $packed = implode("\n", array_map($packer, $files));

        $cache->set($id, $packed);

        return ["cache/resources/$id"];
    }

    /**
     * Pack C-style file
     *
     * @param string $file
     *
     * @return string
     */
    private static function packCStyle($file) {
        return "/*** $file ***/\n".file_get_contents(HYG_ROOT.'/view/'.$file);
    }

    /**
     * Pack css file
     *
     * @param string $file
     *
     * @return string
     */
    private static function packStyle($file) {
        $style = self::packCStyle($file);

        $rel = '../../'.dirname($file);

        $style = preg_replace_callback('`url\((\'|"|)([^\)]+)\1\)`U', function($match) use($rel) {
            $url = $match[2];

            if(preg_match('`^(https?:)//`', $url))
                return "url('$url')";

            if($url{0} === '/')
                return "url('$url')";

            return "url('$rel/$url')";
        }, $style);

        return $style;
    }

    /**
     * Pack javascript file
     *
     * @param string $file
     *
     * @return string
     */
    private static function packScript($file) {
        return self::packCStyle($file);
    }

    /**
     * Find file
     *
     * @param string $name
     *
     * @return string
     *
     * @throws FileNotFound
     */
    public static function file($name) {
        foreach(array_filter([Config::get('skin'), 'default']) as $location) {
            $rel = "resources/skin/$location/$name";
            if(file_exists(HYG_ROOT.'/view/'.$rel))
                return $rel;
        }

        throw new FileNotFound($name);
    }
}