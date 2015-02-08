<?php

require_once __DIR__ . '/Base.php';

class BasicTest extends Base {

    public function testDatabaseConnectionWithSimpleQuery()
    {
        $result = $this->db->fetchOne("SELECT ?", ['Hello from Postgres!']);
        $this->assertEquals('Hello from Postgres!', $result);
    }

    public function testCreateTable()
    {
        $result = $this->db->query(
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
        $result = $this->db->query('truncate public.users');
        $this->db->query('alter sequence public.users_id_seq restart with 5;');
        $this->assertTrue($result);
    }

    /**
     * @depends testTruncateTable
     */
    public function testFillCreatedTable()
    {
        $result = $this->db->insert('public.users', ['name'], $this->users);
        $this->assertEquals([5,6,7], $result);
    }

    /**
     * @depends testFillCreatedTable
     */
    public function testGetLogs()
    {
        $result = $this->db->getQueriesLog();
        $this->assertCount(5, $result);
    }

}
