<?php

use PostgresDB\Driver as DB;

class BasicTest extends Base {

    public function testSimpleQuery()
    {
        $result = DB::fetchOne('SELECT ?', 'Hello from Postgres!');
        $this->assertEquals('Hello from Postgres!', $result);
    }

    public function testCreateTable()
    {
        $result = DB::query(
            'create table if not exists public.users ('
            . 'id bigserial, name varchar(100) not null, '
            . 'created_at timestamptz not null default now());'
        );
        $this->assertTrue($result);
    }

    /**
     * @depends testCreateTable
     */
    public function testTruncateTable()
    {
        $result = DB::query('truncate public.users');
        DB::query('alter sequence public.users_id_seq restart with 5;');
        $this->assertTrue($result);
    }

    /**
     * @depends testTruncateTable
     */
    public function testFillCreatedTable()
    {
        $result = DB::insert('public.users', ['name'], $this->users, true);
        $this->assertEquals([5,6,7], $result);
    }

    /**
     * @depends testFillCreatedTable
     */
    public function testGetLogs()
    {
        $result = DB::getQueriesLog();
        $this->assertCount(5, $result);
    }

}
