<?php

class AdditionalTest extends Base {

    public function testClosureTransaction()
    {
        $user_id = 0;
        $deleted = 0;
        $this->db->transaction(function($db) use (&$user_id, &$deleted) {
            // This will be done in a transaction
            $user_id = $db->select('id')->from('users')->order('id', 'asc')->fetchOne();
            $deleted = $db->delete('users', ['id' => $user_id]);
        });
        $this->assertEquals(5, $user_id);
        $this->assertEquals(1, $deleted);
    }

}
