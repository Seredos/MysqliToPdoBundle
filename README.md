MysqliToPdoBundle
=================
This bundle create a mysqli wrapper to handle database connections like an pdo connection.

Installation
============
add the wrapper bundle in your composer.json as below:
```js
"require": {
    ...
    "Seredos/database/MysqliToPdoBundle" : "dev-master",
},
"repositories" : [
                    ...
                    {
                  			"type" : "vcs",
                  			"url" : "https://github.com/Seredos/MysqliToPdoBundle"
                  		}],
```
and execute the composer update command

Usage
=====
create a connection:
```php
use database\MysqliToPdoBundle\mysqli\MysqliConnection;
...
$mysqli = new mysqli($server, $user, $password, $name);
    
$mysqliConnection = new MysqliConnection($mysqli);
```

create a statement:
```php
$statement = $mysqliConnection->prepare('SELECT * FROM table WHERE column = :param1 AND column = :param2');
$executedStatement = $mysqliConnection->query('SELECT * FROM table');
```

execute a statement:
```php
$statement->bindParam('param1',$param1);    //bind the variable reference
$statement->bindValue('param2','value');
$statement->execute();
```

save stream data:
```php
$fp = fopen('path/to/file.ext');
$statement->bindParam('param1',$fp,PDO::PARAM_LOB);
```

loop over results:
```php
while($row = $executedStatement->fetch(PDO::FETCH_ASSOC)){
    ...
}
```

```php
$executedStatement->setFetchMode(PDO::FETCH_ASSOC);
foreach($executedStatement as $row){
    ...
}
```

Features
========

differences between Pdo, MysqliConnection and Mysqli:

| Compatible | PDO                          | MysqliConnection            |
|------------|------------------------------|-----------------------------|
|supported|bool beginTranaction()        |bool beginTransaction()      |
|supported|bool commit()                 |bool commit()                |
|supported|mixed errorCode()             |mixed errorCode()            |
|supported|array errorInfo()             |array errorInfo()            |
|supported|int exec($sql)                |int exec($sql)               |
|supported|bool inTransaction()          |bool inTransaction()         |
|supported|bool rollBack()               |bool rollBack()              |
|supported|PDOStatement query($sql)      |MysqliStatement query($sql)  |
|partially supported|string lastInsertId($name)    |string lastInsertId()        |
|partially supported|PDOStatement prepare($sql, [])|MysqliStatement prepare($sql)|
|partially supported|string quote($string, $type)     |string quote($string)        |
|not supported|getAttribute($attr)           |                             |
|not supported|setAttribute($attr,$value)    |                             |

differences between PdoStatement, MysqliStatementIterator and mysqli_stmt

| Compatible | PdoStatement | MysqliStatement |
|------------|--------------|-----------------|
|supported|int columnCount()|int columnCount()|
|supported|int rowCount()|int rowCount()|
|supported|bool bindValue($param,$value,$type)|bindValue($param,$value,$type)|
|supported|bool execute($params)|bool execute($params)|
|supported|string errorCode()|string errorCode()|
|supported|array errorInfo()|array errorInfo()|
|supported|bool setFetchMode($type)|bool setFetchMode($type)|
|partially supported|bool bindParam($param,&$var,$type,$length,$options)|bindParam($param,&$value,$type)|
|partially supported|mixed fetch($type,$ori,$off)|mixed fetch($type)|
|partially supported|array fetchAll($type,$arg,$ct)|array fetchAll($type)|
|not supported|bool bindColumn($col,$param,$type,$len,$options)| |
|not supported|bool closeCursor()| |
|not supported|debugDumpParams()| |
|not supported|mixed fetchColumn($num)| |
|not supported|mixed fetchObject($class,$ctor)| |
|not supported|getAttribute($attr)| |
|not supported|setAttribute($attr,$val)| |
|not supported|getColumnMeta($col)| |
|not supported|bool nextRowset()| |

supported fetch types are:
* PDO::FETCH_BOTH
* PDO::FETCH_ASSOC
* PDO::FETCH_NUM
* PDO::FETCH_OBJ

Tests
=====
to run the unit tests call
```js
phpunit --configuration [path/to/MysqliToPdoBundle]/phpunit.xml --verbose --bootstrap=[path/to/your/autoload.php]
```
to run the functional tests, you must setup your database-configuration in the Tests/BaseDatabaseSetup.php and remove the $this->markTestSkipped() row.
