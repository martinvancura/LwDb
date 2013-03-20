<?php
/**
 * LwDbConnection
 * Single pdo database connection class
 *
 * @package de.lectura.lw.core
 * @author  Petr Brabac <pb@lectura.de>
 */
class LwDbConnection
{
    const RETURN_STD = "std";
    const RETURN_ARRAY = "array";
    /**
     * PDO connection
     */
    private $connection;

    /**
     * Establish database connection
     *
     * @param String $dsn      A data source name
     * @param String $user     A database user name
     * @param String $password A database user password
     *
     * @throws Exception
     * @return \LwDbConnection
     */
    public function __construct($dsn, $user, $password)
    {
        try {
            $this->connection = new PDO($dsn, $user, $password);
            $this->connection->exec("SET NAMES utf8");
        } catch (PDOException $e) {
            throw new Exception("Database connection error! " . $e->getMessage());
        }
    }

    /**
     * Executes query for current connection and returns result
     *
     * @param string $query      A query to execute
     * @param array  $params     Paramters to bind
     * @param string $returnType A return type one of the class constants
     *
     * @throws Exception
     * @return mixed
     */
    public function execute($query, $params = array(), $returnType = self::RETURN_STD) {
        $result = null;
        $stmt = $this->connection->prepare($query);
        $check = $stmt->execute($params);

        if ($check === false) {
            $error = $stmt->errorInfo();
            
            throw new Exception("Running sql: " . $query . " A database error occured. Details: ". $error[0] . " " . $error[1] . " " . $error[2], (int)$error[1]);
        }


        if ($returnType === self::RETURN_STD) {
            $result = array();
            while($obj = $stmt->fetchObject()) {
                $result[] = $obj;
            }
        } else {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    /**
     * excuteInsertStmt
     * executes query without watching for return values
     *
     * @param string $query  A query
     * @param array  $params Query parameters
     *
     * @throws Exception
     *
     * @return void
     */
    public function executeInsertStmt($query, $params = array()) {
        $stmt = $this->connection->prepare($query);
        $check = $stmt->execute($params);

        if ($check === false) {
            $error = $stmt->errorInfo();

            throw new Exception("Running sql: " . $query . " A database error occured. Details: ". $error[0] . " " . $error[1] . " " . $error[2], (int)$error[0]);
        }
    }

    /**
     * Executes query for current connection and return single result
     *
     * @param string $query      A query to execute
     * @param array  $params     Paramters to bind
     * @param string $returnType A return type one of the class constants
     *
     * @return mixed null|stdClass
     */
    public function executeOne($query, $params = array(), $returnType = self::RETURN_STD) {
        $result = $this->execute($query, $params, $returnType);
        if (count($result) > 0) {
            $result = $result[0];
        } else {
            $result = null;
        }
            
        return $result;
    }
    
    /**
     * executeSave
     * Performs insert or update query based on $type
     *
     * @param mixed  $params      An array of params or stdClass
     * @param string $table       A table name
     * @param int    $type        One of LwSaveQueryFactory constants
     * @param string $whereClause for update queries
     *
     * @return void
     */
    public function executeSave($params, $table, $type, $whereClause = "")
    {
        if ($params instanceof stdClass) {
            $params = get_object_vars($params);
        }

        $queryObject = LwSaveQueryFactory::getQuery($params, $type, $whereClause);
        $queryObject->setTable($table);

        $this->executeInsertStmt($queryObject, $params);
    }

    /**
     * beginTransaction
     * Starts database transaction with throwing exceptions
     *
     * @return void
     */
    public function beginTransaction() {
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->beginTransaction();
    }

    /**
     * commitTransaction
     * Run transaction queries and ends transaction
     *
     * @return void
     */
    public function commitTransaction() {
        $this->connection->commit();
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    }

    /**
     * rollback
     * Rolls back current transaction
     *
     * @return void
     */
    public function rollback() {
        $this->connection->rollBack();
    }

    /**
     * getLastInsertedId
     * Returns last inserted id in database
     *
     * @return string
     */
    public function getLastInsertedId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Returns prepared statement, useful for multiple inserts
     *
     * @param string $query
     *
     * @return PDOStatement
     */
    public function prepareStmt($query) {
        return $this->connection->prepare($query);
    }
    
    public function quote($string) {
        $this->connection->quote($string);
    }
}