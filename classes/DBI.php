<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo;

use HereYouGo\DBI\Exception\CallFailed;
use HereYouGo\DBI\Exception\UnknownMethod;

use HereYouGo\DBI\Statement;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Manages database connexion(s)
 *
 * @package HereYouGo
 *
 * @method static bool beginTransaction()
 * @method static bool commit()
 * @method static string errorCode()
 * @method static array errorInfo()
 * @method static int exec(string $statement)
 * @method static mixed getAttribute(int $attribute)
 * @method static array getAvailableDrivers()
 * @method static bool inTransaction()
 * @method static string lastInsertId(string $name = null)
 * @method static Statement prepare(string $statement, array $driver_options = [])
 * @method static Statement query(string $statement)
 * @method static string quote(string $string, int $parameter_type = PDO::PARAM_STR)
 * @method static bool rollBack()
 * @method static bool setAttribute(int $attribute, mixed $value)
 */
class DBI {
    /** @var self[] */
    private static $connexions = [];

    /** @var string */
    private $id = '';

    /** @var string */
    private $db_name = '';

    /** @var PDO */
    private $pdo = null;

    /**
     * Get connexion interface
     *
     * @param string|null $id
     *
     * @return self
     */
    private static function get($id = null) {
        if(!array_key_exists((string)$id, self::$connexions))
            self::$connexions[(string)$id] = new self($id);

        return self::$connexions[(string)$id];
    }

    /**
     * Get local connexion
     *
     * @return self
     */
    public static function local() {
        return self::get();
    }

    /**
     * Get remote connection
     *
     * @param string $id
     *
     * @return self
     */
    public static function remote($id) {
        return self::get($id);
    }

    /**
     * Call method on local database connexion
     *
     * @param string $name
     * @param array $arguments
     *
     * @return Statement|mixed
     */
    public static function __callStatic($name, $arguments) {
        return call_user_func_array([self::local(), $name], $arguments);
    }
    
    /**
     * Get DSN for given config
     *
     * @param array $conf
     *
     * @return string
     */
    public static function getDsn($conf) {
        if(array_key_exists('dsn', $conf))
            return $conf['dsn'];
        
        $dsn = [];
        foreach(['host', 'dbname', 'port', 'charset'] as $p)
            if(array_key_exists($p, $conf))
                $dsn[] = $p.'='.$conf[$p];
    
        return $conf['type'].':'.implode(';', $dsn);
    }

    /**
     * Exception constructor
     *
     * @param string|null $id
     */
    private function __construct($id = null) {
        $this->id = $id ? "remote.$id" : 'local';

        $conf = Config::get($id ? "remote_db.$id.*" : 'db.*');
        
        $dsn = self::getDsn($conf);

        if(preg_match('`(?:^|;)dbanme=(.+)(?:;.+|$)`', $dsn, $match))
            $this->db_name = $match[1];

        $user = array_key_exists('user', $conf) ? $conf['user'] : '';
        $passwd = array_key_exists('passwd', $conf) ? $conf['passwd'] : '';
        $options = array_key_exists('options', $conf) ? $conf['options'] : [];

        $this->pdo = new PDO($dsn, $user, $passwd, $options);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
    }

    /**
     * Call method on database connexion
     *
     * @param string $name
     * @param array $arguments
     *
     * @return Statement|mixed
     *
     * @throws UnknownMethod
     * @throws CallFailed
     */
    public function __call($name, $arguments) {
        try {
            if(!method_exists($this->pdo, $name))
                throw new UnknownMethod($name, $this);

            $res = call_user_func_array([$this->pdo, $name], $arguments);

            if($res instanceof PDOStatement)
                $res = new Statement($this, $res);

            return $res;

        } catch(PDOException $e) {
            throw new CallFailed($name, $arguments, $this, $e);
        }
    }

    /**
     * Get connexion id
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get database name
     *
     * @return string
     */
    public function getDbName() {
        return $this->db_name;
    }
}