<?php namespace PostgresDB;

interface ConnectorInterface {

    /**
     * Initialize connector instance
     */
    public function __construct(array $config);

    /**
     * Switches logging of queries
     *
     * @param boolean $value
     * @return $this
     */
    public function isLog($value);

    /**
     * Returns history log array
     *
     * @return array
     */
    public function getQueriesLog();

    /**
     * Creates SELECT query builder
     *
     * @return SelectInterface
     */
    public function select();

    /**
     * Do something in transaction
     *
     * @param Closure $callback Callback function doing in transaction
     * @return void
     */
    public function transaction(\Closure $callback);

    /**
     * Begin transaction
     *
     * @return bool
     */
    public function begin();

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit();

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback();

    /**
     * Performs an any statement queries
     *
     * @param string $query    Query string
     * @param array  $bindings Binding parameters
     * @return mixed
     */
    public function query($query, $bindings = []);

    /**
     * Inserts the entries into the tables
     *
     * @param string $table     Table name
     * @param array  $fields    Fields name
     * @param array  $data      Array of array data
     * @param bool   $returning Return the inserted entries? If not, return boolean
     * @return integer|bool
     */
    public function insert($table, $fields, $data, $returning = true);

    /**
     * Updates entries
     *
     * @param string       $table Table name
     * @param array        $data  Array data, where key is field name and value is updated data
     * @param string|array $where Condition, which entries will update
     * @return integer
     */
    public function update($table, $data, $where = null);

    /**
     * Removes entries
     *
     * @param string       $table     Table name
     * @param string|array $where     Condition, which entries will delete
     * @param bool         $returning Return deleted entries
     * @return bool|object[]
     */
    public function delete($table, $where = null, $returning = false);

    /**
     * Returns an array of values in the first column
     *
     * @param DbSelectInterface|string $query Query string or select builder
     * @param array $bindings
     * @return array
     */
    public function fetchColumn($query, $bindings);

    /**
     * Returns an array of objects of entries
     *
     * @param DbSelectInterface|string $query Query string or select builder
     * @param array $bindings
     * @return object[]
     */
    public function fetchAll($query, $bindings);

    /**
     * Returns an array of arrays of entries
     *
     * @param DbSelectInterface|string $query Query string or select builder
     * @param array $bindings
     * @return array
     */
    public function fetchArray($query, $bindings);

    /**
     * Returns an array of objects of entries, where key of array is first column
     *
     * @param DbSelectInterface|string $query Query string or select builder
     * @param array $bindings
     * @return object[]
     */
    public function fetchAssoc($query, $bindings);

    /**
     * Returns the first entry in the first column
     *
     * @param SelectInterface|string $query Query string or select builder
     * @param array $bindings
     * @return mixed
     */
    public function fetchOne($query, $bindings);

    /**
     * Returns an array, where the key is the value of the first column and value the second
     *
     * @param SelectInterface|string $query Query string or select builder
     * @param array $bindings
     * @return array
     */
    public function fetchPair($query, $bindings);

    /**
     * Returns an object of single entry
     *
     * @param DbSelectInterface|string $query Query string or select builder
     * @param array $bindings
     * @return object|null
     */
    public function fetchRow($query, $bindings);

    /**
     * Quotes value
     *
     * @param mixed $value
     * @return string
     */
    public function quote($value);

    /**
     * Prepares the data values in the query string
     *
     * @param string $query Query string
     * @param mixed $bindings Array of data
     * @return void
     */
    public function prepareQuery(&$query, $bindings);

}
