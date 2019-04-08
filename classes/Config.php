<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;

use HereYouGo\Config\Deferred;

/**
 * Manages access to configuration settings
 *
 * @package HereYouGo
 */
class Config {
    const DEFAULTS = [];

    const PROTECTED = HYG_ROOT.'config/protected.php';
    const OVERRIDES = HYG_ROOT.'config/overrides.json';

    /** @var array */
    private static $parameters = [];

    /**
     * Load / reload config from files
     *
     * @param bool $force_reload
     */
    public static function load($force_reload = false) {
        if(self::$parameters && !$force_reload)
            return;

        self::$parameters = [];

        // Load defaults
        self::$parameters = self::DEFAULTS;

        // Mix in protected
        self::$parameters = (function() {
            $conf = [];
            include self::PROTECTED;
            return $conf;
        })();

        $overrides = [];
        if(file_exists(self::OVERRIDES))
            $overrides = json_decode(file_get_contents(self::OVERRIDES), true);

        if(!is_array($overrides))
            $overrides = [];

        $allowed_overrides = array_key_exists('allowed_overrides', self::$parameters) ? self::$parameters['allowed_overrides'] : null;
        if($allowed_overrides && is_array($allowed_overrides))
            foreach($allowed_overrides as $key)
                if(array_key_exists($key, $overrides))
                    self::$parameters[$key] = $overrides[$key];
    }

    /**
     * recursively evaluate config value
     *
     * @param mixed $conf
     *
     * @return mixed
     */
    private static function evaluate(&$conf) {
        if($conf instanceof Deferred)
            $conf = $conf->evaluate();

        if(is_array($conf))
            foreach($conf as &$sub)
                $sub = self::evaluate($sub);

        return $conf;
    }

    /**
     * Get config param
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function get($key) {
        self::load();

        $key = explode('.', $key);
        $conf = &self::$parameters;
        while(count($key)) {
            $bit = array_shift($key);

            if($bit === '*') break;

            if(!array_key_exists($bit, $conf))
                return null;

            $conf = &$conf[$bit];
        }

        return self::evaluate($conf);
    }
}