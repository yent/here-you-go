<?php


namespace HereYouGo\Auth\Exception;


use HereYouGo\Exception\Detailed;

/**
 * Class UnknownBackend
 *
 * @package HereYouGo\Backend
 */
class UnknownBackend extends Detailed {
    /**
     * UnknownSP constructor.
     *
     * @param string $type
     */
    public function __construct($type) {
        parent::__construct('unknown_backend_type', ['type' => $type]);
    }
}