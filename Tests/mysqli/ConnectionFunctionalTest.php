<?php

use database\MysqliToPdoBundle\mysqli\MysqliException;
use database\MysqliToPdoBundle\mysqli\MysqliStatement;
use database\MysqliToPdoBundle\Tests\BaseDatabaseSetUp;
use database\MysqliToPdoBundle\Tests\mysqli\ConnectionTestBuilder;

/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 20.04.2016
 * Time: 19:32
 */
class ConnectionFunctionalTest extends BaseDatabaseSetUp {


    /**
     * @var ConnectionTestBuilder
     */
    private $testBuilder;

    protected function setUp () {
        parent::setUp();
        $this->testBuilder = new ConnectionTestBuilder();
    }

    /**
     * this test run a simple insert without transaction.
     * @test
     */
    public function simpleInsertAndSelect () {
        $this->testBuilder->createBindValueInsert($this->mysqliConnection,
                                                  'parameter',
                                                  ['name' => 'mysqli', 'order' => 1]);
        $this->testBuilder->createBindValueInsert($this->pdoConnection,
                                                  'parameter',
                                                  ['name' => 'pdo', 'order' => 2]);

        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter');
        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter');

        $this->assertSame(2, count($pdoResult));
        $this->assertEquals($pdoResult, $mysqliResult);

        $this->assertSame(2, count($pdoResult));
        $this->assertEquals($pdoResult, $mysqliResult);
        $this->assertArraySubset([['name' => 'mysqli', 'order' => 1],
                                  ['name' => 'pdo', 'order' => 2]],
                                 $mysqliResult);
    }

    /**
     * this test binds the parameters in another order, as they are in the query.
     * @test
     */
    public function insertWithOtherParamOrder () {
        $this->testBuilder->createBindValueInsertWithOrder($this->mysqliConnection,
                                                           'parameter',
                                                           ['name' => 'mysqli', 'order' => 1]);
        $this->testBuilder->createBindValueInsertWithOrder($this->pdoConnection,
                                                           'parameter',
                                                           ['name' => 'pdo', 'order' => 2]);

        $compareResult = [['id' => 1, 'name' => 'mysqli', 'order' => 1],
                          ['id' => 2, 'name' => 'pdo', 'order' => 2]];
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter', 'id,`name`,`order`');
        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter', 'id,`name`,`order`');

        $this->assertEquals($compareResult, $mysqliResult);
        $this->assertEquals($compareResult, $pdoResult);
    }

    /**
     * set the parameter with PDO::PARAM_INT
     * @test
     */
    public function insertNumeric () {
        $this->testBuilder->createNumeric($this->mysqliConnection, 54321);
        $this->testBuilder->createNumeric($this->pdoConnection, 54321);

        //set invalid value
        $this->testBuilder->createNumeric($this->mysqliConnection, 'test');
        $this->testBuilder->createNumeric($this->pdoConnection, 'test');

        $compareResult = [
            ['id' => '1', 'value' => '54321'],
            ['id' => '2', 'value' => '54321'],
            ['id' => '3', 'value' => '0'],
            ['id' => '4', 'value' => '0']
        ];
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'int_table');
        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'int_table');

        $this->assertEquals($compareResult, $mysqliResult);
        $this->assertEquals($compareResult, $pdoResult);
    }

    /**
     * set the parameter with PDO::PARAM_LOB
     * @test
     */
    public function insertBlob () {
        $this->testBuilder->createBlob($this->pdoConnection, 'string');
        $this->testBuilder->createBlob($this->mysqliConnection, 'string');

        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'blob_table');
        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'blob_table');
        $this->assertEquals($pdoResult, $mysqliResult);
    }

    /**
     * insert a value with all tree connections and check if they hold them. after a rollbacks, the table is empty
     * @test
     */
    public function transaction_withRollback () {
        $this->assertEquals(false, $this->pdoConnection->inTransaction());
        $this->assertEquals(false, $this->mysqliConnection->inTransaction());

        $this->pdoConnection->beginTransaction();
        $this->mysqliConnection->beginTransaction();

        $this->assertEquals(true, $this->pdoConnection->inTransaction());
        $this->assertEquals(true, $this->mysqliConnection->inTransaction());

        $this->testBuilder->createBindValueInsert($this->pdoConnection,
                                                  'parameter',
                                                  ['name' => 'pdo', 'order' => 1]);
        $this->testBuilder->createBindValueInsert($this->mysqliConnection,
                                                  'parameter',
                                                  ['name' => 'mysqli', 'order' => 2]);

        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter', 'id,`name`,`order`');
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter', 'id,`name`,`order`');

        $this->assertEquals(1, count($pdoResult));
        $this->assertEquals(1, count($mysqliResult));

        $this->assertEquals([['id' => '1', 'name' => 'pdo', 'order' => '1']], $pdoResult);
        $this->assertEquals([['id' => '2', 'name' => 'mysqli', 'order' => '2']], $mysqliResult);

        $this->pdoConnection->rollBack();
        $this->mysqliConnection->rollBack();

        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter', 'id,`name`,`order`');
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter', 'id,`name`,`order`');

        $this->assertEquals([], $pdoResult);
        $this->assertEquals([], $mysqliResult);
    }

    /**
     * check if all values saved after a commit
     * @test
     */
    public function transaction_withCommit () {
        $this->pdoConnection->beginTransaction();
        $this->mysqliConnection->beginTransaction();

        $this->testBuilder->createBindValueInsert($this->pdoConnection,
                                                  'parameter',
                                                  ['name' => 'pdo', 'order' => 1]);
        $this->testBuilder->createBindValueInsert($this->mysqliConnection,
                                                  'parameter',
                                                  ['name' => 'mysqli', 'order' => 2]);

        $this->pdoConnection->commit();
        $this->mysqliConnection->commit();

        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter');
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter');

        $this->assertEquals(2, count($pdoResult));
        $this->assertEquals($pdoResult, $mysqliResult);
    }

    /**
     * this test check, if changes on the binded params are used in the execute
     * @test
     */
    public function bindParam () {
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name,:order)');
        $mysqliStatement = $this->mysqliConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name,:order)');

        $name = 'test1';
        $order = 1;

        $pdoStatement->bindParam('name', $name);
        $mysqliStatement->bindParam('name', $name);

        $pdoStatement->bindParam('order', $order);
        $mysqliStatement->bindParam('order', $order);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        //this variables are used as pointer in the execute method!
        $name = 'test2';
        $order = 2;

        $pdoStatement->execute();
        $mysqliStatement->execute();

        unset($name);
        unset($order);

        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter', 'id,`name`,`order`');
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter', 'id,`name`,`order`');

        $compareResult = [['id' => 1, 'name' => 'test1', 'order' => 1],
                          ['id' => 2, 'name' => 'test1', 'order' => 1],
                          ['id' => 3, 'name' => 'test2', 'order' => 2],
                          ['id' => 4, 'name' => 'test2', 'order' => 2]];

        $this->assertEquals($compareResult, $pdoResult);
        $this->assertEquals($compareResult, $mysqliResult);
    }

    /**
     * check that a parameter can be overwritten
     * @test
     */
    public function bindParam_overwrite () {
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name,:order)');
        $mysqliStatement = $this->mysqliConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name,:order)');

        $name = 'test1';
        $order = 1;

        $pdoStatement->bindParam('name', $name);
        $mysqliStatement->bindParam('name', $name);

        $pdoStatement->bindParam('order', $order);
        $mysqliStatement->bindParam('order', $order);

        $pdoStatement->execute();
        $this->assertEquals(1, $this->pdoConnection->lastInsertId());
        $mysqliStatement->execute();
        $this->assertEquals(2, $this->mysqliConnection->lastInsertId());

        $nameOverwrite = 'test3';
        $orderOverwrite = 3;

        //overwrite the param with another param
        $pdoStatement->bindParam('name', $nameOverwrite);
        $mysqliStatement->bindParam('name', $nameOverwrite);

        $pdoStatement->bindParam('order', $orderOverwrite);
        $mysqliStatement->bindParam('order', $orderOverwrite);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        //overwrite the param with an value
        $pdoStatement->bindValue('name', 'test4');
        $mysqliStatement->bindValue('name', 'test4');

        $pdoStatement->bindValue('order', 4);
        $mysqliStatement->bindValue('order', 4);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter', 'id,`name`,`order`');
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter', 'id,`name`,`order`');

        $compareResult = [['id' => 1, 'name' => 'test1', 'order' => 1],
                          ['id' => 2, 'name' => 'test1', 'order' => 1],
                          ['id' => 3, 'name' => 'test3', 'order' => 3],
                          ['id' => 4, 'name' => 'test3', 'order' => 3],
                          ['id' => 5, 'name' => 'test4', 'order' => 4],
                          ['id' => 6, 'name' => 'test4', 'order' => 4]];

        $this->assertEquals($compareResult, $pdoResult);
        $this->assertEquals($compareResult, $mysqliResult);
    }

    /**
     * this test check, that the bindValue does not use a variable reference
     * @test
     */
    public function bindValue () {
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name,:order)');
        $mysqliStatement = $this->mysqliConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name,:order)');

        $name = 'test1';
        $order = 1;

        $pdoStatement->bindValue('name', $name);
        $mysqliStatement->bindValue('name', $name);

        $pdoStatement->bindValue('order', $order);
        $mysqliStatement->bindValue('order', $order);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        //this change do not use in the execute! the bindValue does not allowed to use a reference
        $name = 'test2';
        $order = 2;

        $pdoStatement->execute();
        $mysqliStatement->execute();

        unset($name);
        unset($order);

        $compareResult = [['id' => 1, 'name' => 'test1', 'order' => 1],
                          ['id' => 2, 'name' => 'test1', 'order' => 1],
                          ['id' => 3, 'name' => 'test1', 'order' => 1],
                          ['id' => 4, 'name' => 'test1', 'order' => 1]];

        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter', 'id,`name`,`order`');
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter', 'id,`name`,`order`');

        $this->assertEquals($compareResult, $pdoResult);
        $this->assertEquals($compareResult, $mysqliResult);
    }

    /**
     * check that a value can be overwritten
     * @test
     */
    public function bindValue_overwrite () {
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name,:order)');
        $mysqliStatement = $this->mysqliConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name,:order)');

        $pdoStatement->bindValue('name', 'test1');
        $mysqliStatement->bindValue('name', 'test1');

        $pdoStatement->bindValue('order', 1);
        $mysqliStatement->bindValue('order', 1);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        //overwrite the value with another value
        $pdoStatement->bindValue('name', 'test2');
        $mysqliStatement->bindValue('name', 'test2');

        $pdoStatement->bindValue('order', 2);
        $mysqliStatement->bindValue('order', 2);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        //overwrite the value with an param
        $nameOverwrite = 'test3';
        $orderOverwrite = 3;

        $pdoStatement->bindParam('name', $nameOverwrite);
        $mysqliStatement->bindParam('name', $nameOverwrite);

        $pdoStatement->bindParam('order', $orderOverwrite);
        $mysqliStatement->bindParam('order', $orderOverwrite);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'parameter', 'id,`name`,`order`');
        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'parameter', 'id,`name`,`order`');

        $compareResult = [['id' => 1, 'name' => 'test1', 'order' => 1],
                          ['id' => 2, 'name' => 'test1', 'order' => 1],
                          ['id' => 3, 'name' => 'test2', 'order' => 2],
                          ['id' => 4, 'name' => 'test2', 'order' => 2],
                          ['id' => 5, 'name' => 'test3', 'order' => 3],
                          ['id' => 6, 'name' => 'test3', 'order' => 3]];

        $this->assertEquals($compareResult, $pdoResult);
        $this->assertEquals($compareResult, $mysqliResult);
    }

    /**
     * check if the supported fetch-types work correctly
     * @test
     */
    public function fetch_modes () {
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO parameter(`name`) VALUES(:name)');
        $pdoStatement->bindValue('name', 'test2');
        $pdoStatement->execute();

        $pdoStatement = $this->pdoConnection->prepare('SELECT * FROM parameter');
        $mysqliStatement = $this->mysqliConnection->prepare('SELECT * FROM parameter');

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $pdoFetch = $pdoStatement->fetch(PDO::FETCH_NUM);
        $mysqliFetch = $mysqliStatement->fetch(PDO::FETCH_NUM);
        $this->assertEquals($pdoFetch, $mysqliFetch);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $pdoFetch = $pdoStatement->fetch(PDO::FETCH_OBJ);
        $mysqliFetch = $mysqliStatement->fetch(PDO::FETCH_OBJ);
        $this->assertEquals($pdoFetch, $mysqliFetch);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $pdoFetch = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        $mysqliFetch = $mysqliStatement->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($pdoFetch, $mysqliFetch);

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $pdoFetch = $pdoStatement->fetch(PDO::FETCH_BOTH);
        $mysqliFetch = $mysqliStatement->fetch(PDO::FETCH_BOTH);
        $this->assertEquals($pdoFetch, $mysqliFetch);
    }

    /**
     * check the handling of multiple statements
     * @test
     */
    public function multiple_statements () {
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO parameter(`name`,`order`) VALUES(:name)');
        for ($i = 0; $i < 10; $i++) {
            $pdoStatement->bindValue('name', 'test1');
            $pdoStatement->execute();
        }
        $pdoStatement = $this->pdoConnection->prepare('INSERT INTO int_table(`value`) VALUES(:value)');
        for ($i = 0; $i < 10; $i++) {
            $pdoStatement->bindValue('value', $i);
            $pdoStatement->execute();
        }

        $pdoStatement = $this->pdoConnection->prepare('SELECT * FROM parameter');
        $mysqliStatement = $this->mysqliConnection->prepare('SELECT * FROM parameter');
        $anotherPdoStatement = $this->pdoConnection->prepare('SELECT * FROM int_table');
        $anotherMysqliStatement = $this->mysqliConnection->prepare('SELECT * FROM int_table');

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $firstPdoRow = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        $firstMysqliRow = $mysqliStatement->fetch(PDO::FETCH_ASSOC);

        $anotherPdoStatement->execute();
        $anotherMysqliStatement->execute();

        $anotherPdoResultSet = [];
        $anotherMysqliResultSet = [];
        $pdoResultSet = [];
        $mysqliResultSet = [];

        while ($row = $anotherPdoStatement->fetch(PDO::FETCH_ASSOC)) {
            $anotherPdoResultSet[] = $row;
        }
        while ($row = $anotherMysqliStatement->fetch(PDO::FETCH_ASSOC)) {
            $anotherMysqliResultSet[] = $row;
        }

        while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC)) {
            $pdoResultSet[] = $row;
        }
        while ($row = $mysqliStatement->fetch(PDO::FETCH_ASSOC)) {
            $mysqliResultSet[] = $row;
        }

        $this->assertEquals($firstPdoRow, $firstMysqliRow);
        $this->assertEquals($pdoResultSet, $mysqliResultSet);
        $this->assertEquals($anotherPdoResultSet, $anotherMysqliResultSet);
    }

    /**
     * compare the connection exec method between pdo and mysqli
     * @test
     */
    public function exec () {
        $pdoCount = $this->pdoConnection->exec('INSERT INTO parameter(`name`) VALUES(\'test1\'); INSERT INTO parameter(`name`) VALUES(\'test2\'); INSERT INTO parameter(`name`) VALUES(\'test3\');');
        $mysqliCount = $this->pdoConnection->exec('INSERT INTO parameter(`name`) VALUES(\'test1\'); INSERT INTO parameter(`name`) VALUES(\'test2\'); INSERT INTO parameter(`name`) VALUES(\'test3\');');
        $this->assertSame($pdoCount, $mysqliCount);

        $pdoCount = $this->pdoConnection->exec('UPDATE parameter SET `order` = 2');
        $mysqliCount = $this->mysqliConnection->exec('UPDATE parameter SET `order` = 3');
        $this->assertSame($pdoCount, $mysqliCount);
    }

    /**
     * @test
     */
    public function query () {
        $this->pdoConnection->exec('INSERT INTO parameter(`name`) VALUES(\'test1\'); INSERT INTO parameter(`name`) VALUES(\'test2\'); INSERT INTO parameter(`name`) VALUES(\'test3\');');

        $pdoStatement = $this->pdoConnection->query('SELECT * FROM parameter');
        $mysqliStatement = $this->mysqliConnection->query('SELECT * FROM parameter');

        while ($pdoRow = $pdoStatement->fetch(PDO::FETCH_ASSOC)) {
            $mysqliRow = $mysqliStatement->fetch(PDO::FETCH_ASSOC);
            $this->assertEquals($pdoRow, $mysqliRow);
        }
    }

    /**
     * @test
     */
    public function quote () {
        $pdoQuote = $this->pdoConnection->quote('test\'1\'');
        $mysqliQuote = $this->mysqliConnection->quote('test\'1\'');
        $this->assertSame($pdoQuote, $mysqliQuote);
    }

    /**
     * @test
     */
    public function iterator () {
        for ($i = 0; $i < 10; $i++) {
            $this->testBuilder->createBindValueInsert($this->pdoConnection,
                                                      'parameter',
                                                      ['name' => 'test'.$i, 'order' => $i]);
        }

        $pdoStatement = $this->pdoConnection->query('SELECT * FROM parameter');
        $mysqliStatement = $this->mysqliConnection->query('SELECT * FROM parameter');
        $pdoStatement->setFetchMode(PDO::FETCH_ASSOC);
        $mysqliStatement->setFetchMode(PDO::FETCH_ASSOC);

        $pdoRows = [];
        $mysqliRows = [];
        foreach ($mysqliStatement as $row) {
            $mysqliRows[] = $row;
        }

        foreach ($pdoStatement as $row) {
            $pdoRows[] = $row;
        }
        $this->assertEquals($pdoRows, $mysqliRows);

        $mysqliStatement->rewind();
        $mysqliRewindRows = [];
        foreach ($mysqliStatement as $row) {
            $mysqliRewindRows[] = $row;
        }
        $this->assertEquals($pdoRows, $mysqliRewindRows);
    }

    /**
     * test the countRow function of the StatementInterface. this is not possible as unit test because public properties
     * (count_row) are not mockable
     * @test
     */
    public function countRow () {
        $this->testBuilder->createBindValueInsert($this->mysqliConnection,
                                                  'parameter',
                                                  ['name' => 'mysqli', 'order' => 1]);
        $this->testBuilder->createBindValueInsert($this->pdoConnection,
                                                  'parameter',
                                                  ['name' => 'pdo', 'order' => 2]);

        $pdoStatement = $this->pdoConnection->prepare('SELECT * FROM parameter');
        $mysqliStatement = $this->mysqliConnection->prepare('SELECT * FROM parameter');

        //this returns 0 because the statement was not executed
        $this->assertSame($pdoStatement->rowCount(), $mysqliStatement->rowCount());

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $this->assertSame(2, $pdoStatement->rowCount());
        $this->assertSame($pdoStatement->rowCount(), $mysqliStatement->rowCount());
    }

    /**
     * test the columnCount function. same problem as countRow. it is not testable with unit tests
     * @test
     */
    public function columnCount () {
        $this->testBuilder->createBindValueInsert($this->mysqliConnection,
                                                  'parameter',
                                                  ['name' => 'mysqli', 'order' => 1]);
        $this->testBuilder->createBindValueInsert($this->pdoConnection,
                                                  'parameter',
                                                  ['name' => 'pdo', 'order' => 2]);

        $pdoStatement = $this->pdoConnection->prepare('SELECT id,`name`,`order` FROM parameter');
        $mysqliStatement = $this->mysqliConnection->prepare('SELECT id,`name`,`order` FROM parameter');

        //this returns 0 because the statement was not executed
        $this->assertSame($pdoStatement->columnCount(), $mysqliStatement->columnCount());

        $pdoStatement->execute();
        $mysqliStatement->execute();

        $this->assertSame(3, $pdoStatement->columnCount());
        $this->assertSame($pdoStatement->columnCount(), $mysqliStatement->columnCount());
    }

    /**
     * check if the construct sends an exception on statement failure
     * @test
     */
    public function construct () {
        $connectionReflection = new ReflectionClass($this->mysqliConnection);
        $connectionProperty = $connectionReflection->getProperty('connection');
        $connectionProperty->setAccessible(true);

        $this->setExpectedException(MysqliException::class);
        $statement = new MysqliStatement($connectionProperty->getValue($this->mysqliConnection), '');
        unset($statement);
    }

    /**
     * test the loading of an stream param. its not possible to test with an unit test
     * @test
     */
    public function loadStreamParam () {
        $fp = fopen(__DIR__.'/../ConnectionTestSchema.sql', 'rb');

        rewind($fp);
        $this->testBuilder->createBlob($this->pdoConnection, $fp);
        rewind($fp);
        $this->testBuilder->createBlob($this->mysqliConnection, $fp);

        $mysqliResult = $this->testBuilder->createSelect($this->mysqliConnection, 'blob_table');
        $pdoResult = $this->testBuilder->createSelect($this->pdoConnection, 'blob_table');
        $this->assertEquals($pdoResult, $mysqliResult);
        rewind($fp);
        $content = stream_get_contents($fp);
        $this->assertSame($content, $pdoResult[1]['value']);
    }

    /**
     * @test
     */
    public function error () {
        $mysqliStatement = $this->mysqliConnection->prepare('SELECT * FROM parameter');
        $this->assertSame($mysqliStatement->errorCode(), $this->mysqliConnection->errorCode());
        $this->assertSame($mysqliStatement->errorInfo(), $this->mysqliConnection->errorInfo());
    }
}