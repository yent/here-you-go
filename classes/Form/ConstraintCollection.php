<?php


namespace HereYouGo\Form;


use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\UI\Translation;

/**
 * Class ConstraintCollection
 *
 * @package HereYouGo\Form
 *
 * @property Constraint[] $collection
 * @property Translation|string|false $hint
 * @property Translation|string $error
 */
class ConstraintCollection extends Fragment {
    /** @var Constraint[] */
    protected $collection = [];

    /** @var string|false */
    protected $hint = '';

    /** @var string */
    protected $error = '';

    /**
     * ConstraintSet constructor.
     *
     * @param Constraint[] $constraints
     * @param Translation|string|false $hint
     * @param Translation|string $error
     */
    public function __construct(array $constraints = [], $hint = '', $error = '') {
        foreach($constraints as $constraint)
            $this->addConstraint($constraint);

        $this->hint = ($hint === false) ? $hint : (string)$hint;

        $this->error = (string)$error;

        parent::__construct('div', ['class' => 'constraints']);
    }

    /**
     * Check if set includes given constraint
     *
     * @param string $type
     *
     * @return bool
     */
    public function has($type) {
        return array_key_exists($type, $this->collection);
    }

    /**
     * Add new constraint
     *
     * @param Constraint $constraint
     */
    public function addConstraint(Constraint $constraint) {
        $constraint->parent = $this;

        $this->collection[$constraint->type] = $constraint;
    }

    /**
     * Validate sent data
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function validate($data) {
        foreach($this->collection as $constraint)
            if(!$constraint->validate($data))
                return false;

        return true;
    }

    /**
     * Get HTML
     *
     * @return string
     */
    public function getHtml() {
        $text = '';
        if($this->hint !== false) {
            $text = $this->hint;

            if(!$text)
                $text = implode('', array_map(function(Constraint $constraint) {
                    return $constraint->getHtml();
                }, $this->collection));
        }

        if($this->error)
            $this->attributes['data-error-text'] = $this->error;

        return $this->wrap($text);
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
        if(in_array($name, ['collection', 'hint', 'error']))
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
        if($name === 'collection') {
            $this->collection = [];
            foreach((array)$value as $constraint)
                $this->addConstraint($constraint);

        } else if($name === 'hint') {
            $this->hint = ($value === false) ? $value : (string)$value;

        } else if($name === 'error') {
            $this->error = (string)$value;

        } else {
            parent::__set($name, $value);
        }
    }
}