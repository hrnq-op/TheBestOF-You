<?php

require_once 'email/vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Gera um token de verificação criptograficamente seguro.
 * @return string O token em formato hexadecimal.
 */
function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Envia o e-mail de verificação para o usuário.
 * @param string $recipientEmail E-mail do destinatário.
 * @param string $username Nome do usuário.
 * @param string $token Token de verificação.
 * @return bool Retorna true se o e-mail foi enviado, false caso contrário.
 */
function sendVerificationEmail($recipientEmail, $username, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thebestofyousuporte@gmail.com'; // SEU EMAIL
        $mail->Password   = 'pxyn ptoj bgml doqx';           // SUA SENHA DE APP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Remetente e Destinatário
        $mail->setFrom('thebestofyousuporte@gmail.com', 'The Best Of-YOU');
        $mail->addAddress($recipientEmail, $username);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Ative sua conta - The Best Of-YOU';
        
        $verificationLink = "http://localhost/TheBestOF-You/Códigos/cadastrar/verify.php?token=" . urlencode($token);
        
        $mail->Body = "Olá <b>$username</b>,<br><br>Bem-vindo(a)! Clique no link abaixo para verificar seu e-mail e ativar sua conta:<br><br><a href=\"$verificationLink\" style='padding:10px 20px; background-color:#00c853; color:white; text-decoration:none; border-radius:5px;'>Verificar meu E-mail</a>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Para depuração, você pode ver o erro: error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

/* ================================================================= */
/* FIM DAS FUNÇÕES DE E-MAIL                                        */
/* ================================================================= */


/* ================================================================= */
/* INÍCIO DA LÓGICA DE CADASTRO                                     */
/* ================================================================= */

// Inclui a conexão com o banco de dados.
// Verifique se este caminho está correto.
require_once '../conexao.php'; 

// Variável para mensagens
$mensagem = '';

function limpar_texto($str) {
    return preg_replace("/[^0-9]/", "", $str);
}

// Processa o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $erro = false;
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $senha_pura = $_POST['senha'] ?? '';

    // Validações
    if (empty($nome) || empty($email) || empty($senha_pura)) {
        $erro = "Todos os campos (Nome, E-mail e Senha) são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Forneça um endereço de e-mail válido.";
    }

    // Se passou na validação inicial, verifica o e-mail no banco
    if (!$erro) {
        $stmt_check = $conexao->prepare("SELECT email FROM usuario WHERE email = ? UNION SELECT email FROM usuarios_pendentes WHERE email = ?");
        $stmt_check->bind_param("ss", $email, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $erro = "Este endereço de e-mail já está em uso.";
        }
        $stmt_check->close();
    }

    // Se existe algum erro, mostra na tela
    if ($erro) {
        $mensagem = "<p class='mensagem-erro'><b>Erro:</b> $erro</p>";
    } else {
        // Se tudo estiver OK, continua o processo
        $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);
        $token = generateVerificationToken(); // AGORA A FUNÇÃO SERÁ ENCONTRADA!
        $telefone_limpo = limpar_texto($telefone);

        $stmt_insert = $conexao->prepare("INSERT INTO usuarios_pendentes (nome, email, telefone, senha_hash, verification_token) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssss", $nome, $email, $telefone_limpo, $senha_hash, $token);

        if ($stmt_insert->execute()) {
            if (sendVerificationEmail($email, $nome, $token)) {
                $mensagem = "<p class='mensagem-sucesso'><b>Cadastro quase concluído!</b><br>Enviamos um link de ativação para <b>$email</b>. Por favor, verifique sua caixa de entrada e spam.</p>";
                $_POST = []; // Limpa o formulário
            } else {
                $conexao->query("DELETE FROM usuarios_pendentes WHERE email = '$email'");
                $mensagem = "<p class='mensagem-erro'><b>Erro Crítico:</b> Não foi possível enviar o e-mail de verificação. Tente novamente.</p>";
            }
        } else {
            $mensagem = "<p class='mensagem-erro'><b>Erro de Banco de Dados:</b> Não foi possível processar seu cadastro. " . $stmt_insert->error . "</p>";
        }
        $stmt_insert->close();
    }
    $conexao->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="cadastrar.css?=4">
    <style>
        .mensagem-erro { border: 1px solid #d9534f; background-color: #f2dede; color: #a94442; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .mensagem-sucesso { border: 1px solid #5cb85c; background-color: #dff0d8; color: #3c763d; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>
    <a href="../pagina_principal/pagina_principal.php" class="botao-voltar" title="Voltar para a página inicial">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div class="form-container">
        <h1>Cadastrar Usuário</h1>
        
        <?php if (!empty($mensagem)) echo $mensagem; ?>

        <form method="POST" action="">
            <label>Nome:</label>
            <input value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" type="text" name="nome" required>

            <label>E-mail:</label>
            <input value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" type="email" name="email" required>

            <label>Telefone (Opcional):</label>
            <input value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>" placeholder="(12) 98888-8888" type="text" name="telefone">

            <label>Senha:</label>
            <input type="password" name="senha" required>

            <button type="submit" name="enviar">Cadastrar</button>
            <br><br>
            <p>Já possui uma conta?</p>
            <div class="login-link">
                <a href="../login/login.php">Login</a>
            </div>
        </form>
    </div>
</body>
</html>
