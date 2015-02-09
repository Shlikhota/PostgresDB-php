<?php

require_once __DIR__ . '/Base.php';

class SelectQueriesTest extends Base {

    public function testSelectFetchAllAsObjectsEntries()
    {
        $result = $this->db
            ->select()->from('public.users')->fetchAll();
        $this->assertThat(
            $result,
            $this->logicalAnd(
                $this->containsOnlyInstancesOf('stdClass')
            )
        );
    }

    public function testSelectFetchAllAsArrayEntries()
    {
        $result = $this->db
            ->select()->from('public.users')->fetchArray();
        $this->assertThat(
            $result,
            $this->logicalAnd(
                $this->containsOnly('array')
            )
        );
    }

    public function testSelectFetchColumnAsArrayWhereEntriesAreValuesFirstColumn()
    {
        $result = $this->db
            ->select('name')->from('public.users')->order('id', 'asc')->fetchColumn();
        $expect = array_reduce($this->users, function($carry, $item) {
            return array_merge(($carry ?: []), $item);
        });
        $this->assertThat(
            $result,
            $this->logicalAnd(
                $this->containsOnly('string'),
                $this->identicalTo($expect)
            )
        );
    }

    public function testSelectFetchAssocAsArrayWhereKeyIsFirstColumn()
    {
        $result = $this->db
            ->select()->from('public.users')->order('id', 'asc')->fetchAssoc();
        $this->assertEquals([5,6,7], array_keys($result));
    }

    public function testSelectFetchOneWhereValueIsFirstColumnOfFirstEntry()
    {
        $result = $this->db
            ->select('name')->from('public.users')->where('id = ?', 5)->fetchOne();
        $this->assertEquals($this->users[0][0], $result);
    }

    public function testSelectFetchPairAsArrayWhereKeyIsFirstColumnAndValueIsSecond()
    {
        $result = $this->db
            ->select('id', 'name')->from('public.users')->order('id', 'asc')->fetchPair();
        $expect = [
            5 => $this->users[0][0],
            6 => $this->users[1][0],
            7 => $this->users[2][0]
        ];
        $this->assertThat(
            $result,
            $this->logicalAnd(
                $this->containsOnly('string'),
                $this->identicalTo($expect)
            )
        );
    }

    public function testSelectFetchRowAsObject()
    {
        $result = $this->db
            ->select()->from('public.users')->where('id = ?', 5)->fetchRow();
        $this->assertThat(
            $result,
            $this->logicalAnd(
                $this->isInstanceOf('stdClass'),
                $this->ObjectHasAttribute('name')
            )
        );
    }

}
