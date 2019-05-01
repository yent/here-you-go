<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Auth\Backend;


use HereYouGo\Enum;

class NewUserPolicy extends Enum {
    const CREATE    = 'create';
    const REGISTER  = 'register';
    const REJECT    = 'reject';
}