<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Form\Field;


use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Form\DataHolder;
use HereYouGo\Form\Field;
use HereYouGo\Form\FieldSet;
use HereYouGo\Form\Traversable;

/**
 * Class Alternative
 *
 * @package HereYouGo\Form\Field
 *
 * @property-read string $selector
 * @property-read FieldSet[] $sets
 */
class Alternative extends Traversable {
    protected $selector = '';

    /** @var FieldSet[] */
    protected $sets = [];

    /**
     * Alternative constructor.
     *
     * @param $selector
     * @param FieldSet[] $sets
     *
     * @throws BadType
     */
    public function __construct($selector, array $sets) {
        foreach($sets as $key => $set) {
            if($set instanceof FieldSet) {
                $this->sets[$key] = $set;

            } else {
                throw new BadType('set', FieldSet::class);
            }
        }

        $this->selector = $selector;

        parent::__construct('div', ['class' => 'alternative']);
    }

    /**
     * Get HTML
     *
     * @return string
     */
    public function getHtml(): string {
        $this->attributes['data-selector'] = $this->selector;

        return $this->wrap(implode('', array_map(function(FieldSet $set) {
            return $set->getHtml();
        }, $this->sets)));
    }

    /**
     * Get holder path
     *
     * @return string
     */
    public function getPath(): string {
        return $this->parent ? $this->parent->getPath() : '';
    }

    /**
     * Find node
     *
     * @param string $path
     *
     * @return DataHolder|null
     */
    public function find($path) {
        foreach($this->sets as $set) {
            $found = $set->find($path);
            if($found)
                return $found;
        }

        return null;
    }

    /**
     * Validate own data
     *
     * @param mixed $data
     */
    public function validate($data) {
        $selector = $this->form->find($this->selector);
        // TODO access selector ???
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
        if(in_array($name, ['selector', 'sets']))
            return $this->$name;

        return parent::__get($name);
    }
}