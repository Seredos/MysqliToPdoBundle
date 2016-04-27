<?php
use database\MysqliToPdoBundle\mysqli\MysqliStatement;
use database\MysqliToPdoBundle\mysqli\MysqliException;

/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 19.04.2016
 * Time: 22:05
 */
class MysqliStatementTest extends PHPUnit_Framework_TestCase {
    /**
     * @var ReflectionClass
     */
    private $statementReflection;

    /**
     * @var MysqliStatement|PHPUnit_Framework_MockObject_MockObject
     */
    private $statementMock;

    protected function setUp () {
        $this->statementReflection = new ReflectionClass(MysqliStatement::class);

        $this->statementMock = $this->getMockBuilder(MysqliStatement::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();
    }

    /**
     * @test
     */
    public function construct () {
        /* @var $mysqli mysqli|PHPUnit_Framework_MockObject_MockObject */
        $mysqli = $this->getMockBuilder(mysqli::class)
                       ->disableOriginalConstructor()
                       ->getMock();

        $mysqli->expects($this->once())
               ->method('prepare')
               ->will($this->returnValue(true));

        $this->statementMock = $this->createRealStatementMock(['getConnectionError'], $mysqli);

        $this->assertSame([], $this->reflectionGetProperty('values'));
        $this->assertSame([], $this->reflectionGetProperty('parameters'));
        $this->assertSame('', $this->reflectionGetProperty('_types'));
        $this->assertSame(false, $this->reflectionGetProperty('bindedParams'));
        $this->assertSame('', $this->reflectionGetProperty('sql'));
    }

    /**
     * @test
     */
    public function fetch_withInvalidType () {
        $this->statementMock = $this->createRealStatementMock();
        $this->reflectionSetProperty('_result',
                                     $this->getMockBuilder(mysqli_result::class)
                                          ->disableOriginalConstructor()
                                          ->getMock());


        $this->setExpectedExceptionRegExp(MysqliException::class);
        $this->statementMock->fetch('asd');
    }

    /**
     * @test
     */
    public function fetch_withoutResult () {
        $this->statementMock = $this->getMockBuilder(MysqliStatement::class)
                                    ->setConstructorArgs([
                                                             $this->createMysqliMock(),
                                                             ''
                                                         ])
                                    ->setMethods(['prepareKey'])
                                    ->getMock();

        $this->reflectionSetProperty('fetchMode', 'invalid');
        $this->setExpectedExceptionRegExp(MysqliException::class);
        $this->statementMock->fetch();
    }

    /**
     * @test
     */
    public function setFetchMode () {
        $this->statementMock = $this->createRealStatementMock();

        $this->reflectionSetProperty('fetchMode', PDO::FETCH_ASSOC);
        $this->assertSame(true, $this->statementMock->setFetchMode(PDO::FETCH_NUM));
        $this->assertSame(PDO::FETCH_NUM, $this->reflectionGetProperty('fetchMode'));
    }

    /**
     * @test
     */
    public function fetch () {
        $this->createFetchModeMock('fetch_assoc');
        $this->reflectionSetProperty('fetchMode', PDO::FETCH_ASSOC);

        $this->assertSame(['test'], $this->statementMock->fetch());
    }

    /**
     * @test
     */
    public function fetch_withInvalidDefaultType () {
        $this->createFetchModeMock('fetch_array', MYSQLI_NUM);
        $this->statementMock->setFetchMode(PDO::FETCH_ASSOC);
        $this->reflectionSetProperty('fetchMode', PDO::FETCH_NUM);

        $this->statementMock->fetch();
    }

    /**
     * @test
     */
    public function fetch_assoc () {
        $this->createFetchModeMock('fetch_assoc');

        $this->assertSame(['test'], $this->statementMock->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * @test
     */
    public function fetch_both () {
        $this->createFetchModeMock('fetch_array');

        $this->assertSame(['test'], $this->statementMock->fetch(PDO::FETCH_BOTH));
    }

    /**
     * @test
     */
    public function fetch_num () {
        $this->createFetchModeMock('fetch_array', MYSQLI_NUM);
        $this->assertSame(['test'], $this->statementMock->fetch(PDO::FETCH_NUM));
    }

    /**
     * @test
     */
    public function fetch_object () {
        $this->createFetchModeMock('fetch_object');
        $this->assertSame(['test'], $this->statementMock->fetch(PDO::FETCH_OBJ));
    }

    /**
     * @test
     */
    public function fetchAll_withInvalidType () {
        $this->statementMock = $this->createRealStatementMock();
        $this->reflectionSetProperty('_result',
                                     $this->getMockBuilder(mysqli_result::class)
                                          ->disableOriginalConstructor()
                                          ->getMock());

        $this->setExpectedExceptionRegExp(MysqliException::class);
        $this->statementMock->fetchAll('asd');
    }

    /**
     * @test
     */
    public function fetchAll_withoutResult () {
        $this->statementMock = $this->getMockBuilder(MysqliStatement::class)
                                    ->setConstructorArgs([
                                                             $this->createMysqliMock(),
                                                             ''
                                                         ])
                                    ->setMethods(['prepareKey'])
                                    ->getMock();

        $this->reflectionSetProperty('fetchMode', PDO::FETCH_ASSOC);
        $this->setExpectedExceptionRegExp(MysqliException::class);
        $this->statementMock->fetchAll();
    }

    /**
     * @test
     */
    public function fetchAll_withInvalidDefaultType () {
        $this->createFetchModeMock('fetch_all', MYSQLI_NUM);
        $this->statementMock->setFetchMode(PDO::FETCH_ASSOC);
        $this->reflectionSetProperty('fetchMode', PDO::FETCH_NUM);

        $this->statementMock->fetchAll();
    }

    /**
     * @test
     */
    public function fetchAll () {
        $this->createFetchModeMock('fetch_all', MYSQLI_ASSOC);
        $this->reflectionSetProperty('fetchMode', PDO::FETCH_ASSOC);

        $this->assertSame(['test'], $this->statementMock->fetchAll());
    }

    /**
     * @test
     */
    public function fetchAll_assoc () {
        $this->createFetchModeMock('fetch_all', MYSQLI_ASSOC);

        $this->assertSame(['test'], $this->statementMock->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @test
     */
    public function fetchAll_both () {
        $this->createFetchModeMock('fetch_all', MYSQLI_BOTH);

        $this->assertSame(['test'], $this->statementMock->fetchAll(PDO::FETCH_BOTH));
    }

    /**
     * @test
     */
    public function fetchAll_num () {
        $this->createFetchModeMock('fetch_all', MYSQLI_NUM);

        $this->assertSame(['test'], $this->statementMock->fetchAll(PDO::FETCH_NUM));
    }

    /**
     * @test
     */
    public function prepare_withoutSqlParameters () {

        $this->assertSame('test', $this->reflectionMethod('prepare', ['test']));
    }

    /**
     * @test
     */
    public function prepare_withInvalidRegexResult () {
        $this->statementMock = $this->createRealStatementMock(['searchParams']);
        $this->statementMock->expects($this->once())
                            ->method('searchParams')
                            ->with('test')
                            ->will($this->returnValue([0 => 1, 1 => []]));
        $this->assertSame('test', $this->reflectionMethod('prepare', ['test']));

        $this->statementMock = $this->createRealStatementMock(['searchParams']);
        $this->statementMock->expects($this->once())
                            ->method('searchParams')
                            ->with('test')
                            ->will($this->returnValue([1 => 1]));
        $this->assertSame('test', $this->reflectionMethod('prepare', ['test']));
    }

    private function reflectionGetProperty ($name) {
        $reflectionProperty = $this->statementReflection->getProperty($name);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($this->statementMock);
    }

    /**
     * @test
     */
    public function prepare_withSqlParameters () {
        $this->assertSame('SELECT * FROM parameter WHERE param1 = ? AND param2 = ? AND param3 = ? AND param4 = ?',
                          $this->reflectionMethod('prepare',
                                                  ['SELECT * FROM parameter WHERE param1 = :param1 AND param2 = :param3 AND param3 = :param2 AND param4 = :param2']));

        $this->assertSame([':param1', ':param3', ':param2', ':param2'],
                          $this->reflectionGetProperty('parameters'));

        $this->assertSame([':param1' => null, ':param3' => null, ':param2' => null],
                          $this->reflectionGetProperty('values'));

        $this->assertSame([':param1' => 's', ':param3' => 's', ':param2' => 's'],
                          $this->reflectionGetProperty('_types'));
    }

    /**
     * @test
     */
    public function execute_withoutArguments () {
        $this->statementMock = $this->createRealStatementMock();

        $mysqliStatementMock = $this->createMysqliStatementExecuteMock();

        $mysqliStatementMock->expects($this->once())
                            ->method('get_result')
                            ->will($this->returnValue('test'));


        $this->assertSame(true, $this->statementMock->execute());

        $this->assertSame('test', $this->reflectionGetProperty('_result'));
    }

    /**
     * @test
     */
    public function execute_withExecuteError () {
        $this->statementMock = $this->createRealStatementMock(['getConnectionError']);

        $this->statementMock->expects($this->once())
                            ->method('getConnectionError')
                            ->will($this->returnValue('test'));

        $this->createMysqliStatementExecuteMock(false);

        $this->setExpectedExceptionRegExp(MysqliException::class);
        $this->statementMock->execute();
    }

    /**
     * @test
     */
    public function execute_withBindParamError () {
        $this->statementMock = $this->createRealStatementMock(['getConnectionError', 'call_bind_param']);

        $mysqliStatementMock = $this->getMockBuilder(mysqli_stmt::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->statementMock->expects($this->once())
                            ->method('getConnectionError')
                            ->will($this->returnValue('test'));
        $this->statementMock->expects($this->once())
                            ->method('call_bind_param')
                            ->will($this->returnValue(false));

        $this->reflectionSetProperty('parameters', ['param1']);
        $this->reflectionSetProperty('_types', ['param1' => 's']);


        $this->reflectionSetProperty('_statement', $mysqliStatementMock);

        $this->setExpectedExceptionRegExp(MysqliException::class);
        $this->statementMock->execute();
    }

    /**
     * @test
     */
    public function execute_withBindParams () {
        $this->statementMock = $this->createRealStatementMock(['call_bind_param']);

        $this->statementMock->expects($this->once())
                            ->method('call_bind_param')
                            ->will($this->returnValue(true));

        $this->reflectionSetProperty('parameters', ['param1', 'param2', 'param1']);
        $this->reflectionSetProperty('_types', ['param1' => 's', 'param2' => 'i']);

        $this->createMysqliStatementExecuteMock();

        $this->statementMock->execute();

        $this->assertSame(true, $this->reflectionGetProperty('bindedParams'));
    }

    /**
     * @test
     */
    public function execute_withArguments () {
        $this->statementMock = $this->createRealStatementMock(['call_bind_param']);

        $this->statementMock->expects($this->once())
                            ->method('call_bind_param')
                            ->will($this->returnValue(true));

        $this->reflectionSetProperty('bindedParams', true);
        $this->reflectionSetProperty('parameters', [':param1', ':param2', ':param1']);
        $this->reflectionSetProperty('_types', [':param1' => 's', ':param2' => 'i']);
        $this->reflectionSetProperty('values', [':param2' => 'test2']);

        $this->createMysqliStatementExecuteMock();

        $this->statementMock->execute(['param1' => 'test']);

        $this->assertArraySubset([':param2' => 'test2', ':param1' => 'test'], $this->reflectionGetProperty('values'));
    }

    /**
     * @test
     */
    public function rowCount () {
        $this->createStatementWithCountMock('getResultRowCount');

        $resultMock = $this->createMysqliResultMock();

        $this->reflectionSetProperty('_result', $resultMock);

        $this->assertSame(4, $this->statementMock->rowCount());
    }

    /**
     * @test
     */
    public function rowCount_withoutResult () {
        $this->statementMock = $this->createRealStatementMock(['getResultRowCount']);

        $this->assertSame(0, $this->statementMock->rowCount());
    }

    /**
     * @test
     */
    public function columnCount () {
        $this->createStatementWithCountMock('getResultFieldCount');

        $resultMock = $this->createMysqliResultMock();

        $this->reflectionSetProperty('_result', $resultMock);

        $this->assertSame(4, $this->statementMock->columnCount());
    }

    /**
     * @test
     */
    public function columnCount_withoutResult () {
        $this->statementMock = $this->createRealStatementMock(['getResultFieldCount']);

        $this->assertSame(0, $this->statementMock->columnCount());
    }

    /**
     * @test
     */
    public function bindValue () {
        $this->statementMock = $this->createRealStatementMock();
        $this->reflectionSetProperty('bindedParams', true);

        $this->assertSame(true, $this->statementMock->bindValue('param1', 'value1'));
        $this->assertSame(false, $this->reflectionGetProperty('bindedParams'));
        $this->assertSame(true, $this->statementMock->bindValue('param2', 'value2', PDO::PARAM_STR));

        $this->assertSame([':param1' => 'value1', ':param2' => 'value2'], $this->reflectionGetProperty('values'));
        $this->assertSame([':param2' => 's'], $this->reflectionGetProperty('_types'));
    }

    /**
     * @test
     */
    public function bindParam () {
        $this->statementMock = $this->createRealStatementMock();
        $this->reflectionSetProperty('bindedParams', true);

        $param1 = 'value1';
        $param2 = 'value2';
        $this->assertSame(true, $this->statementMock->bindParam('param1', $param1));
        $this->assertSame(false, $this->reflectionGetProperty('bindedParams'));
        $this->assertSame(true, $this->statementMock->bindParam('param2', $param2, PDO::PARAM_STR));

        $this->assertSame([':param1' => 'value1', ':param2' => 'value2'], $this->reflectionGetProperty('values'));
        $this->assertSame([':param2' => 's'], $this->reflectionGetProperty('_types'));

        $param1 = 'value3';
        $this->assertSame([':param1' => 'value3', ':param2' => 'value2'], $this->reflectionGetProperty('values'));

        unset($param1);
    }

    /**
     * @test
     */
    public function current () {
        $this->statementMock = $this->createRealStatementMock(['fetch']);

        $this->statementMock->expects($this->once())
                            ->method('fetch')
                            ->will($this->returnValue(['test']));

        $this->assertSame(['test'], $this->statementMock->current());
        $this->assertSame(['test'], $this->statementMock->current());
    }

    /**
     * @test
     */
    public function next () {
        $this->reflectionSetProperty('position', 0);
        $this->statementMock = $this->createRealStatementMock(['fetch']);

        $this->statementMock->expects($this->at(0))
                            ->method('fetch')
                            ->will($this->returnValue(['test']));
        $this->statementMock->expects($this->at(1))
                            ->method('fetch')
                            ->will($this->returnValue(['test2']));

        $this->assertSame(['test'], $this->statementMock->next());
        $this->assertSame(1, $this->reflectionGetProperty('position'));
        $this->assertSame(['test2'], $this->statementMock->next());
        $this->assertSame(2, $this->reflectionGetProperty('position'));
    }

    /**
     * @test
     */
    public function key () {
        $this->statementMock = $this->createRealStatementMock();

        $this->reflectionSetProperty('position', 3);

        $this->assertSame(3, $this->statementMock->key());
    }

    /**
     * @test
     */
    public function valid () {
        $this->statementMock = $this->createRealStatementMock(['getResultRowCount']);

        $this->statementMock->expects($this->any())
                            ->method('getResultRowCount')
                            ->will($this->returnValue(4));

        $this->reflectionSetProperty('position', -1);
        $this->assertSame(false, $this->statementMock->valid());

        $this->reflectionSetProperty('position', 0);
        $this->assertSame(true, $this->statementMock->valid());

        $this->reflectionSetProperty('position', 3);
        $this->assertSame(true, $this->statementMock->valid());

        $this->reflectionSetProperty('position', 4);
        $this->assertSame(false, $this->statementMock->valid());
    }

    /**
     * @test
     */
    public function rewind () {
        $this->statementMock = $this->createRealStatementMock();

        $result = $this->getMockBuilder(mysqli_result::class)
                       ->disableOriginalConstructor()
                       ->getMock();
        $result->expects($this->once())
               ->method('data_seek')
               ->with(0)
               ->will($this->returnValue(0));

        $this->reflectionSetProperty('position', 4);
        $this->reflectionSetProperty('_result', $result);

        $this->statementMock->rewind();

        $this->assertSame(0, $this->reflectionGetProperty('position'));
    }

    /**
     * @test
     */
    public function prepareKeys () {
        $this->assertSame([':parameter2' => 'value2', ':parameter3' => ':value3', ':parameter1' => 'value1'],
                          $this->reflectionMethod('prepareKeys',
                                                  [['parameter1' => 'value1',
                                                    ':parameter2' => 'value2',
                                                    ':parameter3' => ':value3']]));
    }

    /**
     * @test
     */
    public function prepareKey () {
        $this->assertSame(':parameter', $this->reflectionMethod('prepareKey', ['parameter']));
        $this->assertSame(':parameter', $this->reflectionMethod('prepareKey', [':parameter']));
        $this->assertSame(':pa:rameter', $this->reflectionMethod('prepareKey', ['pa:rameter']));
        $this->assertSame(':parameter:', $this->reflectionMethod('prepareKey', ['parameter:']));
    }

    /**
     * @test
     */
    public function loadStreamParams () {
        $this->reflectionSetProperty('_types', ['param1' => 'b', 'param2' => 'b']);
        $this->reflectionSetProperty('parameters', ['param1', 'param2']);
        $this->reflectionSetProperty('values', ['param1' => 'value1', 'param2' => 'value2']);

        $statement = $this->getMockBuilder(mysqli_stmt::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $statement->expects($this->exactly(2))
                  ->method('send_long_data')
                  ->will($this->returnValueMap([[0, 'value1', null], [1, 'value2', null]]));

        $this->reflectionSetProperty('_statement', $statement);

        $this->reflectionMethod('loadStreamParams');
    }

    /**
     * @test
     */
    public function loadStreamParams_withEmptyTypes () {
        $this->reflectionSetProperty('_types', []);

        $this->reflectionMethod('loadStreamParams');
    }

    /**
     * @test
     */
    public function _bindArguments () {
        $this->reflectionSetProperty('parameters', ['param1', 'param2', 'param3']);
        $this->reflectionSetProperty('_types', ['param1' => 's', 'param2' => 'i', 'param3' => 'b']);

        $mapArray = '';
        $mapTypes = $this->reflectionMethod('_bindArguments', [&$mapArray]);
        $this->assertSame([0 => null, 1 => null, 2 => null], $mapArray);
        $this->assertSame('sib', $mapTypes);
    }

    /**
     * @test
     */
    public function _bindArguments_withoutValues () {
        $this->reflectionSetProperty('parameters', []);
        $this->reflectionSetProperty('_types', []);

        $mapArray = '';
        $mapTypes = $this->reflectionMethod('_bindArguments', [&$mapArray]);
        $this->assertSame([], $mapArray);
        $this->assertSame('', $mapTypes);
    }

    /**
     * @test
     */
    public function noResultException () {
        $this->setExpectedExceptionRegExp(MysqliException::class);
        $this->reflectionMethod('noResultException');
    }

    /**
     * @test
     */
    public function noResultException_withoutException () {
        $result = $this->getMockBuilder(mysqli_result::class)
                       ->disableOriginalConstructor()
                       ->getMock();
        $this->reflectionSetProperty('_result', $result);
        $this->assertSame(true, $this->reflectionMethod('noResultException'));
    }

    /**
     * @test
     */
    public function getFetchMode () {
        $this->reflectionSetProperty('fetchMode', 2);
        $this->assertSame(2, $this->reflectionMethod('getFetchMode', [null]));
        $this->assertSame(0, $this->reflectionMethod('getFetchMode', [0]));
        $this->assertSame(3, $this->reflectionMethod('getFetchMode', [3]));
    }

    private function createFetchModeMock ($method, $param = null) {
        $this->statementMock = $this->getMockBuilder(MysqliStatement::class)
                                    ->setConstructorArgs([
                                                             $this->createMysqliMock(),
                                                             ''
                                                         ])
                                    ->setMethods(['prepareKey'])
                                    ->getMock();

        $resultMock = $this->getMockBuilder(mysqli_result::class)
                           ->disableOriginalConstructor()
                           ->getMock();
        if ($param == null) {
            $resultMock->expects($this->once())
                       ->method($method)
                       ->will($this->returnValue(['test']));
        } else {
            $resultMock->expects($this->once())
                       ->method($method)
                       ->with($param)
                       ->will($this->returnValue(['test']));
        }

        $this->reflectionSetProperty('_result', $resultMock);
    }

    private function reflectionSetProperty ($property, $value) {
        $reflectionProperty = $this->statementReflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->statementMock, $value);
    }

    private function reflectionMethod ($method, array $arguments = []) {
        $prepareKey = $this->statementReflection->getMethod($method);
        $prepareKey->setAccessible(true);

        return $prepareKey->invokeArgs($this->statementMock, $arguments);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|mysqli
     */
    private function createMysqliMock () {
        return $this->getMockBuilder(mysqli::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /**
     * create a mock object which call the real public functions
     *
     * @param array                                               $methods
     * @param null|PHPUnit_Framework_MockObject_MockObject|mysqli $mysqli
     *
     * @return PHPUnit_Framework_MockObject_MockObject|MysqliStatement
     */
    private function createRealStatementMock ($methods = ['prepareKey'], $mysqli = null) {
        if ($mysqli == null) {
            $mysqli = $this->getMockBuilder(mysqli::class)
                           ->disableOriginalConstructor()
                           ->getMock();
        }

        return $this->getMockBuilder(MysqliStatement::class)
                    ->setConstructorArgs([
                                             $mysqli,
                                             ''
                                         ])
                    ->setMethods($methods)
                    ->getMock();
    }

    private function createMysqliStatementExecuteMock ($return = true) {
        $mysqliStatementMock = $this->getMockBuilder(mysqli_stmt::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $mysqliStatementMock->expects($this->once())
                            ->method('execute')
                            ->will($this->returnValue($return));

        $statementProperty = $this->statementReflection->getProperty('_statement');
        $statementProperty->setAccessible(true);
        $statementProperty->setValue($this->statementMock, $mysqliStatementMock);

        return $mysqliStatementMock;
    }

    private function createStatementWithCountMock ($method) {
        $this->statementMock = $this->createRealStatementMock([$method]);

        $this->statementMock->expects($this->once())
                            ->method($method)
                            ->will($this->returnValue(4));
    }

    private function createMysqliResultMock () {
        return $this->getMockBuilder(mysqli_result::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}