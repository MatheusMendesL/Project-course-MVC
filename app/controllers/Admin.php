<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\adminModel;
use bng\System\sendEmail;


class admin extends BaseController
{

    private function check()
    {
        if (!check_session() or $_SESSION['user']->profile != 'admin') {
            return header('Location: index.php');
        }
    }

    

    public function all_clients()
    {

        $this->check();

        $data['user'] = $_SESSION['user'];

        $model = new AdminModel();
        $results = $model->get_all_clients();
        $data['user'] = $_SESSION['user'];
        $data['clients'] = $results->results;

        $this->views_with_navbar('global_clients', $data);
    }

    public function export_clients_xlsx()
    {

        $model = new adminModel();
        $results = $model->get_all_clients();
        $results = $results->results;

        // add header to collection
        $data[] = ['name', 'gender', 'birthdate', 'email', 'phone', 'interests', 'created_at', 'Agente'];

        // place all clients in the $data collection
        foreach ($results as $client) {

            // remove the first property (id)
            unset($client->id);

            // add data as array (original $client is a stdClass object)
            $data[] = (array)$client;
        }

        // store the data into the XSLX file
        $filename = 'all_clients_output_' . time() . '.xlsx';
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

    public function stats()
    {
        $this->check();

        $model = new adminModel();

        $data['agents'] = $model->get_agents_clients_stats();

        $data['user'] = $_SESSION['user'];

        // Prepara os dados para o chartjs
        if (count($data['agents']) != 0) {
            $labels_tmp = [];
            $totals_tmp = [];
            foreach ($data['agents'] as $agent) {
                $labels_tmp[] = $agent->agente; // Passa os nomes dos agentes
                $totals_tmp[] = $agent->total_clientes; // Passa o total de clientes de cada agente
            }

            $data['chart_labels'] = '["' . implode('","', $labels_tmp) . '"]';
            $data['chart_totals'] = '[' . implode(',', $totals_tmp) . ']';
            $data['chartjs'] = true;
        }

        $data['global_stats'] = $model->get_global_stats();

        $this->views_with_navbar('stats', $data);
    }

    public function create_pdf_report()
    {
        $this->check();

        logger(get_user_session() . "Visualizou o PDF com o report estatístico");

        $model = new adminModel();
        $agents = $model->get_agents_clients_stats();
        $global_stats = $model->get_global_stats();

        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P'
        ]);

        $x = 50;
        $y = 50;
        $html = "";

        $html .= '<div style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px;">';
        $html .= '<img src="assets/images/logo_32.png">';
        $html .= '</div>';
        $html .= '<h2 style="position: absolute; left: ' . ($x + 50) . 'px; top: ' . ($y - 10) . 'px;">' . APP_NAME . '</h2>';

        // separator
        $y += 50;
        $html .= '<div style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px; width: 700px; height: 1px; background-color: rgb(200,200,200);"></div>';

        // report title
        $y += 20;
        $html .= '<h3 style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px; width: 700px; text-align: center;">REPORT DE DADOS DE ' . date('d-m-Y') . '</h4>';

        // -----------------------------------------------------------
        // table agents and totals
        $y += 50;

        $html .= '
            <div style="position: absolute; left: ' . ($x + 90) . 'px; top: ' . $y . 'px; width: 500px;">
                <table style="border: 1px solid black; border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 60%; border: 1px solid black; text-align: left;">Agente</th>
                            <th style="width: 40%; border: 1px solid black;">N.º de Clientes</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($agents as $agent) {
            $html .=
                '<tr style="border: 1px solid black;">
                    <td style="border: 1px solid black;">' . $agent->agente . '</td>
                    <td style="text-align: center;">' . $agent->total_clientes . '</td>
                </tr>';
            $y += 25;
        }

        $html .= '
            </tbody>
            </table>
            </div>';

        // -----------------------------------------------------------
        // table globals
        $y += 50;

        $html .= '
            <div style="position: absolute; left: ' . ($x + 90) . 'px; top: ' . $y . 'px; width: 500px;">
                <table style="border: 1px solid black; border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 60%; border: 1px solid black; text-align: left;">Item</th>
                            <th style="width: 40%; border: 1px solid black;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>';

        $html .= '<tr><td>Total agentes:</td><td style="text-align: right;">' . $global_stats['total_agents']->value . '</td></tr>';
        $html .= '<tr><td>Total clientes:</td><td style="text-align: right;">' . $global_stats['total_clients']->value . '</td></tr>';
        $html .= '<tr><td>Total clientes removidos:</td><td style="text-align: right;">' . $global_stats['total_deleted_clients']->value . '</td></tr>';
        $html .= '<tr><td>Média de clientes por agente:</td><td style="text-align: right;">' . sprintf("%.2f", $global_stats['average_clients_per_agent']->value) . '</td></tr>';

        if (empty($global_stats['younger_client']->value)) {
            $html .= '<tr><td>Idade do cliente mais novo:</td><td style="text-align: right;">-</td></tr>';
        } else {
            $html .= '<tr><td>Idade do cliente mais novo:</td><td style="text-align: right;">' . $global_stats['younger_client']->value . ' anos.</td></tr>';
        }
        if (empty($global_stats['oldest_client']->value)) {
            $html .= '<tr><td>Idade do cliente mais velho:</td><td style="text-align: right;">-</td></tr>';
        } else {
            $html .= '<tr><td>Idade do cliente mais velho:</td><td style="text-align: right;">' . $global_stats['oldest_client']->value . ' anos.</td></tr>';
        }

        $html .= '<tr><td>Percentagem de homens:</td><td style="text-align: right;">' . $global_stats['percentage_males']->value . ' %</td></tr>';
        $html .= '<tr><td>Percentagem de mulheres:</td><td style="text-align: right;">' . $global_stats['percentage_females']->value . ' %</td></tr>';

        $html .= '
                    </tbody>
                </table>
            </div>';

        // -----------------------------------------------------------

        $pdf->WriteHTML($html);

        $pdf->Output();
    }

    public function agents_management()
    {
        $this->check();
        $model = new adminModel();
        $results = $model->get_agents_for_management();
        $data['agents'] = $results->results;
        $data['user'] = $_SESSION['user'];

        $this->views_with_navbar('agents_management', $data);
    }

    public function add_new_agent_frm()
    {
        $this->check();

        $data['user'] = $_SESSION['user'];
        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] =  $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] =  $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->views_with_navbar('agents_add_new_frm', $data);
    }

    public function add_new_agent_submit()
    {
        $this->check();

        $validation_error = null;
        if (empty($_POST['text_name']) or !filter_var($_POST['text_name'], FILTER_VALIDATE_EMAIL)) {
            $validation_error = 'O nome deve ser válido';
        }

        $valid_profiles = ['agent', 'admin'];
        if (empty($_POST['select_profile']) or !in_array($_POST['select_profile'], $valid_profiles)) {
            $validation_error = 'O perfil deve ser válido';
        }

        if (!empty($validation_error)) {
            $_SESSION['validation_errors'] = $validation_error;
            $this->add_new_agent_frm();
            return;
        }


        $server_error = null;
        $model = new adminModel();

        $results = $model->check_name($_POST['text_name']);

        if ($results) {
            $_SESSION['server_error'] = 'Esse nome já está no banco de dados';
            $this->add_new_agent_frm();
            return;
        }

        if (!empty($server_error)) {
            $_SESSION['server_error'] = $server_error;
            $this->add_new_agent_frm();
            return;
        }

        $results = $model->add_agent($_POST);

        if($results['status'] == 'error')
        {
            logger(get_user_session() . "- Aconteceu um erro na criação de novo registro de agente.");
            header('location: index.php');
        }

        $url = explode('?', $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        // Busca o endereço por completo para saber aonde está
        $url = $url[0] . '?ct=main&mt=define_password&purl=' . $results['purl'];
        $email = new sendEmail();

        $data = [
            'from' => 'mathesmendelopes@gmail.com',
            'to' => 'consolezone76@gmail.com',
            'link' => $url
        ];

        $results = $email->send_email(
            APP_NAME,
            '<p>Para concluir o processo de registro de agente, clique no link abaixo:</p>
             <a href="'.$data['link'].'">Concluir registro de agente</a>',
            $data
        );
        

        if($results['status'] == 'error')
        {
            logger(get_user_session() . "- Não foi possível enviar o email para conclusão de registro." . $_POST['text_name']); 
            die($results['message']);
        }

        logger(get_user_session() . "- Enviado com sucesso para conclusão do registro." . $_POST['text_name']);

        $data['user'] = $_SESSION['user'];
        $data['email'] = $_POST['text_name'];

        $this->views_with_navbar('agents_email_sent', $data);
    }

    public function delete_agent($id)
    {
        $this->check();

        

        $model = new adminModel();
        $results = $model->get_agent_data_and_total_clients(decrypt($id));

        $data['agent'] = $results;
        $data['user'] = $_SESSION['user'];

        $this->views_with_navbar('agents_delete_confirmation', $data);
    }

    public function confirm_delete($id)
    {
        $this->check();
        $model = new adminModel();
        $model->delete_agent(decrypt($id));
        logger("Agente com o id " .  decrypt($id) . "  foi eliminado");
        header('location: ?ct=admin&mt=agents_management');
    }

    public function edit_agent_frm($id)
    {

        $this->check();
        
        $data['user'] = $_SESSION['user'];
        $model = new adminModel();


        $results = $model->get_agent_data(decrypt($id));

        $data['agent_data'] = $results->results[0];

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] =  $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->views_with_navbar('agents_edit_frm', $data);
    }

    public function edit_agent_submit($id)
    {
        $this->check();

        $name = $_POST['text_name'];
        

        $model = new adminModel();

        $results = $model->check_name($name);

        if($results == true){
            $_SESSION['server_error'] = ["Já tem um agente com esse nome"];
            $this->edit_agent_frm($id);
            return;
        }

        if(!filter_var($name, FILTER_VALIDATE_EMAIL)){
            $_SESSION['server_error'] = ["Coloque um email valido"];
            $this->edit_agent_frm($id);
            return;
        }

        $results = $model->edit_agent(decrypt($id));


        $this->agents_management();

        
    }

    public function edit_recover($id)
    {
        $this->check();

        $data['user'] = $_SESSION['user'];
        $data['id'] = decrypt($id);

        $model = new adminModel();
        $results = $model->get_agent_data_and_total_clients(decrypt($id));

        $data['agent'] = $results[0];


        $this->views_with_navbar('agents_recover_confirmation', $data);
    }

    public function recover_submit($id)
    {
        $this->check();

        $model = new adminModel();
        $model->recover(decrypt($id));
        logger("Agente com o id " .  decrypt($id) . " foi recuperado");

        $this->agents_management();
    }

    public function export_to_xlsx()
    {
        $this->check();

        $model = new adminModel();
        $results = $model-> get_agents_for_management();

        // add header to collection
        $data[] = ['name', 'profile', 'last_login', 'created_at', 'updated_at', 'deleted_at'];

        // place all clients in the $data collection
        foreach ($results->results as $client) {

            // remove the first property (id)
            unset($client->id);
            unset($client->passwrd);

            // add data as array (original $client is a stdClass object)
            $data[] = (array)$client;
        }

        // store the data into the XSLX file
        $filename = 'output_agents_management' . time() . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'dados');
        $spreadsheet->addSheet($worksheet);
        $worksheet->fromArray($data);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        $writer->save('php://output');


        logger(get_user_session() . " Fez download da lista de agentes para o ficheiro: " . $filename . " | total: " . count($data) - 1 . " registros.");
    }

    

}
