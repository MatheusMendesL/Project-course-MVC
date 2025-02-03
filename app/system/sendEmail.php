<?php

namespace bng\System;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class sendEmail
{
    public function send_email($subject, $body, $data)
    {
        $mail = new PHPMailer(true);

        try{
            $mail->IsSMTP();
            $mail->Host = Mailer_Host;
            $mail->SMTPAuth = true;
            $mail->Username = Mailer_Username;
            $mail->Password = Mailer_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = Mailer_port;
            $mail->CharSet = Mailer_charset;

            $mail->setFrom($data['from']);
            $mail->addAddress($data['to']);


            $mail->isHTML(true);

            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();

            return [
                'status' => 'sucesso'
            ];

        }  catch(Exception $e)
        {
            return [
                'status' => 'error',
                'message' => $mail->ErrorInfo
            ];
        }
    }
}