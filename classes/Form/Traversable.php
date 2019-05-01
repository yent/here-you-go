<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Form;


abstract class Traversable extends Fragment implements DataHolder {
    /**
     * Find node
     *
     * @param string $path
     *
     * @return DataHolder|null
     */
    abstract public function find($path);
}