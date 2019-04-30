<?php


namespace HereYouGo\Form\Field;

use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Form\ConstraintCollection;
use HereYouGo\Form\Fragment;
use HereYouGo\UI\Translation;

/**
 * Class Text
 *
 * @package HereYouGo\Form\Field
 *
 * @property string $type
 * @property string $prepend
 * @property string $placeholder
 */
class Text extends Scalar {
    /** @var string */
    protected $prepend = '';

    /** @var string */
    protected $placeholder = '';

    /** @var Fragment */
    protected $input = null;

    /**
     * Text constructor.
     *
     * @param string $name
     * @param Translation|string $label
     * @param ConstraintCollection|null $constraints
     * @param string $value
     */
    public function __construct($name, $label = '', ConstraintCollection $constraints = null, $value = '') {
        parent::__construct($name, $label, $constraints, $value);

        $this->input = new Fragment('input');
    }

    /**
     * Get interactive part
     *
     * @return string
     */
    public function getInteractivePart(): string {
        if(!$this->prepend)
            return $this->getInput().$this->getErrorsPart();

        return (new Fragment('div', ['class' => 'input-group']))->wrap(
            (new Fragment('div', ['class' => 'input-group-prepend']))->wrap($this->prepend).$this->getInput()
        ).$this->getErrorsPart();
    }

    /**
     * Get input part
     *
     * @return string
     */
    public function getInput(): string {
        $attributes = [
            'type' => $this->type,
            'class' => 'form-control',
            'id' => $this->getPath(),
            'name' => $this->name,
            'value' => $this->value
        ];

        if($this->placeholder)
            $attributes['placeholder'] = $this->placeholder;

        $this->input->addAttributes($attributes, false);

        return $this->input->wrap();
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
        if(in_array($name, ['prepend', 'placeholder']))
            return $this->$name;

        if($name === 'type')
            return strtolower(substr(static::class, strrpos(static::class, '\\')));

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
        if(in_array($name, ['prepend', 'placeholder'])) {
            $this->$name = (string)$value;

        } else {
            parent::__set($name, $value);
        }
    }
}