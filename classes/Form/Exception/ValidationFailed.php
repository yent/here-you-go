<?php


namespace HereYouGo\Form\Exception;


use HereYouGo\Exception\Detailed;
use HereYouGo\Form\Constraint;
use HereYouGo\Form\Field;

class ValidationFailed extends Detailed {
    public function __construct(Field $field, Constraint $constraint) {
        parent::__construct('validation_failed', [], [
            'field' => $field->name,
        ]);
    }
}