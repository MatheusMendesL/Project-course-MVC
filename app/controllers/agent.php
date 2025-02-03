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
        $data['datatables'] = true;


        $this->views_with_navbar('agent_clients', $data);
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



        $this->views_with_navbar('insert_client_frm', $data);
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
        $data['cliente']->birthdate = date('d-m-Y', strtotime($data['cliente']->birthdate));

        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] =  $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] =  $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $data['cliente_id'] = $id_cliente;


        $this->views_with_navbar('edit_client_frm', $data);
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
        $this->views_with_navbar('delete_client_confirmation', $data);
    }

    public function confirm_delete($id)
    {

        $this->check();
        $id_cliente = decrypt($id);
        logger(get_user_session() . ' - Excluiu um cliente ' . $_SESSION['client']->name . ' - ' . $_SESSION['client']->email, 'info');
        $delete = new Agents();
        $delete->delete_client($id_cliente);
        $this->my_clients();
    }

    public function upload_file_frm()
    {
        $this->check();

        $data['user'] = $_SESSION['user'];

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] =  $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        if (!empty($_SESSION['report'])) {
            $data['report'] =  $_SESSION['report'];
            unset($_SESSION['report']);
        }

        $this->views_with_navbar('upload_file_with_clients_frm', $data);
    }

    public function upload_file_submit()
    {
        $this->check();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            die("Acesso negado");
        }

        if (empty($_FILES['clients_file']['name'])) {
            $_SESSION['server_error'] = 'Faça o carregamento de um arquivo XLSX ou CSV';
            $this->upload_file_frm();
            return;
        }

        $valid_extensions = ['xlsx', 'csv'];
        $tmp = explode('.', $_FILES['clients_file']['name']);
        $extension = end($tmp);

        if (!in_array($extension, $valid_extensions)) {
            logger(get_user_session() . " - tentou carregar um arquivo inválido: " . $_FILES['clients_file']['name'], "error");
            $_SESSION['server_error'] = 'O ficheiro deve ser XLSX ou CSV';
            $this->upload_file_frm();
            return;
        }

        if ($_FILES['clients_file']['size'] > 2000000) {
            $_SESSION['server_error'] = "O arquivo deve ter no máximo 2 MB";
            logger(get_user_session() . " - tentou carregar um arquivo inválido: " . $_FILES['clients_file']['name'] . " Ultrapassou o limite máximo", "error");
            $this->upload_file_frm();
            return;
        }

        $file_path = __DIR__ . '/../../uploads/dados_' . time() . '.' . $extension;


        if (move_uploaded_file($_FILES['clients_file']['tmp_name'], $file_path)) {
            $result = $this->has_valid_header($file_path);
            if (!$result) {

                logger(get_user_session() . " - tentou carregar um arquivo com header inválido: " . $_FILES['clients_file']['name'], "error");
                unlink($file_path);
                $_SESSION['server_error'] = "O cabeçalho deve ter as seguintes informações: name,gender,birthdate,email,phone,interests";
                $this->upload_file_frm();
                return;
            } else {
                $results = $this->load_file_data_to_database($file_path);
            }
        } else {
            logger(get_user_session() . " - Aconteceu um erro inesperado no carregamento do arquivo: " . $_FILES['clients_file']['name'], "error");
            $_SESSION['server_error'] = 'Houve um erro inesperado no carregamento de arquivos';
            $this->upload_file_frm();
            return;
        }
    }

    private function has_valid_header($file_path)
    {

        $data = [];

        $file_info = pathinfo($file_path);

        if ($file_info['extension'] == 'csv') {

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setDelimiter(';');
            $reader->setEnclosure('');
            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray()[0];
        } else if ($file_info['extension'] == 'xlsx') {

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray()[0];
        }

        $valid_header = 'name,gender,birthdate,email,phone,interests';
        return implode(',', $data) == $valid_header ? true : false;
    }

    private function load_file_data_to_database($file_path)
    {

        $data = [];

        $file_info = pathinfo($file_path);

        if ($file_info['extension'] == 'csv') {

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setDelimiter(';');
            $reader->setEnclosure('');
            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray();
        } else if ($file_info['extension'] == 'xlsx') {

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray();
        }

        $model = new Agents();

        $report = [
            'total' => 0,
            'total_carregados' => 0,
            'total_nao_carregados' => 0
        ];

        array_shift($data);

        foreach ($data as $client) {
            $report['total']++;
            // checa se o cliente existe verificando o nome
            $exists = $model->check_name($client[0]);
            if ($exists['status'] == 1) {
                $post_data = [
                    'text_name' => $client[0],
                    'radio_gender' => $client[1],
                    'text_birthdate' => $client[2],
                    'text_email' => $client[3],
                    'text_phone' => $client[4],
                    'text_interests' => $client[5]
                ];


                if (!in_array(null, $post_data)) {
                    $model->add_new_client(null, $post_data);
                    $report['total_carregados']++;
                } else {
                    $report['total_nao_carregados']++;
                }
            } else {
                $report['total_nao_carregados']++;
                /*unlink($file_path);
                $_SESSION['server_error'] = "Já existe um cliente com o nome $client[0]";
                $this->upload_file_frm();
                return;*/
            }
        }

        logger(get_user_session() . " - Carregamento de ficheiro efetuado " . $_FILES['clients_file']['name']);
        logger(get_user_session() . " - Report: " . json_encode($report));

        $report['filename'] = $_FILES['clients_file']['name'];
        $_SESSION['report'] = $report;

        $this->upload_file_frm();
    }

    public function export_clients_xlsx()
    {
        $this->check();

        $model = new Agents();
        $results = $model->get_agent_clients($_SESSION['user']->id);

        // add header to collection
        $data[] = ['name', 'gender', 'birthdate', 'email', 'phone', 'interests', 'created_at', 'updated_at'];

        // place all clients in the $data collection
        foreach ($results['data'] as $client) {

            // remove the first property (id)
            unset($client->id);

            // add data as array (original $client is a stdClass object)
            $data[] = (array)$client;
        }

        // store the data into the XSLX file
        $filename = 'output_' . time() . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'dados');
        $spreadsheet->addSheet($worksheet);
        $worksheet->fromArray($data);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        $writer->save('php://output');


        logger(get_user_session() . " Fez download da lista de clientes para o ficheiro: " . $filename . " | total: " . count($data) - 1 . " registros.");
    }
}
