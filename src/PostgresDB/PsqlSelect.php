<?php namespace PostgresDB;

class PsqlSelect implements SelectInterface {

    private $db;
    /** @var array params of query builder */
    private $string = [];
    /** @var array replace parameters */
    private $bindings = [
        'select' => [],
        'from' => [],
        'where' => [],
        'having' => []
    ];

    function __construct()
    {
        $columns = func_get_args();
        $this->db = $columns[0];
        if ( ! empty($columns[1])) {
            if ( ! empty($columns[1][1]) && is_array($columns[1][1])) {
                $this->bindings['select'] = $this->bindings['select'] + $columns[1][1];
                $this->addColumns($columns[1][0]);
            } else {
                $this->addColumns(is_array($columns[1][0]) ? $columns[1][0] : $columns[1]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $result = 'SELECT '
            . (!empty($this->string['distinct']) ? $this->string['distinct'] : '')
            . (!empty($this->string['columns']) && is_array($this->string['columns'])
                ? implode(', ', $this->string['columns']) : '*')
            . (isset($this->string['from']) ? ' FROM ' . implode(', ', $this->string['from']) : '');

        if (!empty($this->string['join']) && is_array($this->string['join'])) {
            $result .= implode("\n ", $this->string['join']);
        }

        if (!empty($this->string['where']) && is_array($this->string['where'])) {
            $result .= ' WHERE (' . implode(') AND (', $this->string['where']) . ')';
        }

        if (!empty($this->string['group']) && is_string($this->string['group'])) {
            $result .= ' ' . $this->string['group'];
        }

        if (!empty($this->string['order']) && is_array($this->string['order'])) {
            $result .= ' ORDER BY ' . implode(', ', $this->string['order']);
        }

        if (!empty($this->string['having']) && is_array($this->string['having'])) {
            $result .= ' HAVING (' . implode(') AND (', $this->string['having']) . ')';
        }

        if (!empty($this->string['having']) && is_string($this->string['limit'])) {
            $result .= ' ' . $this->string['limit'];
        }

        if (!empty($this->string['for_update'])) {
            $result .= ' FOR UPDATE';
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function distinct()
    {
        $this->string['distinct'] = 'DISTINCT ';
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function from($table, $bindings = [])
    {
        if (is_string($table)) {
            $this->string['from'][] = $table;
            if ( ! empty($bindings) && is_array($bindings)) {
                $this->bindings['from'] = $this->bindings['from'] + $bindings;
            }
        } else if ( ! empty($table) && is_array($table)) {
            foreach ($table as $name => $alias) {
                $this->string['from'][] = '"' . $name . '" AS ' . $alias;
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function where($condition, $bindings = [])
    {
        $this->addCondition('where', $condition, $bindings);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function having($condition, $bindings = [])
    {
        $this->addCondition('having', $condition, $bindings);
        return $this;
    }

    private function addCondition($clause, $condition, $bindings)
    {
        $this->string[$clause][] = $condition . (mb_strpos($condition, '?') === false ? ' = ?' : '');
        if ( ! empty($bindings)) {
            if (is_array($bindings) && mb_substr_count($condition, '?') === 1) {
                $bindings = [$bindings];
            }
            $this->bindings[$clause] += (array) $bindings;
        }
    }

    /**
     * @inheritdoc
     */
    public function order($field, $direction = null)
    {
        if (!preg_match('/^[a-zA-Z0-9\._\-\(\)\"]*$/', $field)) {
            return $this;
        }
        $direction = in_array(mb_strtolower(trim($direction)), ['asc', 'desc']) ? $direction : null;
        $this->string['order'][] = $field . (is_string($direction) ? ' ' . $direction : '');
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function group($value)
    {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $value = trim($value);
        if ( ! empty($value)) {
            $this->string['group'] = 'GROUP BY ' . $value;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function limit($count, $offset = 0)
    {
        $count = (int) $count;
        $offset = (int) $offset;
        $this->string['limit'] = 'LIMIT ' . $count . ($offset ? ' OFFSET ' . $offset : '');
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function forUpdate()
    {
        $this->string['for_update'] = true;
        return $this;
    }

    /**
     * Join data from other table
     *
     * @param string $type Join type
     * @param string $table Table name
     * @param string $condition Condition
     * @return DbPsqlSelect
     */
    private function _join($type, $table, $condition)
    {
        if ($type == 'left') {
            $join = 'LEFT JOIN';
        } else if ($type == 'right') {
            $join = 'RIGHT JOIN';
        } else if ($type == 'inner') {
            $join = 'INNER JOIN';
        } else {
            $join = 'JOIN';
        }
        $this->string['join'][] = " $join $table ON $condition ";
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function innerJoin($table, $condition)
    {
        return $this->_join('inner', $table, $condition);
    }

    /**
     * @inheritdoc
     */
    public function leftJoin($table, $condition)
    {
        return $this->_join('left', $table, $condition);
    }

    /**
     * @inheritdoc
     */
    public function rightJoin($table, $condition)
    {
        return $this->_join('right', $table, $condition);
    }

    /**
     * @inheritdoc
     */
    public function addColumns($columns)
    {
        if (empty($columns)) return $this;

        if (is_string($columns)) {
            $this->string['columns'][] = $columns;
        } else if (is_array($columns)) {
            foreach ($columns as $column) {
                if (is_string($column)) {
                    $this->string['columns'][] = $column;
                } else if (is_array($column)) {
                    $this->string['columns'][] = $column[1] . '."' . $column[0] . '"';
                }
            }
        }

        return $this;
    }

    private function getBindings()
    {
        return array_reduce($this->bindings, function($carry, $item) {
            if ($carry === null) {
                return $item;
            }
            foreach ($item as $value) {
                $carry[] = $value;
            }
            return $carry;
        });
    }

    /**
     * @inheritdoc
     */
    public function fetchColumn()
    {
        return $this->db->fetchColumn($this, $this->getBindings());
    }

    /**
     * @inheritdoc
     */
    public function fetchAll()
    {
        return $this->db->fetchAll($this, $this->getBindings());
    }

    /**
     * @inheritdoc
     */
    public function fetchArray()
    {
        return $this->db->fetchArray($this, $this->getBindings());
    }

    /**
     * @inheritdoc
     */
    public function fetchAssoc()
    {
        return $this->db->fetchAssoc($this, $this->getBindings());
    }

    /**
     * @inheritdoc
     */
    public function fetchOne()
    {
        return $this->db->fetchOne($this, $this->getBindings());
    }

    /**
     * @inheritdoc
     */
    public function fetchPair()
    {
        return $this->db->fetchPair($this, $this->getBindings());
    }

    /**
     * @inheritdoc
     */
    public function fetchRow()
    {
        return $this->db->fetchRow($this, $this->getBindings());
    }

}
