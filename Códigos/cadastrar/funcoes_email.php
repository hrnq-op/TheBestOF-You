<?php
// Carrega o autoloader do PHPMailer
require_once '../email/vendor/autoload.php'; // Ajuste o caminho se necessário

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Gera um token de verificação criptograficamente seguro.
 *
 * @return string O token em formato hexadecimal.
 */
function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Envia o e-mail de verificação para o usuário.
 *
 * @param string $recipientEmail E-mail do destinatário.
 * @param string $username Nome do usuário.
 * @param string $token Token de verificação.
 * @return bool Retorna true se o e-mail foi enviado, false caso contrário.
 */
function sendVerificationEmail($recipientEmail, $username, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thebestofyousuporte@gmail.com'; // SEU EMAIL DE APP
        $mail->Password   = 'pxyn ptoj bgml doqx';           // SUA SENHA DE APP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Remetente e Destinatário
        $mail->setFrom('thebestofyousuporte@gmail.com', 'The Best Of-YOU');
        $mail->addAddress($recipientEmail, $username);

        // Conteúdo do E-mail
        $mail->isHTML(true);
        $mail->Subject = 'Ative sua conta - The Best Of-YOU';

        // Link de verificação. Altere o domínio quando for para produção.
        // O arquivo alvo é o 'verify.php' que criaremos a seguir.
        $verificationLink = "http://localhost/TheBestOF-You/Códigos/cadastrar/verify.php?token=" . urlencode($token);
        
        // Corpo do e-mail
        $mail->Body = "Olá <b>$username</b>,<br><br>Bem-vindo(a) ao The Best Of-YOU! Clique no link abaixo para verificar seu e-mail e ativar sua conta:<br><br><a href=\"$verificationLink\" style='padding:10px 20px; background-color:#007BFF; color:white; text-decoration:none; border-radius:5px; font-size:16px;'>Verificar meu E-mail</a><br><br>Se você não se cadastrou, por favor, ignore este e-mail.";
        
        $mail->AltBody = "Olá $username,\n\nPara verificar seu e-mail e ativar sua conta, copie e cole o seguinte link no seu navegador:\n$verificationLink";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Em um ambiente de produção, seria bom logar o erro: error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
