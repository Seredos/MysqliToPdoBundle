<?php
/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 20.04.2016
 * Time: 19:33
 */

namespace database\MysqliToPdoBundle\Tests\mysqli;

use database\MysqliToPdoBundle\mysqli\MysqliConnection;
use PDO;

class ConnectionTestBuilder {
    /**
     * @param PDO|MysqliConnection $connection
     * @param string               $table
     * @param array                $values
     *
     * @return int
     */
    public function createBindValueInsert ($connection, $table, $values = []) {
        $sqlInsert = 'INSERT INTO '.$table.' (`'.implode('`,`', array_keys($values)).'`)';
        $sqlInsert .= ' VALUES (:'.implode(',:', array_keys($values)).')';

        $statement = $connection->prepare($sqlInsert);
        foreach ($values as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();

        return $connection->lastInsertId();
    }

    /**
     * bind the params in another order as they are in the query
     *
     * @param PDO|MysqliConnection           $connection
     * @param                                $table
     * @param                                $values
     *
     * @return int|null
     */
    public function createBindValueInsertWithOrder ($connection, $table, $values) {
        $sqlInsert = 'INSERT INTO '.$table.' (`'.implode('`,`', array_keys($values)).'`)';
        $sqlInsert .= ' VALUES (:'.implode(',:', array_keys($values)).')';

        $statement = $connection->prepare($sqlInsert);
        foreach (array_reverse($values) as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();

        return $connection->lastInsertId();
    }

    /**
     * @param PDO|MysqliConnection $connection
     * @param string               $table
     * @param string               $columns
     *
     * @return array
     */
    public function createSelect ($connection, $table, $columns = '*') {
        $sql = 'SELECT '.$columns.' FROM '.$table;
        $statement = $connection->prepare($sql);
        $result = [];

        $statement->execute();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * @param PDO|MysqliConnection $connection
     * @param mixed                $value
     *
     * @return mixed
     */
    public function createNumeric ($connection, $value) {
        $statement = $connection->prepare('INSERT INTO int_table(`value`) VALUES(:value)');
        $statement->bindValue('value', $value, PDO::PARAM_INT);
        $statement->execute();

        return $connection->lastInsertId();
    }

    /**
     * @param PDO|MysqliConnection $connection
     * @param mixed                $value
     *
     * @return mixed
     */
    public function createBlob ($connection, $value) {
        $statement = $connection->prepare('INSERT INTO blob_table(`value`) VALUES(:value)');
        $statement->bindValue('value', $value, PDO::PARAM_LOB);
        $statement->execute();

        return $connection->lastInsertId();
    }
}