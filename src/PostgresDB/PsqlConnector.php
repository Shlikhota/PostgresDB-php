<?php namespace PostgresDB;

use PDO,
    PDOException;

class PsqlConnector implements ConnectorInterface {

    /** @var PDO[] database connection handlers */
    private $connections = [];
    /** @var string|null last connected server */
    private $currentServer;
    private $config = [];
    private $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_PERSISTENT => true
    ];
    private $logger;
    private $queriesLog = [];
    private $isLog = false;
    private $isDebug = false;
    private $transactionLevel = 0;

    const MSG_ERROR_NO_CONNECTION = 'No connection';
    const MSG_ERROR_WRONG_QUERY_PARAMS = 'Wrong query params';
    const MSG_ERROR_DATABASE_DOESNT_EXIST = 'Attempting to change a non-existent database server';
    const MSG_ERROR_SWITCH_SERVER_IN_TRANSACTION = 'Changing the server is not available in the transaction.';
    const MSG_ERROR_NON_UTF8_SEQUENCE = 'Non utf-8 sequance detected';
    const MSG_ERROR_NOT_ENOUGH_BINDING_PARAMS = 'Replace parameters not enough for query';

    /**
     * @inheritdoc
     */
    public function __construct(array $config, $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Set database server name
     *
     * @param string $name Server name
     * @return void
     */
    public function setDatabaseServer($name)
    {
        if ($this->getTransactionsLevel()) {
            $this->exception(self::MSG_ERROR_SWITCH_SERVER_IN_TRANSACTION, compact('name'));
        }
        if (empty($this->config[$this->currentServer])) {
            $this->exception(self::MSG_ERROR_DATABASE_DOESNT_EXIST, compact('name'));
        }
        $this->currentServer = $name;
    }

    /**
     * Connect to database
     *
     * @return PDO
     * @throws PDOException
     */
    private function connect()
    {
        $server = ($this->currentServer !== null ? $this->currentServer : 'default');
        try {
            if ( ! empty($this->connections[$server]) && $this->connections[$server] instanceof PDO) {
                $this->currentServer = $server;
                return $this->connections[$server];
            }
            $config = isset($this->config[$server]) ? $this->config[$server] : $this->config;
            $this->connections[$server] = new PDO(
                'pgsql:host=' . $config['host']
                    . (isset($config['port']) ? ';port=' . $config['port'] : '')
                    . ';dbname=' . $config['database'],
                $config['username'],
                $config['password'],
                !empty($config['options'])
                    ? array_diff_key($this->options, $config['options']) + $config['options']
                    : $this->options
            );
            $this->connections[$server]->prepare("set names '" . $config['charset'] . "'")->execute();
        } catch (PDOException $exception) {
            throw $exception;
        }
        $this->currentServer = $server;
        return $this->connections[$server];
    }

    /**
     * Execute query
     *
     * @param string $function Caller, which execute the query
     * @param string $query Query string
     * @return mixed
     * @throws \Exception
     */
    private function execute($function, $query, $bindings = [])
    {
        try {
            if ( ! $dbh = $this->connect()) {
                $this->exception(
                    self::MSG_ERROR_NO_CONNECTION,
                    compact('function', 'query', 'bindings')
                );
            }
            $this->prepareQuery($query, $bindings);
            $timeStart = microtime(true);
            $statement = $dbh->prepare($query);
            $executeResult = $statement->execute();
            $timeEnd = (microtime(true) - $timeStart) * 1000;
            $this->addQueryLog($query, $timeEnd);
            if ($statement === false) {
                $error = $dbh->errorInfo();
                $this->exception(
                    $error[2] . ' ' . $error[1],
                    compact('function', 'query', 'bindings')
                );
            }
            switch ($function):
                case 'fetchAll':
                    $result = $statement->fetchAll(PDO::FETCH_OBJ);
                    break;
                case 'fetchArray':
                    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                    break;
                case 'fetchOne':
                    $result = $statement->fetchColumn();
                    break;
                case 'fetchPair':
                    $result = $statement->fetchAll(PDO::FETCH_KEY_PAIR);
                    break;
                case 'fetchColumn':
                    $result = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
                    break;
                case 'fetchAssoc':
                    $temp = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $result = [];
                    while ($row = array_shift($temp)) {
                        $result[reset($row)] = (object) $row;
                    }
                    break;
                case 'fetchRow':
                    $result = $statement->fetch();
                    break;
                case 'insert':
                    $result = (
                        (mb_stripos($query, 'returning') !== false)
                        ? $statement->fetchAll(PDO::FETCH_COLUMN, 0)
                        : $statement->rowCount()
                    );
                    break;
                case 'update':
                case 'delete':
                    $result = (
                        (mb_stripos($query, 'returning') !== false)
                        ? $statement->fetchAll(PDO::FETCH_ASSOC)
                        : $statement->rowCount()
                    );
                    break;
                case 'query':
                    $result = (bool) $executeResult;
                    break;
                default:
                    $result = $statement->fetchAll();
                    break;
            endswitch;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
        return $result;
    }

    /**
     * Save info about query to log array
     *
     * @param string $query Query string
     * @param string $time  Query execution time in second
     * @return void
     */
    private function addQueryLog($query, $time)
    {
        if ( ! $this->isLog) {
            return false;
        }
        $method = $this->getCalledMethod();
        array_push($this->queriesLog, [
            'server' => $this->currentServer,
            'query' => $query,
            'method' => $method,
            'time' => $time
        ]);
    }

    /**
     * @inheritdoc
     */
    public function isLog($value = true)
    {
        $this->isLog = (bool) $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getQueriesLog()
    {
        return $this->queriesLog;
    }

    /**
     * @inheritdoc
     */
    public function select()
    {
        return new PsqlSelect($this, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function transaction(\Closure $callback)
    {
        $this->begin();
        try {
            $result = $callback($this);
            $this->commit();
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function begin()
    {
        if ($this->getTransactionsLevel() === 0) {
            $this->connect()->beginTransaction();
        }
        $this->transactionLevel++;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function commit()
    {
        if ($this->getTransactionsLevel() > 0) {
            $this->transactionLevel--;
            if ($this->getTransactionsLevel() === 0) {
                $this->connect()->commit();
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function rollback()
    {
        if ($this->getTransactionsLevel() > 0) {
            $this->transactionLevel = 0;
            $this->connect()->rollBack();
        }
    }

    /**
     * Returns the level of an open transaction
     *
     * @return int
     */
    private function getTransactionsLevel()
    {
        return $this->transactionLevel;
    }

    /**
     * @inheritdoc
     */
    public function query($query, $bindings = [])
    {
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function insert($table, $fields, $data, $returning = false)
    {
        if ( ! is_array($data) || empty($data) || ! is_array($data[0])) {
            $this->exception(
                self::MSG_ERROR_WRONG_QUERY_PARAMS,
                compact('table', 'fields', 'data', 'returning')
            );
        }
        $placeholders = '(?' . str_repeat(',?', count($fields)-1) . ')';
        $placeholders = $placeholders . str_repeat(',' . $placeholders, count($data)-1);
        $query = 'INSERT INTO ' . $table
            . ' ("'. implode('", "', $fields) . '") '
            . ' VALUES' . $placeholders
            . ($returning ? ' RETURNING id' : '');
        $dataBindings = array_reduce($data, function($carry, $item) {
            return array_merge(($carry ? : []), $item);
        });
        $result = $this->execute(__FUNCTION__, $query, $dataBindings);
        return is_array($result) && count($result) == 1 ? $result[0] : $result;
    }

    /**
     * @inheritdoc
     */
    public function update($table, $data, $where = null)
    {
        if ( ! is_array($data) || empty($data) || ! is_array($data[0])) {
            $this->exception(
                self::MSG_ERROR_WRONG_QUERY_PARAMS,
                compact('table', 'data', 'where')
            );
        }
        $query = 'UPDATE ' . $table . ' SET "' . implode('" = ?, "', array_keys($data)) . '" = ?';
        $bindings = array_values($data);
        if ( ! empty($where)) {
            if (is_array($where)) {
                $query .= ' WHERE ' . $this->prepareCondition($where, $bindings);
            } else if (is_string($where)) {
                $query .= ' WHERE ' . $where;
            }
        }
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function delete($table, $where = null, $returning = false)
    {
        $query = 'DELETE FROM ' . $table;
        $bindings = [];
        if (!empty($where)) {
            if (is_array($where)) {
                $query .= ' WHERE ' . $this->prepareCondition($where, $bindings);
            } else if (is_string($where)) {
                $query .= ' WHERE ' . $where;
            }
        }
        if ($returning !== false) {
            $query .= ' RETURNING ';
            if (is_array($returning)) {
                $query .= implode(', ', $returning);
            } else if (is_string($returning)) {
                $query .= $returning;
            } else {
                $query .= '*';
            }
        }
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function fetchColumn($query, $bindings = [])
    {
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function fetchAll($query, $bindings = [])
    {
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function fetchArray($query, $bindings = [])
    {
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function fetchAssoc($query, $bindings = [])
    {
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function fetchOne($query, $bindings = [])
    {
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function fetchPair($query, $bindings = [])
    {
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function fetchRow($query, $bindings = [])
    {
        return $this->execute(__FUNCTION__, $query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function quote($value)
    {
        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            $this->exception(
                self::MSG_ERROR_NON_UTF8_SEQUENCE,
                compact('value')
            );
        }
        return $this->connect()->quote($value);
    }

    /**
     * Throw exception with logging
     *
     * @throws \Exception
     */
    private function exception($message, $context)
    {
        if ($this->logger &&
            method_exists($this->logger, 'critical')
        ) {
            // Should be implement PSR-3 logger interface
            call_user_func(
                $this->logger . '::critical',
                $message,
                $context
            );
        }
        throw new \Exception($message);
    }

    /**
     * @inheritdoc
     */
    public function prepareQuery(&$query, $bindings = [])
    {
        $query = (string) $query;
        $bindings = (array) $bindings;
        foreach ($bindings as &$data) {
            if (is_array($data)) {
                foreach ($data as &$subval) {
                    if (!(is_float($subval) || is_int($subval))){
                        $subval = $this->quote($subval);
                    }
                }
                $data = implode(',', $data);
            } else if (is_string($data)) {
                $data = $this->quote($data);
            }
        }

        $result = '';
        $quotes = 0;
        $chars = preg_split('//u', $query, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $k => $ch) {
            if ($ch == '?') {
                if ($quotes % 2 === 0) {
                    if (empty($bindings)) {
                        $this->exception(
                            self::MSG_ERROR_NOT_ENOUGH_BINDING_PARAMS,
                            compact('query', 'bindings')
                        );
                    }
                    $replaceData = array_shift($bindings);
                    if ($replaceData === null) {
                        $replaceData = 'NULL';
                    }
                    $ch = $replaceData;
                }
            } else if ($ch == '\'' && $k > 0 && $chars[$k-1] != '\\') {
                $quotes++;
            }
            $result .= $ch;
        }
        $query = $result;
    }

    /**
     * @inheritdoc
     */
    public function prepareCondition($whereArray, &$bindings)
    {
        $where = [];
        foreach ($whereArray as $expression => $value) {
            if (is_int($expression)) {
                $where[] = $value;
                continue;
            }
            $expression = trim(mb_strtolower($expression));
            $where[] = $expression . (mb_strpos($expression, '?') === false ? ' = ?' : '');
            if (is_array($value) && mb_strpos($expression, ' between ') !== false) {
                $bindings[] = $value[0];
                $bindings[] = $value[1];
                continue;
            }
            $bindings[] = $value;
        }
        return '(' . implode(') AND (', $where) . ')';
    }

    /**
     * Returns the place where was call
     *
     * @return string
     */
    private function getCalledMethod()
    {
        if ( ! $this->isDebug) {
            return '';
        }
        $placeIndex = 0;
        $backtrace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true);
        foreach ($backtrace as $index => $call) {
            if (isset($call['class']) && strpos($call['class'], 'PostgresDB\\') === 0) {
                $placeIndex = $index + 1;
                break;
            }
        }
        if ( ! $placeIndex) {
            return '';
        }
        return $backtrace[$placeIndex]['class']
            . $backtrace[$placeIndex]['type']
            . $backtrace[$placeIndex]['function']
            . '[' . $backtrace[$placeIndex-1]['line'] . ']';
    }

    /**
     * Is it debug mode?
     *
     * @return $this
     */
    public function setDebug($value = true)
    {
        $this->isDebug = $value;
        return $this;
    }

}
