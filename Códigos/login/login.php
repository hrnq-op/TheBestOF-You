<?php
// ===================================================================================
// PARTE 1: LÓGICA PHP - Deve vir ANTES de qualquer código HTML
// ===================================================================================

// Inicia a sessão obrigatoriamente no topo do arquivo.
session_start();

// Inclui o autoloader do Composer para o PHPMailer.
// O caminho '../' significa que a pasta 'vendor' está um nível acima da pasta atual.
require_once 'email/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Funções para Envio de E-mail (copiadas da nossa conversa anterior) ---

function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

function saveVerificationToken($conn, $userId, $token) {
    // Atualiza o token do usuário e ZERA o status de verificação.
    // Usamos 'prepared statements' (?) para segurança máxima.
    $stmt = $conn->prepare("UPDATE usuario SET verification_token = ?, email_verified_at = NULL WHERE id_usuario = ?");
    $stmt->bind_param("si", $token, $userId); // 's' para string (token), 'i' para integer (id)
    return $stmt->execute();
}

function sendVerificationEmail($recipientEmail, $username, $token) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thebestofyousuporte@gmail.com'; // SEU EMAIL
        $mail->Password   = 'pxyn ptoj bgml doqx';      // SUA SENHA DE APP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('thebestofyousuporte@gmail.com', 'The Best Of-YOU');
        $mail->addAddress($recipientEmail, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Ative sua conta - The Best Of-YOU';
        // Altere 'localhost/seu_projeto' para o caminho real do seu site quando for para produção
        $verificationLink = "http://localhost/Projetos/TheBestOF-You/TheBestOF-You/Códigos/login/verify.php?token=" . urlencode($token);
        $mail->Body    = "Olá <b>$username</b>,<br><br>Bem-vindo(a) ao The Best Of-YOU! Clique no link abaixo para verificar seu e-mail e ativar sua conta:<br><br><a href=\"$verificationLink\" style='padding:10px 15px; background-color:#007BFF; color:white; text-decoration:none; border-radius:5px;'>Verificar meu E-mail</a>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}


// --- Processamento Principal do Formulário ---

// Verifica se o formulário foi enviado (se há dados no POST)
if (count($_POST) > 0) {

    // Inclui a conexão com o banco
    include('../conexao.php'); 

    $email_digitado = $_POST['email'];
    $senha_digitada = $_POST['senha'];

    // Usando Prepared Statements para segurança
    $sql_code = "SELECT id_usuario, nome, email, senha, email_verified_at FROM usuario WHERE email = ? LIMIT 1";
    $stmt = $conexao->prepare($sql_code);
    $stmt->bind_param("s", $email_digitado);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    // 1. VERIFICA SE O USUÁRIO EXISTE
    if ($usuario) {
        
        // 2. SE EXISTE, VERIFICA SE A SENHA ESTÁ CORRETA
        if (password_verify($senha_digitada, $usuario['senha'])) {
            
            // Senha correta! Ótimo.
            // 3. AGORA, DENTRO DA SENHA CORRETA, VERIFICA O STATUS DO E-MAIL
            if ($usuario['email_verified_at'] !== null) {
                
                // 3a. E-MAIL JÁ VERIFICADO: Login normal.
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                header("Location: ../pagina_principal/index.php");
                exit();

            } else {
   // 3b. E-MAIL AINDA NÃO VERIFICADO: Envia o e-mail de verificação.
    $token = generateVerificationToken();
    if (saveVerificationToken($conexao, $usuario['id_usuario'], $token)) {
        if (sendVerificationEmail($usuario['email'], $usuario['nome'], $token)) {
            // Redireciona de volta para o login com mensagem de sucesso
            header("Location: login.php?status=verification_sent");
            exit();
        }
    }
    // Se falhar no processo de envio, redireciona com erro de e-mail
    header("Location: login.php?status=email_error");
    exit();
}

        } else {
            // Senha incorreta  
            header("Location: login.php?status=error");
            exit();
        }

    } else {
        // Email não encontrado no banco de dados
        header("Location: login.php?status=error");
        exit();
    }
}
?>

<?php
// ===================================================================================
// PARTE 2: CÓDIGO HTML - Exibição da página
// ===================================================================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="login.css?=3">
    <style>
        /* Estilos para as mensagens de feedback */
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; }
        .sucesso { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <a href="../pagina_principal/pagina_principal.php" class="botao-voltar" title="Voltar para a página inicial">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="form-section">
        <form method="post" action="login.php"> <?php /* O action aponta para o próprio arquivo */ ?>
            <h1 class="titulo">The Best Of-YOU</h1>
            <h1>Login</h1>

            <?php
            // Bloco para exibir mensagens de status vindas da URL
            if (isset($_GET['status'])):
                if ($_GET['status'] == 'verification_sent'):
                    echo '<div class="mensagem sucesso">Conta encontrada! Um e-mail de verificação foi enviado para sua caixa de entrada.</div>';
                elseif ($_GET['status'] == 'error'):
                    echo '<div class="mensagem erro">E-mail ou senha incorretos. Tente novamente.</div>';
                 elseif ($_GET['status'] == 'email_error'):
                    echo '<div class="mensagem erro">Não foi possível enviar o e-mail de verificação. Tente novamente mais tarde.</div>';
                elseif ($_GET['status'] == 'verified'):
                     echo '<div class="mensagem sucesso">Seu e-mail foi verificado com sucesso! Por favor, faça o login.</div>';
                endif;
            endif;
            ?>

            <div class="input-container">
                <i class="fas fa-envelope"></i>
                <input value="<?php if (isset($_POST['email'])) echo htmlspecialchars($_POST['email']); ?>" type="text" name="email" placeholder="E-mail" required>
            </div>

            <div class="input-container">
                <i class="fas fa-lock"></i>
                <?php /* Removido o 'value' do campo de senha por segurança */ ?>
                <input type="password" name="senha" placeholder="Senha" required>
            </div>

            <div class="buttonLogin">
                <button type="submit">Login</button>
            </div>

            <div class="cadastroLogin">
                <span>Não possui conta?</span><br>
                <span><a href="../cadastrar/cadastrar.php">Cadastre-se</a></span>
            </div>
        </form>
    </div>
</body>
</html>