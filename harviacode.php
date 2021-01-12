<?php

/*  SQL Server version
    2020
    zhiewq
*/

class Harviacode
{

    private $host;
    private $user;
    private $password;
    private $database;
    private $sql;

    function __construct()
    {
        $this->connection();
    }

    function connection()
    {
        $subject = file_get_contents('../application/config/database.php');
        $string = str_replace("defined('BASEPATH') OR exit('No direct script access allowed');", "", $subject);
        
        $con = 'core/connection.php';
        $create = fopen($con, "w") or die("Change your permision folder for application and harviacode folder to 777");
        fwrite($create, $string);
        fclose($create);
        
        require $con;

        $this->host = $db['default']['hostname'];
        $this->user = $db['default']['username'];
        $this->password = $db['default']['password'];
        $this->database = $db['default']['database'];
        try {
            $this->sql = new PDO("sqlsrv:server=".$this->host.";Database=".$this->database, $this->user, $this->password);
            if ($this->sql->connect_error)
            {
                echo $this->sql->connect_error . ", please check 'application/config/database.php'.";
                die();
            }
        }
        catch(PDOException $e) {
            die("Error connecting to SQL Server: " . $e->getMessage());
        }
        
        unlink($con);
    }

    function table_list()
    {
        $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_CATALOG=?";
        $stmt = $this->sql->prepare($query) OR die("Error code :" . $this->sql->errno . " (not_primary_field)");
        $stmt->bindValue(1, $this->database, PDO::PARAM_STR);
        $stmt->execute();
        
        while ($row = $stmt->fetch()) {
            $fields[] = array('table_name' => $row['TABLE_NAME']);
        }
        
        return $fields;
        $stmt->close();
        $this->sql->close();
    }

    function primary_field($table)
    {
        $query = "SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + '.' + QUOTENAME(CONSTRAINT_NAME)), 'IsPrimaryKey') = 1
                    AND TABLE_CATALOG =?
                    AND TABLE_NAME=?";
        $stmt = $this->sql->prepare($query) OR die("Error code :" . $this->sql->errno . " (primary_field)");
        $stmt->bindValue(1, $this->database, PDO::PARAM_STR);
        $stmt->bindValue(2, $table, PDO::PARAM_STR);
        $stmt->execute();
        $getRow = $stmt->fetch();
        $column_name = $getRow['COLUMN_NAME'];

        return $column_name;
        $stmt->close();
        $this->sql->close();
    }

    function not_primary_field($table)
    {
        $query = "SELECT COLUMN_NAME, '' AS COLUMN_KEY, DATA_TYPE
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE COLUMN_NAME 
                    NOT IN 
                        (SELECT COLUMN_NAME
                            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                            WHERE OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + '.' + QUOTENAME(CONSTRAINT_NAME)), 'IsPrimaryKey') = 1
                            AND TABLE_CATALOG=?
                            AND TABLE_NAME=?
                        )
                    AND TABLE_CATALOG=?
                    AND TABLE_NAME=?";
        $stmt = $this->sql->prepare($query) OR die("Error code :" . $this->sql->errno . " (not_primary_field)");
        $stmt->bindValue(1, $this->database, PDO::PARAM_STR);
        $stmt->bindValue(2, $table, PDO::PARAM_STR);
        $stmt->bindValue(3, $this->database, PDO::PARAM_STR);
        $stmt->bindValue(4, $table, PDO::PARAM_STR);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $fields[] = array('column_name' => $row['COLUMN_NAME'], 'column_key' => $row['COLUMN_KEY'], 'data_type' => $row['DATA_TYPE']);
        }

        return $fields;
        $stmt->close();
        $this->sql->close();
    }

    function all_field($table)
    {
        $query = "SELECT infoCol.COLUMN_NAME, DATA_TYPE,
                        IIF(OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + '.' + QUOTENAME(CONSTRAINT_NAME)), 'IsPrimaryKey') = 1, 'PRI', '') AS COLUMN_KEY
                    FROM INFORMATION_SCHEMA.COLUMNS infoCol
                    LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE infoColUse 
                        ON (infoColUse.TABLE_NAME=infoCol.TABLE_NAME AND infoColUse.COLUMN_NAME = infoCol.COLUMN_NAME)
                    WHERE infoCol.TABLE_CATALOG=? 
                    AND infoCol.TABLE_NAME=?";
        $stmt = $this->sql->prepare($query) OR die("Error code :" . $this->sql->errno . " (all_field)");
        $stmt->bindValue(1, $this->database, PDO::PARAM_STR);
        $stmt->bindValue(2, $table, PDO::PARAM_STR);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $fields[] = array('column_name' => $row['COLUMN_NAME'], 'column_key' => $row['COLUMN_KEY'], 'data_type' => $row['DATA_TYPE']);
        }

        return $fields;
        $stmt->close();
        $this->sql->close();
    }

}

$hc = new Harviacode();
?>
