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

use PDO;
use PDOException;
use PDOStatement;

/**
 * Class Statement
 *
 * @package HereYouGo\DBI
 *
 * @method bool bindColumn(mixed $column, mixed &$param, int $type = null, int $maxlen = null, mixed $driverdata = null)
 * @method bool bindParam(mixed $parameter, mixed &$variable, int $data_type = PDO::PARAM_STR, int $length = null, mixed $driver_options = null)
 * @method bool bindValue(mixed $parameter, mixed $value, int $data_type = PDO::PARAM_STR)
 * @method bool closeCursor()
 * @method int columnCount()
 * @method void debugDumpParams()
 * @method string errorCode()
 * @method array errorInfo()
 * @method bool execute(array $input_parameters = [])
 * @method mixed fetch(int $fetch_style = null, int $cursor_orientation = PDO::FETCH_ORI_NEXT, int $cursor_offset = 0)
 * @method array fetchAll(int $fetch_style = null, mixed $fetch_argument = null, array $ctor_args = [])
 * @method mixed fetchColumn(int $column_number = 0)
 * @method mixed fetchObject(string $class_name = 'stdClass', array $ctor_args = [])
 * @method mixed getAttribute(int $attribute)
 * @method array getColumnMeta(int $column)
 * @method bool nextRowset()
 * @method int rowCount()
 * @method bool setAttribute(int $attribute, mixed $value)
 * @method bool setFetchMode(int $mode, mixed $colno_or_classname_or_object = null, array $ctorargs = [])
 */
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