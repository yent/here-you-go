<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\Model;


abstract class Converter {
    /**
     * Encode data
     *
     * @param mixed $data
     *
     * @return string
     */
    abstract public function encode($data): string;
    
    /**
     * Decode data
     *
     * @param string $data
     *
     * @return mixed
     */
    abstract public function decode($data);
}