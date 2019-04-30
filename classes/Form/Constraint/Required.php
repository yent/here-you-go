<?php


namespace HereYouGo\Form\Constraint;


use HereYouGo\Exception\BadType;
use HereYouGo\Form\Constraint;
use HereYouGo\UI\Translation;

/**
 * Class Required
 *
 * @package HereYouGo\Form\Constraint
 */
class Required extends Constraint {
    /**
     * Constraint constructor.
     *
     * @param Translation|string|false $hint
     * @param Translation|string $error
     */
    public function __construct($hint = '', $error = '') {
        parent::__construct(true, $hint, $error);
    }

    /**
     * Validate sent data
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function validate($data): bool {
        return isset($data);
    }
}