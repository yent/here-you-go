<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Exception;

/**
 * Class FileNotFound
 *
 * @package HereYouGo\Exception
 */
class FileNotFound extends Detailed {
    /**
     * FileNotFound constructor.
     *
     * @param string $file
     */
    public function __construct($file) {
        parent::__construct('file_not_found', ['file' => $file], [], 404);
    }
}