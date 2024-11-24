<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;


class agent extends BaseController
{

    private function check()
    {
        if (!check_session() || $_SESSION['user']->profile != 'agent') {
            return header('location:index.php');
        }
    }

    private function verify_errors()
    {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('location:index.php');
        }

        $validation_errors = [];

        if (empty($_POST['text_name'])) {
            $validation_errors[] = "O nome de usuário é obrigatório";
        } else if (strlen($_POST['text_name']) < 3 or (strlen($_POST['text_name']) > 50)) {
            $validation_errors[] = "O nome deve ter entre 3 e 50 caracteres";
        }

        if (empty($_POST['radio_gender'])) {
            $validation_errors[] = "O gênero é obrigatório";
        }


        if (empty($_POST['text_birthdate'])) {
            $validation_errors[] = "A data de nascimento é obrigatória";
        }

        if (empty($_POST['text_email'])) {
            $validation_errors[] = "O email é obrigatório";
        } else if (!filter_var($_POST['text_email'], FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "O email é inválido";
        }

        if (empty($_POST['text_phone'])) {
            $validation_errors[] = "O telefone é obrigatório";
        } else if (!preg_match("/^9{1}\d{8}$/", $_POST['text_phone'])) {
            $validation_errors[] = "O telefone deve começar por 9 e ter 9 algarismos no total";
        }

        if (empty($_POST['text_interests'])) {
            $validation_errors[] = "Os interesses são obrigatórios";
        }

        if (!empty($validation_errors)) {
            $_SESSION['validation_errors'] = $validation_errors;
            return [
                'status' => false
            ];
        }

        return [
            'status' => true
        ];

        // Aqui ele verifica se há erros

    }

    public function my_clients()
    {
        $this->check();


        $id_agent = $_SESSION['user']->id;
        $agent = new Agents();
        $results = $agent->get_agent_clients($id_agent);

        $data['user'] = $_SESSION['user'];
        $data['Clients'] = $results['data'];


        $this->views_with_navbar('agent_clients.php', $data);
    }

    public function add_new_client()
    {

        $this->check();

        $data['user'] = $_SESSION['user'];
        $data['flatpickr'] = true;


        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] =  $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] =  $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }



        $this->views_with_navbar('insert_client_frm.php', $data);
    }


    public function new_client_submit()
    {
        $this->check();
        $errors =  $this->verify_errors();
        if ($errors['status'] == false) {
            $this->add_new_client();
            return;
        }

        $new_user = new Agents();
        $check_name = $new_user->check_name($_POST['text_name']);

        if ($check_name['status'] == 0) {
            $_SESSION['server_error'] = ["Já tem um cliente com esse nome"];
            $this->add_new_client();
            return;
        }

        $new_user->add_new_client($_SESSION['user']->id);
        $this->my_clients();
        logger(get_user_session() . ' - Adcionou um novo cliente ' . $_POST['text_name'] . ' - ' . $_POST['text_email'], 'info');
    }

    public function edit_client($id)
    {

        $this->check();

        $data['user'] = $_SESSION['user'];
        $id_cliente = decrypt($id);

        $data['flatpickr'] = true;

        $get_cliente = new Agents();
        $results = $get_cliente->get_cliente_data(decrypt($_GET['id']));

        $_SESSION['client'] = $results['data'];
        $data['cliente'] = $_SESSION['client'];

        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] =  $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] =  $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $data['cliente_id'] = $id_cliente;


        $this->views_with_navbar('edit_client_frm.php', $data);
    }

    public function edit_client_submit($id)
    {
        $this->check();
        $errors =  $this->verify_errors();
        if ($errors['status'] == false) {
            $this->edit_client($_SESSION['client']->id);
            return;
        }


        $id_client = decrypt($_POST['id_client']);


        $edit_user = new Agents();
        $_POST['id_client'] = $_SESSION['client']->id;
        $check_name = $edit_user->check_edit_client_name($_POST);


        if ($check_name['status'] == true) {
            $_SESSION['server_error'] = ["Já tem um cliente com esse nome"];
            $this->edit_client(decrypt($id));
            return;
        }



        $edit_user->edit_client_model(decrypt($id));
        $this->my_clients();
        header('location: ?ct=agent&mt=my_clients');
        logger(get_user_session() . ' - Editou um cliente ' . $_POST['text_name'] . ' - ' . $_POST['text_email'], 'info');
    }

    public function delete_client($id)
    {

        $this->check();
        $data['user'] = $_SESSION['user'];

        $id_cliente = decrypt($id);

        $get_cliente = new Agents();
        $results = $get_cliente->get_cliente_data($id_cliente);

        $_SESSION['client'] = $results['data'];
        $data['cliente'] = $_SESSION['client'];
        $this->views_with_navbar('delete_client_confirmation.php', $data);
    }

    public function confirm_delete($id)
    {

        $this->check();
        $id_cliente = decrypt($id);
        logger(get_user_session() . ' - Excluiu um cliente ' . $_SESSION['client']->name . ' - ' . $_SESSION['client']->email , 'info');
        $delete = new Agents();
        $delete->delete_client($id_cliente);
        $this->my_clients();
    }
}
