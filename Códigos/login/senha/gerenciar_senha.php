<?php
// Arquivo: gerenciar_senha.php (Versão Unificada com Redirecionamento)

// --- CONFIGURAÇÃO E INICIALIZAÇÃO ---
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../email/vendor/autoload.php';
include('../../conexao.php');

$modo = 'solicitar';
$mensagem_feedback = '';
$token_valido = false;
$token_from_url = '';

// --- ROTEADOR PRINCIPAL: Decide o que fazer com base na requisição ---

// 1. Processar o envio da NOVA SENHA (via POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token'], $_POST['senha'], $_POST['confirma_senha'])) {
    $modo = 'redefinir';
    $token = $_POST['token'];
    $token_hash = hash('sha256', $token);

    $stmt = $conexao->prepare("SELECT id_usuario, reset_token_expires_at FROM usuario WHERE reset_token_hash = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $expirado = true;
    if ($user && $user['reset_token_expires_at'] !== NULL) {
        $data_expiracao_utc = new DateTime($user['reset_token_expires_at'], new DateTimeZone('UTC'));
        $agora_sp = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        if ($agora_sp <= $data_expiracao_utc) {
            $expirado = false;
        }
    }
    
    if (!$user || $expirado) {
        $modo = 'mensagem';
        $mensagem_feedback = 'Este link de redefinição é inválido ou expirou. Por favor, solicite um novo.';
    } elseif ($_POST['senha'] !== $_POST['confirma_senha']) {
        $token_from_url = $token;
        $token_valido = true;
        $mensagem_feedback = 'As senhas não coincidem. Tente novamente.';
    } else {
        $nova_senha = $_POST['senha'];
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $stmt_update = $conexao->prepare(
            "UPDATE usuario SET senha = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id_usuario = ?"
        );
        $stmt_update->bind_param("si", $senha_hash, $user['id_usuario']);
        
        if ($stmt_update->execute()) {
            $modo = 'mensagem';
            $mensagem_feedback = 'Sua senha foi redefinida com sucesso! Você já pode <a href="../login.php">fazer login</a>.';
        } else {
            $modo = 'mensagem';
            $mensagem_feedback = 'Ocorreu um erro ao atualizar sua senha. Por favor, tente novamente.';
        }
    }
}
// 2. Processar a solicitação de RESET DE SENHA (via POST com email)
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = $_POST['email'];
    $stmt = $conexao->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verifica se o e-mail foi encontrado no banco de dados
    if ($user) {
        // --- EMAIL ENCONTRADO ---
        // A lógica para gerar o token e enviar o e-mail continua a mesma
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);

        $fuso_horario_sp = new DateTimeZone('America/Sao_Paulo');
        $data_expiracao = new DateTime('now', $fuso_horario_sp);
        $data_expiracao->add(new DateInterval('PT1H'));
        $data_expiracao->setTimezone(new DateTimeZone('UTC'));
        $expire_date_string_para_o_banco = $data_expiracao->format('Y-m-d H:i:s');

        $stmt_update = $conexao->prepare(
            "UPDATE usuario SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id_usuario = ?"
        );
        $stmt_update->bind_param("ssi", $token_hash, $expire_date_string_para_o_banco, $user['id_usuario']);
        $stmt_update->execute();
        
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'thebestofyousuporte@gmail.com';
            $mail->Password   = 'pxyn ptoj bgml doqx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('thebestofyousuporte@gmail.com', 'The Best Of-YOU');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de Senha';
            $reset_link = "http://localhost/TheBestOF-You/Códigos/login/senha/gerenciar_senha.php?token=" . $token; // Link para este mesmo arquivo
            $mail->Body    = "Olá,<br><br>Recebemos uma solicitação para redefinir sua senha. Clique no botão abaixo para criar uma nova senha:<br><br>";
            $mail->Body   .= "<a href='$reset_link' style='background-color: #00c853; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Redefinir Senha Agora</a><br><br>";
            $mail->Body   .= "Se você não solicitou isso, por favor, ignore este e-mail.<br>";
            $mail->AltBody = "Para redefinir sua senha, copie e cole este link no seu navegador: " . $reset_link;
            $mail->send();
            
            $mensagem_feedback = 'E-mail de recuperação enviado com sucesso! Verifique sua caixa de entrada e pasta de spam.';

        } catch (Exception $e) {
            $mensagem_feedback = "A mensagem não pôde ser enviada. Erro do Mailer: {$mail->ErrorInfo}";
        }
        $modo = 'mensagem';

    } else {
        // --- ALTERAÇÃO APLICADA AQUI ---
        // Se o e-mail NÃO for encontrado, redireciona para a página de cadastro.
        header("Location: ../../cadastrar/cadastrar.php");
        exit(); // Encerra o script para garantir que o redirecionamento ocorra.
    }
}
// 3. Validar o TOKEN da URL (via GET)
elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
    $token_from_url = $_GET['token'];
    $token_hash = hash('sha256', $token_from_url);

    $stmt = $conexao->prepare("SELECT id_usuario, reset_token_expires_at FROM usuario WHERE reset_token_hash = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || $user['reset_token_expires_at'] === NULL) {
        $modo = 'mensagem';
        $mensagem_feedback = 'Este link de redefinição de senha é inválido.';
    } else {
        $fuso_horario_sp = new DateTimeZone('America/Sao_Paulo');
        $agora_sp = new DateTime('now', $fuso_horario_sp);
        $data_expiracao_utc = new DateTime($user['reset_token_expires_at'], new DateTimeZone('UTC'));

        if ($agora_sp > $data_expiracao_utc) {
            $modo = 'mensagem';
            $mensagem_feedback = 'Este link de redefinição de senha expirou. Por favor, <a href="gerenciar_senha.php">solicite um novo</a>.';
        } else {
            $modo = 'redefinir';
            $token_valido = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Senha</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f0f2f5; }
        .container { background: white; padding: 2rem 3rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; width: 100%; max-width: 420px; }
        h1 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 1.5rem; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #00c853; color: white; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; transition: background-color 0.2s; }
        button:hover { background-color: #00c853; }
        .feedback-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 1rem; border-radius: 5px; margin-top: 1rem; }
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 1rem; border-radius: 5px; margin-top: 1rem; }
        .error-message a, .feedback-message a { color: inherit; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <?php // --- LÓGICA DE EXIBIÇÃO --- ?>

    <?php if ($modo === 'solicitar'): ?>
        <h1>Recuperar Senha</h1>
        <p>Digite seu e-mail para receber um link de redefinição de senha.</p>
        <form action="gerenciar_senha.php" method="POST">
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required placeholder="seuemail@exemplo.com">
            </div>
            <button type="submit">Enviar Link de Recuperação</button>
        </form>

    <?php elseif ($modo === 'redefinir' && $token_valido): ?>
        <h1>Redefinir a sua Senha</h1>
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="error-message"><?php echo $mensagem_feedback; ?></div>
        <?php endif; ?>
        <form action="gerenciar_senha.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token_from_url); ?>">
            <div class="form-group">
                <label for="senha">Nova Senha:</label>
                <input type="password" id="senha" name="senha" required placeholder="Digite a nova senha">
            </div>
            <div class="form-group">
                <label for="confirma_senha">Confirmar Nova Senha:</label>
                <input type="password" id="confirma_senha" name="confirma_senha" required placeholder="Confirme a nova senha">
            </div>
            <button type="submit">Salvar Nova Senha</button>
        </form>

    <?php elseif ($modo === 'mensagem'): ?>
        <h1>Aviso</h1>
        <div class="feedback-message">
            <p><?php echo $mensagem_feedback; ?></p>
        </div>
    <?php endif; ?>

</div>

</body>
</html>