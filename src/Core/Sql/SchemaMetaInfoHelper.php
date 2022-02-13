<?php

namespace webayun\upgradeengine\Core\Sql;

use PDO;

class SchemaMetaInfoHelper
{
    private $dbname = "information_schema";
    private $host;
    private $user;
    private $password;
    private $port;

    const TABLE_NAME          = "tableName";
    const TABLE_PRIMARY_KEYS  = "primaryKeys";
    const TABLE_UNIQUE_INDEXS = "uniqueIndexs";
    const TABLE_COLUMNS       = "columns";

    const COLUMN_NAME           = "name";
    const COLUMN_COLUMN_TYPE    = "type";
    const COLUMN_DATA_TYPE      = "dataType";
    const COLUMN_DEFAULT        = "columnDefault";
    const COLUMN_IS_NULLABLE    = "isNullable";
    const COLUMN_AUTO_INCREMENT = "autoIncrement";

    const COLUMN_COMMENT        = "comment";

    /**
     * @var PDO
     */
    private $pdo;

    public function __construct($host, $user, $pass, $port = 3306)
    {
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $pass;
        $this->port     = $port;

        $this->pdo = $this->getPdo($host, $user, $pass, $port);
    }

    private function getPdo($host, $user, $pass, $port = 3306)
    {
        $dbname  = $this->dbname;
        $connStr = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        $conn    = new PDO($connStr, $user, $pass);

        return $conn;
    }

    private function keysFromtConstraintsSql($type = 1)
    {
        if ($type === 1) {
            $constraintType = "PRIMARY KEY";
            $key            = "primarys";
        } else {
            $constraintType = "UNIQUE";
            $key            = "uniques";
        }

        $sql
            = "select group_concat(COLUMN_NAME) {$key},TABLE_CONSTRAINTS.TABLE_NAME  from TABLE_CONSTRAINTS join KEY_COLUMN_USAGE
               on TABLE_CONSTRAINTS.TABLE_SCHEMA=KEY_COLUMN_USAGE.TABLE_SCHEMA
                  and TABLE_CONSTRAINTS.TABLE_NAME = KEY_COLUMN_USAGE.TABLE_NAME
                      and TABLE_CONSTRAINTS.CONSTRAINT_NAME=KEY_COLUMN_USAGE.CONSTRAINT_NAME
            where TABLE_CONSTRAINTS.TABLE_SCHEMA=:schema and CONSTRAINT_TYPE='$constraintType'
            group by TABLE_CONSTRAINTS.TABLE_NAME";

        return $sql;
    }

    private function primarykeysql()
    {
        return $this->keysFromtConstraintsSql(1);
    }

    private function uniqueIndexsql()
    {
        return $this->keysFromtConstraintsSql(2);
    }

    private function handleConstraints($row, $key)
    {
        if (!(is_null($row[$key]))) {
            $keys = explode(",", $row[$key]);
        } else {
            $keys = array();
        }

        return $keys;
    }

    public function getTables($schema)
    {
        set_time_limit(0);
        $primarykeys    = $this->primarykeysql();
        $uniqueIndexsql = $this->uniqueIndexsql();

        $sql = "select TABLES.TABLE_NAME, TABLES.TABLE_COMMENT, primarys,uniques from TABLES";
        $sql .= " left join($primarykeys) tp on tp.table_name=TABLES.TABLE_NAME";
        $sql .= " left join($uniqueIndexsql) tu on tu.table_name=TABLES.TABLE_NAME";
        $sql .= " where TABLES.TABLE_SCHEMA=:schema";

        $st = $this->pdo->prepare($sql);
        $st->bindParam(':schema', $schema, PDO::PARAM_STR);
        $st->execute();

        $tables = array();
        foreach ($st->fetchAll() as $row) {
            $table                   = array();
            $table[self::TABLE_NAME] = $row['TABLE_NAME'];

            $primaryKeys  = $this->handleConstraints($row, 'primarys');
            $uniqueIndexs = $this->handleConstraints($row, 'uniques');

            $table[self::TABLE_NAME]          = $row['TABLE_NAME'];
            $tbname                           = $row['TABLE_NAME'];
            $table[self::TABLE_COLUMNS]       = $this->getColumns($schema, $tbname);
            $table[self::TABLE_PRIMARY_KEYS]  = $primaryKeys;
            $table[self::TABLE_UNIQUE_INDEXS] = $uniqueIndexs;

            $tables[$row['TABLE_NAME']] = $table;
        }

        return $tables;
    }


    public function getColumns($schema, $table)
    {
        $columnNames   = array(
            'COLUMN_NAME',
            'DATA_TYPE',
            'COLUMN_TYPE',
            'COLUMN_DEFAULT',
            'IS_NULLABLE',
            'EXTRA',
            'COLUMN_COMMENT',
        );
        $selectColumns = implode(',', $columnNames);

        $wheremap = array(
            'TABLE_SCHEMA=:s',
            'TABLE_NAME=:t',
        );
        $where    = 'where ' . implode(' and ', $wheremap);


        $sql = "select $selectColumns from COLUMNS $where";

        $st = $this->pdo->prepare($sql);
        $st->bindParam(":s", $schema, PDO::PARAM_STR);
        $st->bindParam(":t", $table, PDO::PARAM_STR);
        $st->execute();
        $columns = array();

        foreach ($st->fetchAll() as $row) {
            $column                              = array();
            $column[self::COLUMN_NAME]           = $row['COLUMN_NAME'];
            $column[self::COLUMN_DATA_TYPE]      = $row['DATA_TYPE'];
            $column[self::COLUMN_COLUMN_TYPE]    = $row['COLUMN_TYPE'];
            $column[self::COLUMN_DEFAULT]        = $row['COLUMN_DEFAULT'];
            $column[self::COLUMN_IS_NULLABLE]    = $row['IS_NULLABLE'] == "NO" ? false : true;
            $column[self::COLUMN_AUTO_INCREMENT] = strpos($row['EXTRA'], 'auto_increment') !== false;

            $column[self::COLUMN_COMMENT]           = $row['COLUMN_COMMENT'];
            $columns[$row['COLUMN_NAME']] = $column;
        }

        return $columns;
    }

    private function fieldWhere($whereMap)
    {
        $whereFields = array();
        foreach ($whereMap as $k => $v) {
            $whereFields[] = "$v[0]=:$k";
        }

        if (!empty($whereFields)) {
            $where = "where " . implode(' and ', $whereFields);
        } else {
            $where = "";
        }

        return $where;
    }

    private function bindParams($whereMap, $stmt)
    {
        foreach ($whereMap as $k => $v) {
            $stmt->bindParam(":$k", $v[1], $v[2]);
        }
    }

    private function getKeysFromtConstraints($schema, $table, $type)
    {
        $whereMap = array(
            'TABLE_NAME'      => array('KEY_COLUMN_USAGE.TABLE_NAME', $table, PDO::PARAM_STR),
            'TABLE_SCHEMA'    => array('KEY_COLUMN_USAGE.TABLE_SCHEMA', $schema, PDO::PARAM_STR),
            'CONSTRAINT_TYPE' => array('CONSTRAINT_TYPE', $type, PDO::PARAM_STR),
        );

        $where = $this->fieldWhere($whereMap);

        $sql = "select COLUMN_NAME from TABLE_CONSTRAINTS join KEY_COLUMN_USAGE " .
               "on TABLE_CONSTRAINTS.TABLE_SCHEMA=KEY_COLUMN_USAGE.TABLE_SCHEMA " .
               "and TABLE_CONSTRAINTS.TABLE_NAME = KEY_COLUMN_USAGE.TABLE_NAME " .
               "and TABLE_CONSTRAINTS.CONSTRAINT_NAME=KEY_COLUMN_USAGE.CONSTRAINT_NAME" .
               " $where";

        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($whereMap, $stmt);
        $stmt->execute();

        $keys = array();
        foreach ($stmt->fetchAll() as $field) {
            $keys[] = $field['COLUMN_NAME'];
        }

        return $keys;
    }
}
