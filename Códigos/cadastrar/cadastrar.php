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
/* FIM DAS FUNÇÕES DE E-MAIL                                         */
/* ================================================================= */


/* ================================================================= */
/* INÍCIO DA LÓGICA DE CADASTRO                                      */
/* ================================================================= */

// Inclui a conexão com o banco de dados.
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
        // A classe da mensagem agora é 'error' para coincidir com o CSS
        $mensagem = "<p class='error'><b>Erro:</b> $erro</p>";
    } else {
        // Se tudo estiver OK, continua o processo
        $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);
        $token = generateVerificationToken();
        $telefone_limpo = limpar_texto($telefone);

        $stmt_insert = $conexao->prepare("INSERT INTO usuarios_pendentes (nome, email, telefone, senha_hash, verification_token) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssss", $nome, $email, $telefone_limpo, $senha_hash, $token);

        if ($stmt_insert->execute()) {
            if (sendVerificationEmail($email, $nome, $token)) {
                // A classe da mensagem agora é 'success' para coincidir com o CSS
                $mensagem = "<p class='success'><b>Cadastro quase concluído!</b><br>Enviamos um link de ativação para <b>$email</b>. Por favor, verifique sua caixa de entrada e spam.</p>";
                $_POST = []; // Limpa o formulário
            } else {
                $conexao->query("DELETE FROM usuarios_pendentes WHERE email = '$email'");
                // A classe da mensagem agora é 'error' para coincidir com o CSS
                $mensagem = "<p class='error'><b>Erro Crítico:</b> Não foi possível enviar o e-mail de verificação. Tente novamente.</p>";
            }
        } else {
            // A classe da mensagem agora é 'error' para coincidir com o CSS
            $mensagem = "<p class='error'><b>Erro de Banco de Dados:</b> Não foi possível processar seu cadastro. " . $stmt_insert->error . "</p>";
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
    <link rel="stylesheet" href="cadastrar.css?=6"> </head>
<body>
    <div class="form-container">
        <div class="spinner-overlay" id="spinner-overlay">
            <div class="spinner"></div>
        </div>

        <a href="../pagina_principal/pagina_principal.php" class="botao-voltar" title="Voltar para a página inicial">
            <i class="fas fa-arrow-left"></i>
        </a>

        <h1>Cadastrar</h1>
        
        <?php if (!empty($mensagem)) echo $mensagem; ?>

        <form method="POST" action="" id="cadastro-form">
            <div class="input-container">
                <i class="fas fa-user"></i>
                <input value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" type="text" name="nome" placeholder="Nome Completo" required>
            </div>

            <div class="input-container">
                <i class="fas fa-envelope"></i>
                <input value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" type="email" name="email" placeholder="E-mail" required>
            </div>

            <div class="input-container">
                <i class="fas fa-phone"></i>
                <input value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>" placeholder="Telefone (Opcional)" type="text" name="telefone">
            </div>

            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" name="senha" id="senha" placeholder="Senha" required>
                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            </div>

            <button type="submit" name="enviar">Cadastrar</button>
            
            <div class="login-link">
                <p>Já possui uma conta? <a href="../login/login.php">Faça Login</a></p>
            </div>
        </form>
    </div>

    <script>
        const form = document.getElementById('cadastro-form');
        const spinnerOverlay = document.getElementById('spinner-overlay');

        form.addEventListener('submit', function() {
            // Validação simples para checar se os campos principais estão preenchidos
            const nome = form.querySelector('input[name="nome"]').value;
            const email = form.querySelector('input[name="email"]').value;
            const senha = form.querySelector('input[name="senha"]').value;

            if (nome && email && senha) {
                 // Exibe o spinner somente se o formulário for válido do lado do cliente
                spinnerOverlay.style.display = 'flex';
            }
        });

        // JavaScript para o Olho de Senha (NOVO)
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#senha');

        togglePassword.addEventListener('click', function (e) {
            // Alternar o tipo do input entre 'password' e 'text'
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // Alternar o ícone do olho
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>