<?php


namespace HereYouGo\Form;


use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Sanitizer;
use HereYouGo\UI\Translation;

/**
 * Class Field
 *
 * @package HereYouGo\Form
 *
 * @property string $name
 * @property string $label
 * @property ConstraintCollection $constraints
 * @property string $hint
 * @property bool $disabled
 * @property string $value
 */
abstract class Field extends Fragment implements DataHolder {
    /** @var string */
    protected $name = '';

    /** @var string */
    protected $label = '';

    /** @var ConstraintCollection|null */
    protected $constraints = null;

    /** @var string|Translation */
    protected $hint = '';

    /** @var bool */
    protected $disabled = false;

    /** @var mixed */
    protected $value = '';

    /**
     * Control constructor.
     *
     * @param string $name
     * @param Translation|string $label
     * @param ConstraintCollection $constraints
     * @param mixed $value
     */
    public function __construct($name, $label = '', ConstraintCollection $constraints = null, $value = '') {
        $this->name = (string)$name;
        $this->label = (string)$label;
        $this->constraints = $constraints;
        $this->value = $value;

        parent::__construct('div');
    }

    /**
     * Check if field has given constraint
     *
     * @param string $type
     *
     * @return bool
     */
    public function hasConstraint($type) {
        return $this->constraints->has($type);
    }

    /**
     * Get HTML
     *
     * @return string
     */
    public function getHtml(): string {
        return $this->wrap(function() {
            $html = '';

            if($this->label)
                $html .= '<label for="'.$this->getPath().'">'.Sanitizer::sanitizeOutput($this->label).'</label>';

            if($this->constraints)
                $html .= $this->constraints->getHtml();

            $html .= $this->getInteractivePart();

            return $html;
        });
    }

    /**
     * Get interactive part
     *
     * @return string
     */
    abstract public function getInteractivePart(): string;

    /**
     * Get constraints errors part
     *
     * @return string
     */
    public function getErrorsPart() {
        if(!$this->constraints)
            return '';

        $feedback = new Fragment('div', ['class' => 'invalid-feedback']);

        if($this->constraints->error)
            return $feedback->wrap($this->constraints->error);

        $errors = [];
        foreach($this->constraints->collection as $constraint)
            $errors[] = (new Fragment('div', ['class' => 'constraint-error', 'data-constraint' => $constraint->type]))->wrap($constraint->error);

        return $feedback->wrap(implode('', $errors));
    }

    /**
     * Get holder path
     *
     * @return string
     */
    public function getPath(): string {
        return ($this->parent ? "{$this->parent->getPath()}." : '').$this->name;
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
        if(in_array($name, ['name', 'label', 'constraints', 'hint', 'disabled', 'value']))
            return $this->$name;

        if($name === 'path')
            return $this->getPath();

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
        if(in_array($name, ['name', 'label', 'hint'])) {
            $this->$name = (string)$value;

        } else if($name === 'constraints') {
            $this->constraints->collection = $value;

        } else if($name === 'disabled') {
            $this->disabled = (bool)$value;

        } else if($name === 'value') {
            $this->value = $value;

        } else {
            parent::__set($name, $value);
        }
    }
}