<?php


namespace HereYouGo\Form\Field;

use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Form\Fragment;

/**
 * Class Hidden
 *
 * @package HereYouGo\Form\Field
 */
class Hidden extends Fragment {
    /** @var string */
    protected $name = '';

    /** @var string */
    protected $value = '';

    /**
     * Hidden constructor.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value) {
        parent::__construct('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(in_array($name, ['name', 'value']))
            return $this->$name;

        return parent::__get($name);
    }

    /**
     * Setter
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws UnknownProperty
     * @throws BadType
     */
    public function __set($name, $value) {
        if(in_array($name, ['name', 'value'])) {
            $this->$name = (string)$value;

        } else {
            parent::__set($name, $value);
        }
    }
}