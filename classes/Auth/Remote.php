<?php


namespace HereYouGo\Auth;


/**
 * Class Remote
 *
 * @package HereYouGo\Backend
 */
class Remote extends Backend {
    /**
     * Remote backend constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = []) {
        // TODO
    }

    /**
     * Check if any user logged-in
     *
     * @return bool
     */
    public function hasIdentity(): bool {
        // TODO: Implement hasIdentity() method.
        return false;
    }

    /**
     * Get current user attributes
     *
     * @return array
     */
    public function getAttributes(): array {
        // TODO: Implement getAttributes() method.
        return [];
    }
}