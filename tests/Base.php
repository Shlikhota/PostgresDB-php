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
            'database' => 'psqldriver_tests',
            'username' => 'psqldriver_owner',
            'password' => '123',
            'charset' => 'utf8',
            'options' => []
        ];
        $this->db = PostgresDB\Driver::instance($config)
            ->isLog(true)
            ->setDebug(true);
    }

}
