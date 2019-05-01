<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

use HereYouGo\Auth\Backend\Embedded\Form;

/** @var string $target */

echo (new Form($target))->getHtml();
