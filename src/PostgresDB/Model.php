<?php namespace PostgresDB;

use PostgresDB\Driver as DB;

abstract class Model {

    /** @var $table The table name of model */
    protected static $table = '';

    /**
     * Returns the table name of the model
     *
     * @return string
     */
    public static function table()
    {
        return static::$table;
    }

    /**
     * Returns the identifiers created entries
     *
     * @param array $data Array where key is column name and value is data
     * @param bool $returning True - return ids, false - the number of created entries
     * @return int|array
     */
    public static function create($data, $returning = true)
    {
        return DB::instance()->insert(static::table(), array_keys($data), array(array_values($data)), $returning);
    }

    /**
     * Returns the number of entries on the condition
     *
     * @param array|string $where Conditions
     * @return int
     */
    public static function count($where = [])
    {
        $query = DB::instance()->select('COUNT(0)')->from(static::table());
        if ($where) {
            if (is_array($where)) {
                $query->where(DB::instance()->prepareWhere($where));
            } else if (is_string($where) ) {
                $query->where($where);
            }
        }
        return (int) $query->fetchOne($server);
    }

    /**
     * Returns entry by identifier
     *
     * @param integer $id identifier
     * @return object|bool
     */
    public static function find($id)
    {
        if (!$id = (int) $id) {
            return false;
        }
        $query = DB::instance()->select()->from(static::table())->where('"id" = ?', $id);
        $result = $query->fetchRow();
        return $result ? : false;
    }

    /**
     * Updates entries by conditions. Returns updated entries
     *
     * @param array $data Array of data where key is column name and value is data
     * @param array|string $where Condition array should be content
     * @return integer
     */
    public static function update($data, $where)
    {
        return DB::instance()->update(static::table(), $data, $where);
    }

    /**
     * Removes entries by conditions
     *
     * @param array|string $where Which entries must be remove
     * @param bool $returning Return removed entries?
     * @return bool|array
     */
    protected static function delete($where, $returning = false)
    {
        return DB::instance()->delete(static::table(), $where, $returning);
    }

}
