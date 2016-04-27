<?php
use database\MysqliToPdoBundle\Tests\BaseDatabaseSetUp;
use database\MysqliToPdoBundle\Tests\mysqli\ConnectionTestBuilder;


/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 21.04.2016
 * Time: 22:22
 */
class ConnectionFunctionalPerformanceTest extends BaseDatabaseSetUp {
    /**
     * @var ConnectionTestBuilder
     */
    private $testBuilder;

    protected function setUp () {
        parent::setUp();
        $this->testBuilder = new ConnectionTestBuilder();
    }

    /**
     * @test
     */
    public function insert_withExec () {
//        $this->markTestSkipped();
        $pdoInsertTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->pdoConnection->exec('INSERT INTO parameter(`name`) VALUES(\'test'.$i.'\')');
        }
        $pdoInsertTime = microtime(true) - $pdoInsertTime;

        $mysqliConnectionInsertTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->mysqliConnection->exec('INSERT INTO parameter(`name`) VALUES(\'test'.$i.'\')');
        }
        $mysqliConnectionInsertTime = microtime(true) - $mysqliConnectionInsertTime;

        $mysqliInsertTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->mysqli->query('INSERT INTO parameter(`name`) VALUES(\'test'.$i.'\')');
        }
        $mysqliInsertTime = microtime(true) - $mysqliInsertTime;

        print_r('performance test: 100 inserts with exec command'.PHP_EOL);
        print_r('mysqli wrapper: '.$mysqliConnectionInsertTime.PHP_EOL);
        print_r('mysqli: '.$mysqliInsertTime.PHP_EOL);
        print_r('pdo: '.$pdoInsertTime.PHP_EOL.PHP_EOL);
    }

    /**
     * @test
     */
    public function insert_withPrepare () {
        // $this->markTestSkipped();
        $pdoInsertTime = microtime(true);
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO parameter(`name`) VALUES(:name)');
        for ($i = 0; $i < 100; $i++) {
            $pdoStatement->bindValue('name', 'test'.$i);
            $pdoStatement->execute();
        }
        $pdoInsertTime = microtime(true) - $pdoInsertTime;

        $mysqliConnectionInsertTime = microtime(true);
        $mysqliStatement = $this->mysqliConnection->prepare('INSERT INTO parameter(`name`) VALUES(:name)');
        for ($i = 0; $i < 100; $i++) {
            $mysqliStatement->bindValue('name', 'test'.$i);
            $mysqliStatement->execute();
        }
        $mysqliConnectionInsertTime = microtime(true) - $mysqliConnectionInsertTime;

        $mysqliInsertTime = microtime(true);
        $mysqliStmt = $this->mysqli->prepare('INSERT INTO parameter(`name`) VALUES(?)');
        for ($i = 0; $i < 100; $i++) {
            $val = 'test'.$i;
            $mysqliStmt->bind_param('s', $val);
            $mysqliStmt->execute();
        }
        $mysqliInsertTime = microtime(true) - $mysqliInsertTime;

        print_r('performance test: 100 inserts with prepare command'.PHP_EOL);
        print_r('mysqli wrapper: '.$mysqliConnectionInsertTime.PHP_EOL);
        print_r('mysqli: '.$mysqliInsertTime.PHP_EOL);
        print_r('pdo: '.$pdoInsertTime.PHP_EOL.PHP_EOL);
    }

    /**
     * @test
     */
    public function select () {
        //  $this->markTestSkipped();
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO parameter(`name`) VALUES(:name)');
        for ($i = 0; $i < 1000; $i++) {
            $pdoStatement->bindValue('name', 'test'.$i);
            $pdoStatement->execute();
        }

        $pdoSelectTime = microtime(true);
        $pdoStatement = $this->pdoConnection->prepare('SELECT * FROM parameter');
        $pdoStatement->execute();
        $pdoSelectTime = microtime(true) - $pdoSelectTime;

        $mysqliConnectionSelectTime = microtime(true);
        $mysqliStatement = $this->mysqliConnection->prepare('SELECT * FROM parameter');
        $mysqliStatement->execute();
        $mysqliConnectionSelectTime = microtime(true) - $mysqliConnectionSelectTime;

        $mysqliSelectTime = microtime(true);
        $mysqliStmt = $this->mysqli->prepare('SELECT * FROM parameter');
        $mysqliStmt->execute();
        $mysqliSelectTime = microtime(true) - $mysqliSelectTime;

        print_r('performance test: select with 1000 result rows'.PHP_EOL);
        print_r('mysqli wrapper: '.$mysqliConnectionSelectTime.PHP_EOL);
        print_r('mysqli: '.$mysqliSelectTime.PHP_EOL);
        print_r('pdo: '.$pdoSelectTime.PHP_EOL.PHP_EOL);
    }
}