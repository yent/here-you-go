<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\REST\Response;


use HereYouGo\Exception\BadType;

/**
 * Class Raw
 *
 * @package HereYouGo\REST\Response
 */
class Raw extends Base {
    /**
     * Get returned Mime type
     *
     * @return mixed
     */
    public static function getMimeType() {
        return 'application/octet-stream';
    }
    
    /**
     * Render exception
     *
     * @param \Exception $e
     *
     * @throws BadType
     */
    public static function renderException(\Exception $e) {
        Plain::renderException($e);
    }
    
    /**
     * Get file extension
     *
     * @return string
     */
    public static function getExtension() {
        return '';
    }
}