<?php


namespace HereYouGo\Form\Field;


use HereYouGo\Form\Field;

/**
 * Class Scalar
 *
 * @package HereYouGo\Form\Field
 */
abstract class Scalar extends Field {
    /**
     * Get interactive part
     *
     * @return string
     */
    public function getInteractivePart(): string {
        return $this->getInput().$this->getErrorsPart();
    }

    /**
     * Get input part
     *
     * @return string
     */
    abstract public function getInput(): string;

    /**
     * Validate own data
     *
     * @param mixed $data
     */
    public function validate($data) {
        if($this->constraints)
            $this->constraints->validate($data);
    }
}