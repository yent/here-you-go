<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Auth\Backend;


use HereYouGo\Auth\Backend;
use HereYouGo\Form\FieldSet;

abstract class Embedded extends Backend {
    /**
     * Get login form fragment
     *
     * @return FieldSet
     */
    abstract public function getFields(): FieldSet;
}