<?php
/**
 * LwDbConnectionManager
 * Class which take care of handling database connections
 *
 * @package de.lectura.lw.core
 * @author  Petr Brabac <pb@lectura.de>
 */
class LwDbConnectionManager
{
    /**
     * An array of LwDbConnections
     */
    private $connections;
    /**
     * A LwDbConnectionManager object
     */
    private static $instance;

    /**
     * Constructor
     *
     * @return \LwDbConnectionManager
     */
    private function __construct()
    {
        $this->initConnections($this->parseDbConfig());
    }
    
    /**
     * Returns LwDbConnection object with specified identifier
     *
     * @param String $identifier A database connection identifier
     *
     * @return LwDbConnection
     */
    public static function getConnection($identifier = "default")
    {
        if (!self::$instance instanceof LwDbConnectionManager) {
            self::$instance = new LwDbConnectionManager();
        }
        
        if (!array_key_exists($identifier, self::$instance->connections)) {
            throw new Exception("Connection with identifier '" . $identifier . "' doesn't exists in configuration.");
        }
        
        return self::$instance->connections[$identifier];
    }
    
    /**
     * Establish database connections and place them in array
     *
     * @param array $connectionsArray An array of connections params
     *
     * @return void
     */
    private function initConnections($connectionsArray)
    {
        foreach ($connectionsArray as $name => $params) {
            $className = "LwDbConnection";
            
            if (!array_key_exists("user", $params) or !array_key_exists("password", $params)) {
                throw new Exception("You have to specified user and password for database connection.");
            }

            if (array_key_exists("class", $params) and $params["class"] !== "") {
                $className = $params["class"];
            }
            
            $this->connections[$name] = new $className(
                $this->getDsnFromParams($params), 
                $params["user"], 
                $params["password"]
            );
        }
    }

    /**
     * Prepares connections parameters from xml config
     *
     * @return array $dbsConfig
     */
    private function parseDbConfig()
    {
        $confFile = simplexml_load_file(RDIR . "config/database.xml");

        $dbs = array();

        if ($confFile instanceof SimpleXMLElement) {
            foreach ($confFile->database as $db) {
                $conName = (string) $db["name"];

                if ($conName !== "") {
                    $paramsArr = array();

                    foreach ($db->children() as $param) {
                        $paramsArr[$param->getName()] = (string) $param;
                    }

                    if (count($paramsArr) > 0) {
                        $dbs[$conName] = $paramsArr;
                    }
                }
            }
        }

        return $dbs;
    }

    /**
     * Prepares dsn string from config parameters
     *
     * @param array $params An array of connection parameters
     *
     * @return string $dsn
     */
    private function getDsnFromParams($params)
    {
        $dsn = "";
        if (!array_key_exists("type", $params)) {
            throw new Exception("You have to specified database type.");
        }

        if (!array_key_exists("host", $params) or !array_key_exists("db", $params)) {
            throw new Exception("You have to specified host and dbname for database connection.");
        }
        
        $dsn .= $params["type"] . ":host=" . $params["host"] . ";";
        
        if (array_key_exists("port", $params)) {
            $dsn .= "port=" . $params["port"] . ";";
        }
        
        $dsn .= "dbname=" . $params["db"] . ";";

        return $dsn;
    }
}