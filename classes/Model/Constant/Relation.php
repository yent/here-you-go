<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\Model\Constant;


use HereYouGo\Enum;

/**
 * Class Relation
 *
 * @package HereYouGo\Model\Constant
 */
class Relation extends Enum {
    const ONE   = 'one';
    const MANY  = 'many';

    const ONE_TO_MANY = 'one_to_many';
    const MANY_TO_ONE = 'many_to_one';
    const MANY_TO_MANY = 'many_to_many';
}