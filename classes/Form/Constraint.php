<?php


namespace HereYouGo\Form;

use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\UI\Locale;
use HereYouGo\UI\Translation;

/**
 * Class Constraint
 *
 * @package HereYouGo\Form
 *
 * @property-read string $type
 * @property Translation|string|false $hint
 * @property Translation|string $error
 */
abstract class Constraint extends Fragment {
    /** @var string|false */
    protected $hint = '';

    /** @var string */
    protected $error = '';

    /** @var mixed */
    protected $value = null;

    /**
     * Constraint constructor.
     *
     * @param mixed $value
     * @param Translation|string|false $hint
     * @param Translation|string $error
     */
    public function __construct($value, $hint = '', $error = '') {
        $this->value = $value;

        $this->hint = ($hint === false) ? $hint : (string)$hint;

        $this->error = (string)($error ? $error : Locale::translate("form.constraint.$this->type.error"));

        parent::__construct('div', ['class' => 'constraint', 'data-constraint' => $this->type, 'data-value' => $value]);
    }

    /**
     * Validate sent data
     *
     * @param mixed $data
     *
     * @return bool
     */
    abstract public function validate($data): bool;

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
                $text = Locale::translate("form.constraint.$this->type.hint");
        }

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
        if($name === 'type')
            return strtolower(substr(static::class, strrpos(static::class, '\\')));

        if(in_array($name, ['hint', 'error']))
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
        if($name === 'hint') {
            $this->hint = ($value === false) ? $value : (string)$value;

        } else if($name === 'error') {
            $this->error = (string)$value;

        } else {
            parent::__set($name, $value);
        }
    }
}