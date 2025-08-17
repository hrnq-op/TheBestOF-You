<?php
// --- CONFIGURAÇÃO E INICIALIZAÇÃO ---
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../email/vendor/autoload.php';
include('../../conexao.php');

$modo = 'solicitar';
$mensagem_feedback = '';
$tipo_mensagem = 'success'; // 'success' ou 'error'
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
        $tipo_mensagem = 'error';
        $mensagem_feedback = 'Este link de redefinição é inválido ou expirou. Por favor, solicite um novo.';
    } elseif ($_POST['senha'] !== $_POST['confirma_senha']) {
        $token_from_url = $token;
        $token_valido = true;
        $tipo_mensagem = 'error';
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
            $tipo_mensagem = 'success';
            $mensagem_feedback = 'Sua senha foi redefinida com sucesso! Você já pode <a href="../login.php">fazer login</a>.';
        } else {
            $modo = 'mensagem';
            $tipo_mensagem = 'error';
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

    if ($user) {
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
            $reset_link = "http://localhost/TheBestOF-You/Códigos/login/senha/gerenciar_senha.php?token=" . $token;
            $mail->Body    = "Olá,<br><br>Recebemos uma solicitação para redefinir sua senha. Clique no botão abaixo para criar uma nova senha:<br><br>";
            $mail->Body   .= "<a href='$reset_link' style='background-color: #00c853; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Redefinir Senha Agora</a><br><br>";
            $mail->Body   .= "Se você não solicitou isso, por favor, ignore este e-mail.<br>";
            $mail->AltBody = "Para redefinir sua senha, copie e cole este link no seu navegador: " . $reset_link;
            $mail->send();
            
            $tipo_mensagem = 'success';
            $mensagem_feedback = 'E-mail de recuperação enviado! Verifique sua caixa de entrada e pasta de spam.';

        } catch (Exception $e) {
            $tipo_mensagem = 'error';
            $mensagem_feedback = "A mensagem não pôde ser enviada. Erro do Mailer: {$mail->ErrorInfo}";
        }
        $modo = 'mensagem';

    } else {
        header("Location: ../../cadastrar/cadastrar.php");
        exit();
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
        $tipo_mensagem = 'error';
        $mensagem_feedback = 'Este link de redefinição de senha é inválido.';
    } else {
        $fuso_horario_sp = new DateTimeZone('America/Sao_Paulo');
        $agora_sp = new DateTime('now', $fuso_horario_sp);
        $data_expiracao_utc = new DateTime($user['reset_token_expires_at'], new DateTimeZone('UTC'));

        if ($agora_sp > $data_expiracao_utc) {
            $modo = 'mensagem';
            $tipo_mensagem = 'error';
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
    <!-- Links para Fontes e Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Link para o CSS externo -->
    <link rel="stylesheet" href="gerenciar_senha.css?v=2">
</head>
<body>

<div class="form-container">
    <!-- HTML do Spinner -->
    <div class="spinner-overlay" id="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <a href="../login.php" class="botao-voltar" title="Voltar para o Login">
        <i class="fas fa-arrow-left"></i>
    </a>

    <?php // --- LÓGICA DE EXIBIÇÃO --- ?>

    <?php if ($modo === 'solicitar'): ?>
        <h1>Recuperar Senha</h1>
        <p>Digite seu e-mail para receber um link de redefinição.</p>
        <!-- ID adicionado ao formulário para o JavaScript -->
        <form action="gerenciar_senha.php" method="POST" id="solicitar-form">
            <div class="input-container">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="seuemail@exemplo.com">
            </div>
            <button type="submit">Enviar Link</button>
        </form>

    <?php elseif ($modo === 'redefinir' && $token_valido): ?>
        <h1>Crie uma Nova Senha</h1>
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="feedback-message <?php echo $tipo_mensagem; ?>">
                <p><?php echo $mensagem_feedback; ?></p>
            </div>
        <?php endif; ?>
        <form action="gerenciar_senha.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token_from_url); ?>">
            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" name="senha" required placeholder="Digite a nova senha">
            </div>
            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirma_senha" required placeholder="Confirme a nova senha">
            </div>
            <button type="submit">Salvar Nova Senha</button>
        </form>

    <?php elseif ($modo === 'mensagem'): ?>
        <h1>Aviso</h1>
        <div class="feedback-message <?php echo $tipo_mensagem; ?>">
            <p><?php echo $mensagem_feedback; ?></p>
        </div>
    <?php endif; ?>

</div>

<!-- JavaScript para controlar o Spinner -->
<script>
    const solicitarForm = document.getElementById('solicitar-form');
    const spinnerOverlay = document.getElementById('spinner-overlay');

    // Adiciona o listener apenas se o formulário de solicitação existir na página
    if (solicitarForm) {
        solicitarForm.addEventListener('submit', function(event) {
            const emailInput = solicitarForm.querySelector('input[name="email"]');
            
            // Valida se o email não está vazio para exibir o spinner
            if (emailInput.value.trim() !== '') {
                if(spinnerOverlay) {
                   spinnerOverlay.style.display = 'flex';
                }
            }
        });
    }
</script>

</body>
</html>
