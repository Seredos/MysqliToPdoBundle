<?php
/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 18.04.2016
 * Time: 23:45
 */

namespace database\MysqliToPdoBundle\mysqli;

class MysqliConnection {
    private $connection;
    private $inTransaction;

    public function __construct (\mysqli $connection) {
        $this->connection = $connection;
        $this->inTransaction = false;
    }

    public function prepare ($sql) {
        return new MysqliStatement($this->connection, $sql);
    }

    public function exec ($sql) {
        $statement = new MysqliStatement($this->connection, $sql);
        $statement->execute();

        return mysqli_affected_rows($this->connection);
    }

    public function query ($sql) {
        $statement = new MysqliStatement($this->connection, $sql);
        $statement->execute();

        return $statement;
    }

    public function lastInsertId () {
        return mysqli_insert_id($this->connection);
    }

    public function quote ($string) {
        return '\''.str_replace('\'', "\\'", $string).'\'';
    }

    public function commit () {
        $this->inTransaction = false;

        return $this->connection->commit();
    }

    public function beginTransaction () {
        $this->inTransaction = true;

        return $this->connection->begin_transaction();
    }

    public function rollBack () {
        $this->inTransaction = false;

        return $this->connection->rollback();
    }

    public function inTransaction () {
        return $this->inTransaction;
    }

    public function errorCode () {
        return $this->connection->errno;
    }

    public function errorInfo () {
        return $this->connection->error_list;
    }
}