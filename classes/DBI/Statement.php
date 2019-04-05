<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\DBI;

use HereYouGo\DBI;

use HereYouGo\DBI\Exception\CallFailed;
use HereYouGo\DBI\Exception\UnknownMethod;

use PDOException;
use PDOStatement;

class Statement {
    /** @var DBI */
    private $dbi = null;

    /** @var PDOStatement */
    private $statement = null;

    public function __construct(DBI $dbi, PDOStatement $statement) {
        $this->dbi = $dbi;
        $this->statement = $statement;
    }

    /**
     * Call method on database connexion
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws UnknownMethod
     * @throws CallFailed
     */
    public function __call($name, $arguments) {
        try {
            if(!method_exists($this->statement, $name))
                throw new UnknownMethod($name, $this);

            return call_user_func_array([$this->statement, $name], $arguments);

        } catch(PDOException $e) {
            throw new CallFailed($name, $arguments, $this, $e);
        }
    }
}