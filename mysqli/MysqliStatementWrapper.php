<?php
/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 22.04.2016
 * Time: 23:59
 */

namespace database\MysqliToPdoBundle\mysqli;


use mysqli_result;

abstract class MysqliStatementWrapper {
    const LENGTH = 10;

    /**
     * @var \mysqli
     */
    protected $_connection;
    /**
     * @var \mysqli_stmt
     */
    protected $_statement;
    /**
     * @var mysqli_result|null
     */
    protected $_result;

    public function __construct (\mysqli $connection) {
        $this->_connection = $connection;
        $this->_result = null;
        $this->_statement = null;
    }

    public function errorCode () {
        return $this->_connection->errno;
    }

    public function errorInfo () {
        return $this->_connection->error_list;
    }

    protected function getConnectionError () {
        return $this->_connection->error;
    }

    protected function getResultFieldCount () {
        return $this->_result->field_count;
    }

    protected function getResultRowCount () {
        return $this->_result->num_rows;
    }

    protected function call_bind_param ($typesString, $mappedValues) {
        return call_user_func_array([$this->_statement, 'bind_param'], array_merge([$typesString], $mappedValues));
    }

    protected function send_resource_data ($index, $value) {
        if (is_resource($value)) {
            while (!feof($value)) {
                $this->_statement->send_long_data($index, fread($value, self::LENGTH));
            }

            return true;
        }

        return false;
    }

    protected function searchParams ($sql) {
        preg_match_all("/:([a-zA-Z0-9]*)/", $sql, $regexParams);

        return $regexParams;
    }
}