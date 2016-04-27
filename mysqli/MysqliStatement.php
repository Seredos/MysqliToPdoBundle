<?php
/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 18.04.2016
 * Time: 21:04
 */

namespace database\MysqliToPdoBundle\mysqli;


use mysqli_result;
use PDO;

class MysqliStatement extends MysqliStatementWrapper implements \Iterator {
    const PARAM_TYPES = [
        PDO::PARAM_STR => 's',
        PDO::PARAM_BOOL => 'i',
        PDO::PARAM_NULL => 's',
        PDO::PARAM_INT => 'i',
        PDO::PARAM_LOB => 'b'
    ];
    /**
     * @var array
     */
    private $parameters;
    /**
     * @var string
     */
    private $sql;
    /**
     * @var array
     */
    private $values;

    /**
     * @var int
     */
    private $fetchMode;
    /**
     * @var bool
     */
    private $bindedParams;

    /**
     * @var string[]
     */
    private $_types;

    /**
     * @var mixed|null
     */
    private $current = null;
    /**
     * @var int
     */
    private $position = 0;

    /**
     * DbiStatement constructor.
     *
     * @param \mysqli $connection
     * @param string  $sql
     *
     * @throws MysqliException
     */
    public function __construct (\mysqli $connection, $sql) {
        parent::__construct($connection);
        $this->values = [];
        $this->parameters = [];
        $this->_types = '';
        $this->bindedParams = false;
        $this->fetchMode = PDO::FETCH_BOTH;

        $this->sql = $this->prepare($sql);

        $this->_statement = $this->_connection->prepare($this->sql);
        if (false === $this->_statement) {
            throw new MysqliException($this->getConnectionError());
        }
    }

    /**
     * Return the current element
     * @link  http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current () {
        if ($this->current == null) {
            $this->current = $this->fetch();
        }

        return $this->current;
    }

    /**
     * Move forward to next element
     * @link  http://php.net/manual/en/iterator.next.php
     * @return mixed
     * @since 5.0.0
     */
    public function next () {
        $this->current = $this->fetch();
        $this->position++;

        return $this->current;
    }

    /**
     * Return the key of the current element
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key () {
        return $this->position;
    }

    /**
     * Checks if current position is valid
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid () {
        if ($this->position >= 0 && $this->position < $this->getResultRowCount()) {
            return true;
        }

        return false;
    }

    /**
     * Rewind the Iterator to the first element
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind () {
        $this->position = 0;
        $this->_result->data_seek(0);
    }

    /**
     * @param string $key
     * @param        $variable
     * @param null   $type
     *
     * @return bool
     */
    public function bindParam ($key, &$variable, $type = null) {
        $key = $this->prepareKey($key);

        $this->values[$key] =& $variable;
        if ($type != null) {
            $this->_types[$key] = self::PARAM_TYPES[$type];
        }
        $this->bindedParams = false;

        return true;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param null   $type
     *
     * @return bool
     */
    public function bindValue ($key, $value, $type = null) {
        $key = $this->prepareKey($key);
        $this->values[$key] = $value;
        if ($type != null) {
            $this->_types[$key] = self::PARAM_TYPES[$type];
        }
        $this->bindedParams = false;

        return true;
    }

    /**
     * @param null|array $parameters
     *
     * @return bool
     * @throws MysqliException
     */
    public function execute ($parameters = null) {
        //if new params are binded, or parameters are setted on the execute, they must binded to mysqli
        if ((!$this->bindedParams || is_array($parameters)) && count($this->parameters) > 0) {
            if (is_array($parameters)) {
                $this->values = array_merge($this->values, $this->prepareKeys($parameters));
            }

            $typesString = $this->_bindArguments($mappedValues);

            if (!$this->call_bind_param($typesString, $mappedValues)) {
                throw new MysqliException($this->getConnectionError());
            }

            $this->loadStreamParams();
            $this->bindedParams = true;
        }

        if (!$this->_statement->execute()) {
            throw new MysqliException($this->getConnectionError());
        }
        $this->_result = $this->_statement->get_result();

        return true;
    }

    /**
     * @param int $type
     *
     * @return array|mixed
     * @throws MysqliException
     */
    public function fetch ($type = null) {
        if ($this->noResultException()) {
            $type = $this->getFetchMode($type);
            switch ($type) {
                case PDO::FETCH_BOTH:
                    return $this->_result->fetch_array();
                    break;
                case PDO::FETCH_ASSOC:
                    return $this->_result->fetch_assoc();
                    break;
                case PDO::FETCH_NUM:
                    return $this->_result->fetch_array(MYSQLI_NUM);
                    break;
                case PDO::FETCH_OBJ:
                    return $this->_result->fetch_object();
                    break;
            }
        }
        throw new MysqliException('invalid fetch type!');
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function setFetchMode ($type) {
        $this->fetchMode = $type;

        return true;
    }

    /**
     * @param int $type
     *
     * @return array|mixed
     * @throws MysqliException
     */
    public function fetchAll ($type = null) {
        if ($this->noResultException()) {
            $type = $this->getFetchMode($type);
            switch ($type) {
                case PDO::FETCH_BOTH:
                    return $this->_result->fetch_all(MYSQLI_BOTH);
                    break;
                case PDO::FETCH_ASSOC:
                    return $this->_result->fetch_all(MYSQLI_ASSOC);
                    break;
                case PDO::FETCH_NUM:
                    return $this->_result->fetch_all(MYSQLI_NUM);
                    break;
            }
        }
        throw new MysqliException('invalid fetch type!');
    }

    /**
     * @return int
     */
    public function columnCount () {
        if ($this->_result instanceof mysqli_result) {
            return $this->getResultFieldCount();
        }

        return 0;
    }

    /**
     * @return int
     */
    public function rowCount () {
        if ($this->_result instanceof mysqli_result) {
            return $this->getResultRowCount();
        }

        return 0;
    }

    /**
     * create an array with the bind values and the type-string for the mysqli bind_param function
     *
     * @param $mappedValues
     *
     * @return string
     */
    private function _bindArguments (&$mappedValues) {
        $typesString = '';
        $mappedValues = [];

        foreach ($this->parameters as $param) {
            $typesString .= $this->_types[$param];
            $mappedValues[] =& $this->getValue($param);
        }

        return $typesString;
    }

    private function & getValue ($key) {
        return $this->values[$key];
    }

    private function getFetchMode ($mode) {
        if ($mode === null) {
            return $this->fetchMode;
        }

        return $mode;
    }

    private function noResultException () {
        if (!($this->_result instanceof mysqli_result)) {
            throw new MysqliException('result not found. maybe the statement was not executed?');
        }

        return true;
    }

    /**
     * all params which defined as PARAM_LOB will be send with send_long_data. if the binded value a resource they will
     * be streamed to the database
     */
    private function loadStreamParams () {
        if (in_array(self::PARAM_TYPES[PDO::PARAM_LOB], $this->_types)) {
            foreach ($this->_types as $param => $type) {
                if ($type == self::PARAM_TYPES[PDO::PARAM_LOB]) {
                    $paramIndex = array_search($param, $this->parameters);
                    if (!$this->send_resource_data($paramIndex, $this->values[$param])) {
                        $this->_statement->send_long_data($paramIndex, $this->values[$param]);
                    }
                }
            }
        }
    }

    /**
     * replace all prepared params with ?, hold the parameters in the query order and set the values for the query to
     * null
     *
     * @param $sql
     *
     * @return mixed
     */
    private function prepare ($sql) {
        $regexParams = $this->searchParams($sql);
        if (isset($regexParams[0]) && is_array($regexParams[0])) {
            $this->parameters = $regexParams[0];
            foreach ($this->parameters as $param) {
                $sql = str_replace($param, '?', $sql);
                $this->values[$param] = null;
                $this->_types[$param] = self::PARAM_TYPES[PDO::PARAM_STR];
            }
        }

        return $sql;
    }

    /**
     * add a : to all param keys of the array, if they not have an, on the firt character
     *
     * @param array $keys
     *
     * @return array
     */
    private function prepareKeys (array $keys) {
        foreach ($keys as $key => $value) {
            $oldKey = $key;
            $key = $this->prepareKey($key);
            $keys[$key] = $value;
            if ($key != $oldKey) {
                unset($keys[$oldKey]);
            }
        }

        return $keys;
    }

    /**
     * add an : to key, if not set on first character
     *
     * @param $key
     *
     * @return string
     */
    private function prepareKey ($key) {
        if (substr($key, 0, 1) != ':') {
            $key = ':'.$key;
        }

        return $key;
    }
}