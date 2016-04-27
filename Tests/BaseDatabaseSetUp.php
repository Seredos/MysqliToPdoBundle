<?php
/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 21.04.2016
 * Time: 22:25
 */

namespace database\MysqliToPdoBundle\Tests;


use database\MysqliToPdoBundle\mysqli\MysqliConnection;
use mysqli;
use PHPUnit_Framework_TestCase;

class BaseDatabaseSetUp extends PHPUnit_Framework_TestCase {
    /**
     * @var MysqliConnection
     */
    protected $mysqliConnection;
    /**
     * @var \PDO
     */
    protected $pdoConnection;

    /**
     * @var mysqli
     */
    protected $mysqli;

    private $run;
    private $config = ['server' => '127.0.0.1', 'user' => 'root', 'password' => '', 'name' => 'test2'];

    protected function setUp () {
        //$this->markTestSkipped('please configure your database on this place to run this tests');
        $this->pdoConnection = new \PDO('mysql:host='.$this->config['server'].';',
                                        $this->config['user'],
                                        $this->config['password']);
        $this->pdoConnection->exec('DROP SCHEMA IF EXISTS '.$this->config['name']);
        $this->pdoConnection->exec('CREATE SCHEMA '.$this->config['name']);
        $this->pdoConnection->exec('USE '.$this->config['name']);
        $this->pdoConnection->exec(file_get_contents(__DIR__.'\ConnectionTestSchema.sql'));

        $this->mysqli = new mysqli($this->config['server'],
                                   $this->config['user'],
                                   $this->config['password'],
                                   $this->config['name']);

        $this->mysqliConnection = new MysqliConnection($this->mysqli);
        $this->run = true;
    }

    protected function tearDown () {
        if ($this->run) {
            $this->pdoConnection = null;
            $this->mysqli->close();
            $this->run = false;
        }
    }
}