<?php

abstract class Base extends PHPUnit_Framework_TestCase {

    protected $db;

    protected $users = [
        ['Patrick Carter'],
        ['Edison Jenkins'],
        ['Rachelle Thiel']
    ];

    protected function setUp()
    {
        $config = [
            'host' => 'localhost',
            'port' => '5432',
            'database' => 'postgresdb_tests',
            'username' => 'postgresdb_tests',
            'password' => '',
            'charset' => 'utf8',
            'options' => []
        ];
        $this->db = (new PostgresDB\Driver($config))
            ->isLog(true)
            ->setDebug(true);
    }

}
