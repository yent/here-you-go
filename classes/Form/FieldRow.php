<?php


namespace HereYouGo\Form;

use HereYouGo\Exception\BadType;

/**
 * Class FieldRow
 *
 * @package HereYouGo\Form
 */
class FieldRow extends FieldSet {
    /**
     * FieldRow constructor.
     * @param array $fields
     *
     * @throws BadType
     */
    public function __construct(array $fields) {
        parent::__construct($fields);

        $this->attributes['class'] = 'form-row';
    }
}