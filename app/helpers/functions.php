<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

function printData($data, $die = true)
{
    echo '<pre>';
    if (is_object($data) or is_array($data)) {
        print_r($data);
    } else {
        echo $data;
    }

    if ($die) {
        die('<br> FIM <br>');
    }
}


//Usado basicamente para ver o retorno de uma variavel ou algo do tipo, é +/- util
function logger($mensagem = '', $level = 'info')
{
    $log = new logger('app_logs');
    $log->pushHandler(new StreamHandler(LOGS_PATH));
    $log->$level($mensagem);
}

function check_session()
{
    return isset($_SESSION['user']);
}


function encrypt($value)
{
    // Encriptar 
    // binario para hexidacemal o bin2hex
    // SSLKEY é uma chave aleatória
    return bin2hex(openssl_encrypt($value, 'aes-256-cbc', OPENSSL_KEY, OPENSSL_RAW_DATA, OPENSSL_IV));
}

function decrypt($value)
{
    if (strlen($value) % 2 != 0) {
        return false;
    }

    // Decriptar
    // Hexadecimal para binario com o hex2bin
    return openssl_decrypt(hex2bin($value), 'aes-256-cbc', OPENSSL_KEY, OPENSSL_RAW_DATA, OPENSSL_IV);
}

function get_user_session()
{
    return $_SESSION['user']->name;
}
