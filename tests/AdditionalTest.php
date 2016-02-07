<?php

use PostgresDB\Driver as DB;

class AdditionalTest extends Base {

    public function testClosureTransaction()
    {
        $user_id = 0;
        $deleted = 0;
        DB::transaction(function($db) use (&$user_id, &$deleted) {
            // This will be done in a transaction
            $user_id = $db->select('id')->from('users')->order('id', 'asc')->fetchOne();
            $deleted = $db->delete('users', ['id' => $user_id]);
        });
        $this->assertEquals(5, $user_id);
        $this->assertEquals(1, $deleted);
    }

    public function testCallFunctionInSelect()
    {
        $result = DB::select('t.date::date')
            ->from('generate_series(?::date, ?::date, ?) AS t(date)', ['2015-04-21', '2015-04-23', '1 day'])
            ->fetchColumn();
        $this->assertEquals(['2015-04-21', '2015-04-22', '2015-04-23'], $result);
    }

    public function testCallFunctionInFrom()
    {
        $result = DB::select(
            'generate_series(?::date, ?::date, ?)::date',
            ['2015-04-25', '2015-04-27', '1 day']
        )->fetchColumn();
        $this->assertEquals(['2015-04-25', '2015-04-26', '2015-04-27'], $result);
    }

}
