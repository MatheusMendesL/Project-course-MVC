<?php

namespace bng\Models;

use bng\Models\BaseModel;

class Agents extends BaseModel
{
    public function check_login($user, $pass)
    {
        $params = [
            ':user' => $user
        ];

        // Verifica se o user tem uma conta
        $this->db_connect();
        $results = $this->query("SELECT id, passwrd FROM agents WHERE AES_ENCRYPT(:user, '" . MYSQL_AES_KEY . "') = name", $params);
        // o AES_ENCRYPT é a encriptação e o aes_key é forma como será encriptado

        // Se não for usuario, retorna falso
        if ($results->affected_rows == 0) {
            return [
                'status' => false
            ];
        }

        // Ve se a senha é correta
        if (!password_verify($pass, $results->results[0]->passwrd)) {
            return [
                'status' => false
            ];
        }

        return [
            'status' => true
        ];
    }

    public function get_user_data($user)
    {
        $params = [
            ':user' => $user
        ];

        $this->db_connect();
        $results = $this->query("SELECT id, AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, profile FROM agents WHERE AES_ENCRYPT(:user, '" . MYSQL_AES_KEY . "') = name", $params);
        return [
            'status' => 'success',
            'data' => $results->results[0]
        ];
    }

    public function set_user_last_login($id)
    {
        $this->db_connect();
        $params = [
            ':id' => $id
        ];
        $results = $this->non_query("UPDATE agents SET last_login = NOW() WHERE id = :id", $params);
        return $results;
    }

    public function check_name($name)
    {
        $params = [
            ':id_agente' => $_SESSION['user']->id,
            ':nome' => $name
        ];

        $this->db_connect();
        $results = $this->query("SELECT * FROM persons WHERE AES_ENCRYPT(:nome, '" . MYSQL_AES_KEY . "') = name AND id_agent = :id_agente", $params);
        if ($results->affected_rows == 0) {
            return [
                'status' => 1,
                'result' => $results
            ];
        } else {
            return [
                'status' => 0,
                'result' => $results
            ];
        }
    }

    public function get_agent_clients($id_agent)
    {
        $params = [
            ':id_agent' => $id_agent
        ];

        $this->db_connect();
        $results = $this->query(
            "SELECT " .
                "id, " .
                "AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, " .
                "gender, " .
                "birthdate, " .
                "AES_DECRYPT(email, '" . MYSQL_AES_KEY . "') email, " .
                "AES_DECRYPT(phone, '" . MYSQL_AES_KEY . "') phone, " .
                "interests, " .
                "created_at, " .
                "updated_at " .
                "FROM persons " .
                "WHERE id_agent = :id_agent " .
                "AND deleted_at IS NULL",
            $params
        );

        return [
            'status' => 'sucesso',
            'data' => $results->results
        ];
    }

    public function add_new_client($id_agente)
    {

        $nome = $_POST['text_name'];
        $sexo = $_POST['radio_gender'];
        $nasc = $_POST['text_birthdate'];
        $email = $_POST['text_email'];
        $tel = $_POST['text_phone'];
        $interests = $_POST['text_interests'];
        $params = [
            ':id' => $id_agente,
            ':nome' => $nome,
            ':sexo' => $sexo,
            ':nasc' => $nasc,
            ':email' => $email,
            ':tel' => $tel,
            ':interests' => $interests
        ];

        $this->db_connect();
        $results = $this->non_query('INSERT INTO persons (id, name, gender, birthdate, email, phone, interests, id_agent, created_at) VALUES(0, AES_ENCRYPT(:nome, "' . MYSQL_AES_KEY . '"), :sexo, :nasc, AES_ENCRYPT(:email, "' . MYSQL_AES_KEY . '"), AES_ENCRYPT(:tel, "' . MYSQL_AES_KEY . '"), :interests, :id,  NOW()) ', $params);
        return [
            'status' => 'sucesso',
            'data' => $results
        ];
    }


    public function get_cliente_data($id)
    {
        $params = [
            ':id' => $id
        ];

        $this->db_connect();
        $results = $this->query(
            "SELECT " .
                "id, " .
                "AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, " .
                "gender, " .
                "birthdate, " .
                "AES_DECRYPT(email, '" . MYSQL_AES_KEY . "') email, " .
                "AES_DECRYPT(phone, '" . MYSQL_AES_KEY . "') phone, " .
                "interests " .
                "FROM persons " .
                "WHERE id = :id",
            $params
        );
        return [
            'status' => 'sucesso',
            'data' => $results->results[0],
            'results' => $results,
            'params' => $params
        ];
    }

    public function check_edit_client_name($user = [])
    {
        if ($user == null) {
            die("Usuário inválido");
        }

        $this->db_connect();
        $params = [
            ':nome' => $_POST['text_name'],
            ':id' => $_POST['id_client'],
            ':id_agent' => $_SESSION['user']->id
        ];

        $results = $this->query("SELECT id FROM persons WHERE id <> :id AND id_agent = :id_agent AND AES_ENCRYPT(:nome, '" . MYSQL_AES_KEY . "') = name", $params);

        if($results->affected_rows == 0)
        {
            return [
                'status' => false,
                'results' => $results,
                'params' => $params

            ];
        } else {
            return [
                'status' => true,
                'results' => $results,
                'params' => $params

            ];
        }

        
    }

    public function edit_client_model($id)
    {
        $nome = $_POST['text_name'];
        $sexo = $_POST['radio_gender'];
        $nasc = $_POST['text_birthdate'];
        $email = $_POST['text_email'];
        $tel = $_POST['text_phone'];
        $interests = $_POST['text_interests'];
        $params = [
            ':id' => $id,
            ':nome' => $nome,
            ':sexo' => $sexo,
            ':nasc' => $nasc,
            ':email' => $email,
            ':tel' => $tel,
            ':interests' => $interests
        ];

        $this->db_connect();
        $results = $this->non_query('UPDATE persons SET name = AES_ENCRYPT(:nome, "' . MYSQL_AES_KEY . '"), gender = :sexo, birthdate = :nasc, email = AES_ENCRYPT(:email, "' . MYSQL_AES_KEY . '"), phone = AES_ENCRYPT(:tel, "' . MYSQL_AES_KEY . '"), interests = :interests, updated_at = NOW() WHERE id = :id', $params);
        return [
            'status' => 'sucesso',
            'data' => $results
        ];
    }

    public function delete_client($id)
    {
        $params = [
            ':id' => $id
        ];

        $this->db_connect();
        $this->non_query('DELETE FROM persons WHERE id = :id', $params);
    }
}
