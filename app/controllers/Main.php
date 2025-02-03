<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;
use bng\System\sendEmail;

class Main extends BaseController
{

    public function index()
    {
        if (!check_session()) {
            $this->login_frm();
            return;
        }

        $data['user'] = $_SESSION['user'];

        $this->views_with_navbar('homepage', $data);
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
        if (isset($_SESSION['user'])) {
            logger($_SESSION['user']->name . ' - Fez logout');
        }

        unset($_SESSION['user']);
        session_destroy();
        $this->index();
    }

    public function change_pass_frm()
    {

        if (!check_session()) {
            $this->index();
            return;
        }



        $data['user'] = $_SESSION['user'];

        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] =  $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] =  $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        // Recebem os erros

        $this->views_with_navbar('profile_change_password_frm', $data);
    }

    public function change_pass_submit()
    {
        if (!check_session()) {
            $this->index();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->index();
            return;
        }

        $validation_errors = [];

        if (empty($_POST['text_current_password'])) {
            $validation_errors[] = "A senha atual é de preenchimento obrigatório";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_pass_frm();
            return;
        }

        if (empty($_POST['text_new_password'])) {
            $validation_errors[] = "A senha nova é de preenchimento obrigatório";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_pass_frm();
            return;
        }

        if (empty($_POST['text_repeat_new_password'])) {
            $validation_errors[] = "A repetição de senha é de preenchimento obrigatório";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_pass_frm();
            return;
        }

        $current_pass = $_POST['text_current_password'];
        $new_pass = $_POST['text_new_password'];
        $repeat_new_pass = $_POST['text_repeat_new_password'];

        if ($new_pass != $repeat_new_pass) {
            $validation_errors[] = "A nova senha e a repetição devem ser iguais";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_pass_frm();
            return;
        }

        if (strlen($new_pass) < 6 or strlen($new_pass) > 12) {
            $validation_errors[] = "A senha deve ter de 6 a 12 caracteres";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_pass_frm();
            return;
        }

        $model = new Agents();
        $results = $model->check_current_password($current_pass, $_SESSION['user']->id);
        $server_error = [];

        if (!$results['status']) {
            $server_error[] = "A senha atual está errada";
            $_SESSION['server_error'] = $server_error;
            $this->change_pass_frm();
            return;
        }

        $updated = $model->update_agent_pass($new_pass, $_SESSION['user']->id);

        // for logger


        if ($updated) {
            $user = $_SESSION['user']->name;
            logger("$user - password alterada com sucesso desse utilizador");

            $data['user'] = $_SESSION['user'];

            $this->views_with_navbar('profile_change_password_success', $data);
        }
    }

    public function define_password($purl = '')
    {
        if (!check_session()) {
            $this->index();
            return;
        }

        if (empty($purl) or strlen($purl) != 20) {
            die("Erro nas credenciais de acesso");
        }

        $model = new Agents();
        $results = $model->check_new_agents_purl($purl);


        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] =  $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!$results['status']) {
            die("Erro nas credenciais");
        }

        $data['purl'] = $purl;
        $data['id'] = $results['results'];

        $this->views_without_navbar('new_agent_define_password', $data);
    }

    public function define_password_submit()
    {
        if (!check_session()) {
            $this->index();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->index();
            return;
        }

        if (empty($_POST['purl']) or empty($_POST['id']) or strlen($_POST['purl']) != 20) {
            $this->index();
            return;
        }

        $id = decrypt($_POST['id']);
        $purl = $_POST['purl'];

        if (!$id) {
            $this->index();
            return;
        }


        if (empty($_POST['text_password'])) {
            $validation_errors[] = "A senha atual é de preenchimento obrigatório";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->define_password($purl);
            return;
        }

        if (empty($_POST['text_repeat_password'])) {
            $validation_errors[] = "A senha nova é de preenchimento obrigatório";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->define_password($purl);
            return;
        }

        $pass = $_POST['text_password'];
        $repeat_pass = $_POST['text_repeat_password'];

        if ($pass != $repeat_pass) {
            $validation_errors[] = "A nova senha e a repetição devem ser iguais";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->define_password($purl);
            return;
        }

        if (strlen($pass) < 6 or strlen($pass) > 12) {
            $validation_errors[] = "A senha deve ter de 6 a 12 caracteres";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->define_password($purl);
            return;
        }

        $model = new Agents();
        $model->set_pass($id, $pass);

        logger("O Agente com ID: $id e com o seguinte purl: $purl definiu a senha");

        $this->views_without_navbar('reset_password_define_password_success');
    }

    public function reset_password()
    {
        if (check_session()) {
            $this->index();
            return;
        }

        $data = [];

        if (isset($_SESSION['validation_error'])) {
            $data['validation_error'] = $_SESSION['validation_error'];
            unset($_SESSION['validation_error']);
        }

        if (isset($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }


        $this->views_without_navbar('reset_password_frm', $data);
    }

    public function reset_password_submit()
    {
        if (check_session()) {
            $this->index();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->index();
            return;
        }

        if (empty($_POST['text_username'])) {
            $_SESSION['validation_error'] = 'O utilizador é obrigatório';
            $this->reset_password();
            return;
        }

        if (!filter_var($_POST['text_username'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['validation_error'] = 'O utilizador deve conter um email válido';
            $this->reset_password();
            return;
        }

        $user = $_POST['text_username'];
        $model = new Agents();
        $results = $model->set_code_for_recover_password($user);

        if ($results['status'] == 'erro') {
            logger("Aconteceu um erro na criação do código para esse utilizador: " . $user, 'ERROR');
            $_SESSION['validation_error'] = 'Esse utilizador não foi encontrado em nosso banco de dados';
            $this->reset_password();
            return;
        }

        $id = $results['id'];
        $code = $results['code'];

        $email = new sendEmail();
        $data = [
            'from' => $user,
            'to' => 'consolezone76@gmail.com',
            'code' => $code,
        ];
        $results = $email->send_email(APP_NAME, 'Código para recuperar password <br> Para definir sua password, use o seguinte código: <h3> ' . $code . ' </h3>', $data);

        if ($results['status'] == 'erro') {
            logger("Aconteceu um erro na criação do código para esse utilizador: " . $user, 'ERROR');

            $_SESSION['validation_error'] = 'Aconteceu um erro inesperado, tente novamente por favor';
            $this->reset_password();
            return;
        }

        logger("Email com código de recuperação enviado para: $user com o código: $code");

        $this->insert_code(encrypt($id));
    }

    public function insert_code($id = '')
    {


        $id = decrypt($id);

        $data['id'] = $id;

        if (isset($_SESSION['validation_error'])) {
            $data['validation_error'] = $_SESSION['validation_error'];
            unset($_SESSION['validation_error']);
        }

        if (isset($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->views_without_navbar('reset_password_insert_code', $data);
    }

    public function insert_code_submit($id = '')
    {
        if (check_session()) {
            $this->index();
            return;
        }

        $id = decrypt($id);




        if (empty($_POST['text_code'])) {
            $_SESSION['validation_error'] = 'Código é de preenchimento obrigatório';
            $this->insert_code(encrypt($id));
            return;
        }

        $code = $_POST['text_code'];

        $model = new Agents();
        $results = $model->check_code($id, $code);



        if ($results) {
            $this->reset_define_pass(encrypt($id));
        } else {
            $_SESSION['server_error'] = 'Código está incorreto';
            $this->insert_code(encrypt($id));
            return;
        }
    }

    public function reset_define_pass($id = '')
    {
        if (check_session()) {
            $this->index();
            return;
        }

        if(empty($id))
        {
            $this->index();
            return;
        }

        $data['id'] = decrypt($id);

        if (isset($_SESSION['validation_error'])) {
            $data['validation_error'] = $_SESSION['validation_error'];
            unset($_SESSION['validation_error']);
        }

        if (isset($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->views_without_navbar('reset_password_define_password_frm', $data);


    }

    public function reset_define_pass_submit($id = '')
    {
        if (check_session()) {
            $this->index();
            return;
        }

        if(empty($id))
        {
            $this->index();
            return;
        }

        if (empty($_POST['text_new_password'])) {
            $_SESSION['validation_error'] = "A senha nova é de preenchimento obrigatório";
            $this->reset_define_pass(encrypt($id));
            return;
        }

        if (empty($_POST['text_repeat_new_password'])) {
            $_SESSION['validation_error'] = "A repetição de senha é de preenchimento obrigatório";
            $this->reset_define_pass(encrypt($id));
            return;
        }

        $new_pass = $_POST['text_new_password'];
        $repeat_new_pass = $_POST['text_repeat_new_password'];

        if ($new_pass != $repeat_new_pass) {

            $_SESSION['validation_error'] = "A nova senha e a repetição devem ser iguais";
            $this->reset_define_pass(encrypt($id));
            return;
        }

        if (strlen($new_pass) < 6 or strlen($new_pass) > 12) {
            $_SESSION['validation_error'] = "A senha deve ter de 6 a 12 caracteres";
            $this->reset_define_pass(encrypt($id));
            return;
        }

        $model = new Agents();
        $results = $model->update_agent_pass($new_pass, decrypt($id));

        if(!$results['status'])
        {
            $_SESSION['server_error'] = "Ocorreu um erro, tente novamente";
            $this->reset_define_pass(encrypt($id));
            return;
        }

        logger('Utlizador com o ID: ' . decrypt($id) . ' atualizou a sua senha');
        $this->views_without_navbar('reset_password_define_password_success');
    }
}
