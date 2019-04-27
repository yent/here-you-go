<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\UI;


use HereYouGo\Config;
use HereYouGo\Converter\JSON;
use HereYouGo\Converter\JSON\Exception\UnableToDecode;
use HereYouGo\Converter\JSON\Exception\UnableToEncode;
use HereYouGo\Exception\BadType;

/**
 * Class Locale
 *
 * @package HereYouGo\UI
 */
class Locale {
    /** @var string[] */
    private static $available = [];
    
    /** @var string[] */
    private static $codes = [];
    
    /** @var string[] */
    private static $dictionary = [];
    
    /**
     * Get available locales
     *
     * @return string[]
     */
    public static function getAvailable() {
        if(!self::$available) {
            $locations = ['locales/', 'config/locales/'];
            $available = [];
            
            foreach($locations as $location) {
                if(!is_dir(HYG_ROOT.$location)) continue;
                
                foreach(scandir(HYG_ROOT.$location) as $id) {
                    if($id{0} === '.') continue;
                    if(!is_dir(HYG_ROOT.$location.'/'.$id)) continue;
                    if(!file_exists(HYG_ROOT.$location.'/'.$id.'/name')) continue;
                    $available[$id] = trim(file_get_contents(HYG_ROOT.$location.'/'.$id.'/name'));
                }
            }
            
            $conf = Config::get('available_locales');
            if($conf)
                $available = array_intersect_key($available, array_fill_keys((array)$conf, true));
            
            self::$available = $available;
        }
        
        return self::$available;
    }
    
    /**
     * Clean code and check if available
     *
     * @param string $code
     *
     * @return string|null
     */
    public static function cleanCode($code) {
        $available = self::getAvailable();
    
        $code = str_replace('_', '-', strtolower($code));
        $code = explode('-', $code)[0];
        
        return array_key_exists($code, $available) ? $code : null;
    }
    
    /**
     * Add code to stack
     *
     * @param string $code
     *
     * @return string|null
     */
    private static function addCode($code) {
        $code = self::cleanCode($code);
    
        if ($code && !in_array($code, self::$codes))
            self::$codes[] = $code;
    
        return $code;
    }
    
    /**
     * Get code stack
     *
     * @return string[]
     */
    public static function getCodes() {
        if(!self::$codes) {
            $available = self::getAvailable();
    
            if (count($available) > 1) {
                try {
                    if (Config::get('lang.use_url')) {
                        if (array_key_exists('lang', $_GET) && preg_match('`^[a-z]+(-.+)?$`', $_GET['lang'])) {
                            $code = self::addCode($_GET['lang']);
                            if ($code) {
                                if (isset($_SESSION)) $_SESSION['locale'] = $code;
                                /** TODO
                                 * if(Config::get('lang.save_user_pref') && Auth::isAuthenticated()) {
                                 * Auth::user()->locale = $code;
                                 * Auth::user()->save();
                                 * }
                                 */
                            }
                        }
                
                        if (isset($_SESSION) && array_key_exists('locale', $_SESSION))
                            self::addCode($_SESSION['locale']);
                    }
            
                    /** TODO
                     * if(Config::get('lang.use_user_pref') && Auth::isAuthenticated()) {
                     * $add_to_stack(Auth::user()->locale);
                     * }
                     */
                } catch (\Exception $e) {
                }
        
                if (Config::get('lang.use_browser') && array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
                    $codes = [];
                    foreach (array_map('trim', explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])) as $part) {
                        if (preg_match('`^([^;]+)(?:;.+)*(?:;q=([0-9]+\.[0-9]+))?(?:;.+)?`', $part, $match)) {
                            $code = self::cleanCode($match[1]);
                            if ($code)
                                $codes[$code] = ($match[2] !== '') ? (float)$match[2] : 1;
                        }
                    }
            
                    asort($codes, SORT_NUMERIC);
            
                    foreach ($codes as $code => $weight)
                        self::addCode($code);
                }
            }
    
            self::addCode(Config::get('lang.default'));
    
            self::addCode('en');
        }
        
        return self::$codes;
    }

    /**
     * Compile dictionary
     */
    private static function compileDictionary() {
        if(!self::$dictionary) {
            $codes = self::getCodes();
            $cache = new Cache('locales');
            
            $id = implode('-', $codes);
            if($cache->isValid($id) && !Config::get('debug')) {
                try {
                    self::$dictionary = JSON::decode($cache->get($id));
                } catch (UnableToDecode $e) {
                    self::$dictionary = null;
                }
            }
            
            if(!self::$dictionary) {
                self::$dictionary = [];
                while($codes) {
                    $code = array_pop($codes);
                    
                    $locations = ["locales/$code/", "config/locales/$code/"];
                    foreach($locations as $location) {
                        if(!is_dir(HYG_ROOT.$location)) continue;

                        if(file_exists(HYG_ROOT.$location.'translations.json')) {
                            try {
                                self::$dictionary = array_merge_recursive(
                                    self::$dictionary,
                                    JSON::decode(file_get_contents(HYG_ROOT.$location.'translations.json'))
                                );

                            } catch (UnableToDecode $e) {
                            }
                        }
                        
                        foreach(scandir(HYG_ROOT.$location) as $item) {
                            if($item{0} === '.') continue;
                            if(!is_file(HYG_ROOT.$location.$item)) continue;
                            if(!preg_match('`^(.+)\.(html?|txt)$`', $item, $match)) continue;
                            
                            self::$dictionary[$match[1]] = trim(file_get_contents(HYG_ROOT.$location.$item));
                        }
                    }
                }
                
                try {
                    $cache->set($id, JSON::encode(self::$dictionary));
                } catch(UnableToEncode $e) {}
            }
        }
    }
    
    /**
     * Get compiled dictionary
     *
     * @return string[]
     */
    public static function getDictionary() {
        self::compileDictionary();
        
        return self::$dictionary;
    }

    /**
     * Get raw translation from id
     *
     * @param string $id
     *
     * @return string|null
     */
    private static function getTranslation($id) {
        self::compileDictionary();

        $id = explode('.', $id);
        $dict = &self::$dictionary;

        while($id) {
            $p = array_shift($id);
            if(!array_key_exists($p, $dict))
                return null;

            $dict = &$dict[$p];
        }

        return $dict;
    }
    
    /**
     * Get translation for id
     *
     * @param string $id
     *
     * @return Translation
     *
     * @throws BadType
     */
    public static function translate($id) {
        $translation = self::getTranslation($id);

        return new Translation($id, (string)$translation, is_null($translation));
    }
    
    /**
     * Translation shorthand
     *
     * @param string $id
     *
     * @return Translation
     *
     * @throws BadType
     */
    public static function tr($id) {
        return self::translate($id);
    }

    /**
     * Check wether id has translation
     *
     * @param string $id
     *
     * @return bool
     */
    public static function isTranslatable($id) {
        return !is_null(self::getTranslation($id));
    }
}