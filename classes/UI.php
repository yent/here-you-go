<?php


namespace HereYouGo;

use HereYouGo\HTTP\Request;

/**
 * Class UI
 *
 * @package HereYouGo
 */
class UI {
    /**
     * Get current url
     *
     * @return string
     */
    public static function currentUrl() {
        $base_url = Config::get('web.base_url');

        $path = Request::getPath();

        $args = $_GET;
        unset($args['path']);
        $args = implode('&', array_map(function($k, $v) {
            return $k.'='.urlencode($v);
        }, array_keys($args), array_values($args)));

        if(Config::get('web.nice_urls')) {
            return $base_url.$path.($args ? "?$args" : '');

        } else {
            return $base_url."index.php?path=$path".($args ? "&$args" : '');
        }
    }

    /**
     * Redirect browser somewhere else
     *
     * @param string $target
     * @param bool $external
     */
    public static function redirect($target, $external = false) {
        if(!$external) {
            if(!Config::get('web.nice_urls')) {
                $target = explode('?', $target);
                $target[0] = 'path='.$target[0];
                $target = 'index.php?'.implode('&', $target);
            }

            $target = Config::get('web.base_url').$target;
        }

        header("Location: $target");
        exit;
    }
}