<?php

namespace bng\System;

use PDO;
use stdClass;
use PDOException;


class Database {

    private $host;
    private $dbname;
    private $user;
    private $pass;
    private $return_type;
    private $opt_connection;

    public function __construct($opt, $return_type = 'object', $opt_connection = 'false')
    {

        $this->host = $opt['server'];
        $this->dbname = $opt['dbname'];
        $this->user = $opt['user'];
        $this->pass = $opt['pass'];

        $this->opt_connection = $opt_connection ? [PDO::ATTR_PERSISTENT => false ] : [PDO::ATTR_PERSISTENT => true];

        if(!empty($return_type) or $return_type == 'object'){
            $this->return_type = PDO::FETCH_OBJ;
        } else {
            $this->return_type = PDO::FETCH_ASSOC;
        }
    }

    public function execute_query($sql, $parametros = null){

        $connection = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->user, $this->pass, $this->opt_connection);

        $results = null;

        try{

            $query = $connection->prepare($sql);
            $query->execute($parametros);
            $results = $query->fetchAll($this->return_type);
            

        } catch(PDOException $err) {

            $connection = null;
            return $this->_result('erro', $err->getMessage(), $sql, null, 0, null);

        }

        $connection = null;
        return $this->_result('Sucesso', 'Sucesso', $sql, $results, $query->rowCount(), null );

    }

    public function execute_non_query($sql, $parametros = null){

        $connection = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->user, $this->pass, $this->opt_connection);
        $connection->beginTransaction();

        try {

            $query = $connection->prepare($sql);
            $query->execute($parametros);
            $ultimo_id = $connection->lastInsertId();
            $connection->commit();

        } catch (PDOException $err){

            $connection->rollBack();

            $connection = null;
            return $this->_result('Erro', $err->getMessage(), $sql, null, 0, null);

        }

        $connection = null;
        return $this->_result('Sucesso', 'Sucesso', $sql, null, $query->rowCount(), $ultimo_id);

    }

    private function _result($status, $mensagem, $sql, $results, $affected_rows, $last_id){

        $tmp = new stdClass();
        $tmp->status = $status;
        $tmp->mensagem = $mensagem;
        $tmp->sql = $sql;
        $tmp->results = $results;
        $tmp->affected_rows = $affected_rows;
        $tmp->last_id = $last_id;
        return $tmp;

    }

}

?>