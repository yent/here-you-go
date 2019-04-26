<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;

/**
 * Class Sanitizer
 *
 * @package HereYouGo
 */
class Sanitizer {
    /**
     * Sanitize user data
     *
     * @param mixed $input
     *
     * @return mixed
     */
    public static function sanitizeInput($input) {
        if(is_array($input)) {
            foreach($input as $k => $v) {
                $nk = preg_replace('`[^a-z0-9\._-]`i', '', $k);
                if($k !== $nk)
                    unset($input[$k]);

                $input[$nk] = self::sanitizeInput($v);
            }

            return $input;
        }

        if(is_numeric($input) || is_bool($input) || is_null($input))
            return $input;

        if(is_string($input)) {
            // Convert to UTF-8
            $input = iconv(mb_detect_encoding($input, mb_detect_order(), true), 'UTF-8', $input);

            // Render potential tags useless by putting a space immediatelly after < which does not already have one
            $input = html_entity_decode($input, ENT_QUOTES, 'UTF-8');
            $input = preg_replace('`<([^\s])`', '< $1', $input);

            return $input;
        }

        return null;
    }
    
    /**
     * Sanitize data with exceptions
     *
     * @param mixed $data
     * @param array $doNotSanitize
     * @param string $path
     *
     * @return array|mixed
     */
    public static function sanitizeData($data, array $doNotSanitize = [], $path = '') {
        if(is_object($data)) {
            foreach (get_object_vars($data) as $k => $v) {
                $v = self::sanitizeData($v, $doNotSanitize, $path ? $path.'/'.$k : $k);
                $data->$k = $v;
            }
            return $data;
            
        } else if (is_array($data)) {
            return array_map(function($d, $k) use($doNotSanitize, $path) {
                return self::sanitizeData($d, $doNotSanitize, $path ? $path.'/'.$k : $k);
            }, $data, array_keys($data));
            
        } else {
            $sanitize = true;
            foreach($doNotSanitize as $regexp)
                if(preg_match($regexp, $path))
                    $sanitize = false;
            
            if($sanitize)
                $data = self::sanitizeInput($data);
            
            return $data;
        }
    }

    /**
     * Sanitize output
     *
     * @param string $output
     *
     * @return string
     */
    public static function sanitizeOutput($output) {
        return htmlentities($output, ENT_QUOTES, 'UTF-8');
    }
}