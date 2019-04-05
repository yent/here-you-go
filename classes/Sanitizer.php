<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;


class Sanitizer {
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
}