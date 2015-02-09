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
    private static $instance;

    /**
     * Returns instance of PsqlConnector
     *
     * @param array  $config Array of database settings
     * @param string $logger A name of class which implements PSR-3 methods
     * @return PsqlConnector
     */
    public static function instance($config = null, $logger = null)
    {
        if (self::$instance === null && !empty($config)) {
            self::$instance = new PsqlConnector($config, $logger);
        }
        return self::$instance;
    }

}
