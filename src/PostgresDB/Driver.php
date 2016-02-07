<?php
/**
 * PostgreSQL database abstract layer over PDO extension.
 *
 * @package  PostgresDB-php
 * @author   Eugene Shlikhota <shlikhota@gmail.com>
 * @see      http://github.com/Shlikhota/PostgresDB-php
 * @license  X11 {@link http://opensource.org/licenses/mit-license.php}
 */

namespace PostgresDB;

class Driver {

    /** @var PsqlConnector */
    private static $connector;

    /**
     * Returns instance of PsqlConnector
     *
     * @param array  $config Array of database settings
     * @param string $logger A name of class which implements PSR-3 methods
     * @return PsqlConnector
     */
    public function __construct($config, $logger = null)
    {
        if (self::$connector === null && !empty($config)) {
            self::$connector = new PsqlConnector($config, $logger);
        }
    }

    public function __call($method, $args)
    {
        return call_user_func_array([self::$connector, $method], $args);
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::$connector, $method], $args);
    }

}
