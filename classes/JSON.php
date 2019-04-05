<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;


use HereYouGo\JSON\Exception\UnableToEncode;
use HereYouGo\JSON\Exception\UnableToDecode;

class JSON {
    /**
     * Encode to JSON
     *
     * @param mixed $data
     *
     * @return string
     *
     * @throws UnableToEncode
     */
    public static function encode($data) {
        $json = json_encode($data);

        if(json_last_error())
            throw new UnableToEncode(json_last_error_msg());

        return $json;
    }

    /**
     * Decode from JSON
     *
     * @param string $json
     * @param bool $assoc
     *
     * @return mixed
     *
     * @throws UnableToDecode
     */
    public static function decode($json, $assoc = false) {
        $data = json_decode($json, $assoc);

        if(json_last_error())
            throw new UnableToDecode(json_last_error_msg());

        return $data;
    }
}