<?php

namespace bng\Models;

use bng\System\Database;

abstract class BaseModel
{
    public $db;

    protected function db_connect()
    {
        $options = [
            'server' => MYSQL_HOST,
            'dbname' => MYSQL_DATABASE,
            'user' => MYSQL_USERNAME,
            'pass' => MYSQL_PASSWORD
        ];

        $this->db = new Database($options);
    }

    protected function query($sql = null, $param = [])
    {
        return $this->db->execute_query($sql, $param);
    }

    protected function non_query($sql = null, $param = [])
    {
        return $this->db->execute_non_query($sql, $param);
    }
}
