<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\UI\Exception;


use HereYouGo\Exception\Detailed;

class TemplateNotFound extends Detailed {
    public function __construct($id) {
        parent::__construct('template_not_found', [], ['id' => $id], 404);
    }
}