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
        $results = $this->query("SELECT id, passwrd FROM agents WHERE AES_ENCRYPT(:user, '" . MYSQL_AES_KEY . "') = name AND deleted_at IS NULL", $params);
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

    public function add_new_client($id_agente = null, $post_data = null)
    {
        if ($id_agente != null) {
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
        } else {
            $params = [
                ':id' => $_SESSION['user']->id,
                ':nome' => $post_data['text_name'],
                ':sexo' => $post_data['radio_gender'],
                ':nasc' => $post_data['text_birthdate'],
                ':email' => $post_data['text_email'],
                ':tel' => $post_data['text_phone'],
                ':interests' => $post_data['text_interests']
            ];
        }


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

        if ($results->affected_rows == 0) {
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

    public function check_current_password($current, $id)
    {
        $params = [
            ':id' => $id,
        ];

        $this->db_connect();
        $results = $this->query('SELECT passwrd FROM agents WHERE id = :id', $params);

        if(password_verify($current, $results->results[0]->passwrd)){
            return [
                'status' => true
            ];
        } else {
            return [
                'status' => false
            ];
        }
        
        
    }

    public function update_agent_pass($new_pass, $id)
    {
        
        $params = [
            ':pass' => password_hash($new_pass, PASSWORD_DEFAULT),
            ':id' => $id
        ];

        $this->db_connect();
        $results = $this->non_query('UPDATE agents SET passwrd = :pass, updated_at = NOW() WHERE id = :id', $params);

        if($results->status == 'Sucesso')
        {
            return [
                'status' => 'sucesso'
            ];
        } else {
            return [
                'status' => 'erro'
            ];
        }
    }

    public function check_new_agents_purl($purl)
    {
        $this->db_connect();
        $params = [
            ':purl' => $purl
        ];

        $results = $this->query('SELECT id FROM agents WHERE purl = :purl', $params);
        if($results->results[0] == null)
        {
            return [
                'status' => false
            ];
        }

        return [
            'status' => true,
            'results' => $results->results[0]->id
        ];
        
    }

    public function set_pass($id, $pass)
    {
        $this->db_connect();

        $params = [
            ':id' => $id,
            ':pass' => password_hash($pass, PASSWORD_DEFAULT)
        ];

        $results = $this->non_query('UPDATE agents SET passwrd = :pass, purl = NULL, updated_at = NOW() WHERE id = :id', $params);

        return $results;
    }

    public function set_code_for_recover_password($user)
    {
        $this->db_connect();

        
        $params = [
            ':user' => $user
        ];

        $results = $this->query("SELECT id FROM agents WHERE AES_ENCRYPT(:user, '" . MYSQL_AES_KEY . "') = name AND passwrd IS NOT NULL AND deleted_at IS NULL", $params);

        if($results->affected_rows == 0){
            return [
                'status' => 'erro',
                'sql' => $results
            ];
        }

        $code = rand(100000, 999999);
        $id = $results->results[0]->id;

        $params = [
            ':id' => $id,
            ':code' => $code
        ];

        $results = $this->non_query('UPDATE agents SET code = :code WHERE id = :id', $params);

        if($results->affected_rows == 1)
        {
            return [
                'status' => 'sucesso',
                'id' => $id,
                'code' => $code
            ];
        } else {
            return [
                'status' => 'erro'
            ];
        }

        
    }

    public function check_code($id, $code)
    {
        $this->db_connect();

        $params = [
            ':id' => $id,
            ':code' => $code
        ];

        $results = $this->query('SELECT id FROM agents WHERE id = :id AND code = :code', $params);

        if($results->affected_rows == 1)
        {
            return true;
        } else {
            return false;
        }

    }
}
