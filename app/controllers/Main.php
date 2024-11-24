<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;

class Main extends BaseController
{

    public function index()
    {
        if (!check_session()) {
            $this->login_frm();
            return;
        }

        $data['user'] = $_SESSION['user'];

        $this->views_with_navbar('homepage.php', $data);
    }


    // LOGIN
    public function login_frm()
    {
        if (check_session()) {
            $this->index();
            return;
        }

        // Retorna ao index caso alguém tente entrar aqui direto

        $data = [];

        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] =  $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] =  $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        // Verifica se há erros

        $this->view('layouts/html_header.php');
        $this->view('login_frm.php', $data);
        $this->view('layouts/html_footer.php');

        // Faz os displays
    }


    public function login_submit()
    {
        if (check_session()) {
            $this->index();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->index();
            return;
        }

        //Verifica erros

        $validation_errors = [];

        if (empty($_POST['text_username']) or empty($_POST['text_password'])) {
            $validation_errors[] = "Username e password são obrigatórios";
        }

        $user = $_POST['text_username'];
        $pass = $_POST['text_password'];

        // Verifica se o email é valido
        if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Digite um email válido";
        }

        // Verifica se a senha tem entre 5 e 50 caracteres
        if (strlen($user) < 5 or strlen($user) > 50) {
            $validation_errors[] = "A usuário deve ter entre 5 e 50 caracteres";
        }

        if (strlen($pass) < 6 or strlen($pass) > 12) {
            $validation_errors[] = "A Senha deve ter entre 6 e 12 caracteres";
        }

        // Se houver erros ele é passado para cá e executado o metódo do login
        if (!empty($validation_errors)) {
            $_SESSION['validation_errors'] = $validation_errors;
            $this->login_frm();
            return;
        }

        $agents = new Agents();
        $result = $agents->check_login($user, $pass);

        if (!$result['status']) {
            // Login inválido
            $_SESSION['server_error'] = ["Login inválido"];
            $this->login_frm();
            logger($user . ' - Login Inválido', 'error');
            return;
        }

        logger($user . " - Fez login com sucesso");
        $results = $agents->get_user_data($user);
        $_SESSION['user'] = $results['data'];
        $agents->set_user_last_login($_SESSION['user']->id);
        $this->index();
        
    }

    public function logout()
    {
        if(isset($_SESSION['user'])){
            logger($_SESSION['user']->name . ' - Fez logout');
        }

        unset($_SESSION['user']);
        session_destroy();
        $this->index();
    }
}
