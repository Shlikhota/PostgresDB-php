<?php namespace PostgresDB;

interface SelectInterface {

    /** Assemble query builder object to the query string */
    public function __toString();

    /**
     * Adds distinct to query
     *
     * @return SelectInterface
     */
    public function distinct();

    /**
     * From which table will select entries
     *
     * @param array|string $table Table name
     * @return SelectInterface
     */
    public function from($table);

    /**
     * Adds condition to query
     *
     * @param string $condition Condition string with placeholders
     * @param mixed $value Single value or array of values for placeholders in condition
     * @return SelectInterface
     */
    public function where($condition, $value = false);

    /**
     * Adds having condition
     *
     * @param string $condition Condition string with placeholders
     * @param mixed $value Single value or array of values for placeholders in condition
     * @return SelectInterface
     */
    public function having($condition, $value = false);

    /**
     * Order entries by field and direction
     *
     * @param array|string $field
     * @param string $direction
     * @return SelectInterface
     */
    public function order($field, $direction = null);

    /**
     * Group entries by fields
     *
     * @param array|string $field Few or a single field name
     * @return SelectInterface
     */
    public function group($field);

    /**
     * Limiting the number of selected entries with offset
     *
     * @param int $count Limit count
     * @param int $offset Offset count
     * @return SelectInterface
     */
    public function limit($count, $offset = 0);

    /**
     * Join addition entries from other table with intersection
     *
     * @param string $table Table name
     * @param string $condition Join condition
     * @return SelectInterface
     */
    public function innerJoin($table, $condition);

    /**
     * Join addition entries from other table on the left
     *
     * @param string $table Table name
     * @param string $condition Join condition
     * @return SelectInterface
     */
    public function leftJoin($table, $condition);

    /**
     * Join addition entries from other table on the right
     *
     * @param string $table Table name
     * @param string $condition Join condition
     * @return SelectInterface
     */
    public function rightJoin($table, $condition);

    /**
     * Adds additional columns to the result of query
     *
     * @param string|array $columns Array of the column names or string column name
     * @return SelectInterface
     */
    public function addColumns($columns);

    /**
     * Returns an array of values in the first column
     *
     * @return array
     */
    public function fetchColumn();

    /**
     * Returns an array of objects of entries
     *
     * @return object[]
     */
    public function fetchAll();

    /**
     * Returns an array of arrays of entries
     *
     * @return array
     */
    public function fetchArray();

    /**
     * Returns an array of objects of entries, where key of array is first column
     *
     * @return object[]
     */
    public function fetchAssoc();

    /**
     * Returns the first entry in the first column
     *
     * @return mixed
     */
    public function fetchOne();

    /**
     * Returns an array, where the key is the value of the first column and value the second
     */
    public function fetchPair();

    /**
     * Returns an object of single entry
     *
     * @return object|null
     */
    public function fetchRow();

    /**
     * Sets the lock on the entries for update
     *
     * @return SelectInterface
     */
    public function forUpdate();

}
