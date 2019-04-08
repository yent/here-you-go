<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\Model\Constant;


use HereYouGo\Enum;

class Type extends Enum {
    const BOOL      = 'bool';
    const INT       = 'int';
    const DECIMAL   = 'decimal';
    const FLOAT     = 'float';
    const DOUBLE    = 'double';
    
    const DATE      = 'date';
    const DATE_TIME = 'date_time';
    const TIME      = 'time';
    
    const STRING    = 'string';
    const TEXT      = 'text';
    const LONG_TEXT = 'long_text';
}