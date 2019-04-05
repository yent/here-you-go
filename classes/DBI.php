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
 */
class DBI {
    /** @var self[] */
    private static $connexions = [];

    /** @var string */
    private $id = '';

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
     * @return mixed
     */
    public static function __callStatic($name, $arguments) {
        return call_user_func_array([self::local(), $name], $arguments);
    }

    /**
     * Exception constructor
     *
     * @param string|null $id
     */
    private function __construct($id = null) {
        $this->id = $id ? "remote.$id" : 'local';

        $conf = Config::get($id ? "remote_db.$id.*" : 'db.*');

        if(array_key_exists('dsn', $conf)) {
            $dsn = $conf['dsn'];

        } else {
            $dsn = [];
            foreach(['host', 'dbname', 'port', 'charset'] as $p)
                if(array_key_exists($p, $conf))
                    $dsn[] = $p.'='.$conf[$p];

            $dsn = $conf['type'].':'.implode(';', $dsn);
        }

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
     * @return mixed
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
}