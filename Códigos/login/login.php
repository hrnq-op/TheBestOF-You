<?php
// ===================================================================================
// PARTE 1: LÓGICA PHP
// ===================================================================================

// Inicia a sessão obrigatoriamente no topo do arquivo.
session_start();

// Inclui a conexão com o banco
include('../conexao.php'); 

$mensagem_erro = ""; // Variável para armazenar mensagens de erro a serem exibidas

// --- Processamento Principal do Formulário ---

// Verifica se o formulário foi enviado (se há dados no POST)
if (count($_POST) > 0) {
    $email_digitado = trim($_POST['email']);
    $senha_digitada = trim($_POST['senha']);

    // Validação básica se os campos estão vazios
    if (empty($email_digitado) || empty($senha_digitada)) {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    } else {
        // Usando Prepared Statements para segurança.
        $sql_code = "SELECT id_usuario, nome, email, senha FROM usuario WHERE email = ? LIMIT 1";
        $stmt = $conexao->prepare($sql_code);
        
        if (!$stmt) {
            $mensagem_erro = "Erro na preparação da consulta: " . $conexao->error;
            error_log("Erro de preparação de consulta no login: " . $conexao->error); // Log para depuração
        } else {
            $stmt->bind_param("s", $email_digitado);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            $stmt->close(); // Fecha o statement após obter o resultado

            // 1. VERIFICA SE O USUÁRIO EXISTE E SE A SENHA ESTÁ CORRETA
            // Usando password_verify() para senhas hashed (MELHOR PRÁTICA!)
            if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
                
                // Login bem-sucedido.
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nome_usuario'] = $usuario['nome']; // Guarda o nome na sessão para o header

                // --- NOVO TRECHO: REGISTRAR SESSÃO ATIVA NO BANCO DE DADOS ---
                $id_usuario_logado = $_SESSION['id_usuario'];
                $session_id_atual = session_id(); // Obtém o ID da sessão PHP atual

                // Captura informações do dispositivo/navegador e IP
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

                // Opcional: Deleta registros antigos com o mesmo session_id para evitar duplicatas em caso de re-login rápido na mesma sessão
                $stmt_delete_old_session = $conexao->prepare("DELETE FROM sessoes_ativas WHERE session_id = ?");
                if ($stmt_delete_old_session) {
                    $stmt_delete_old_session->bind_param("s", $session_id_atual);
                    $stmt_delete_old_session->execute();
                    $stmt_delete_old_session->close();
                } else {
                    error_log("Erro na preparação DELETE OLD SESSION no login: " . $conexao->error);
                }

                // Insere o novo registro de sessão ativa
                $stmt_insert_session = $conexao->prepare("INSERT INTO sessoes_ativas (id_usuario, session_id, user_agent, ip_address) VALUES (?, ?, ?, ?)");
                if ($stmt_insert_session) {
                    $stmt_insert_session->bind_param("isss", $id_usuario_logado, $session_id_atual, $user_agent, $ip_address);
                    
                    if ($stmt_insert_session->execute()) {
                        $stmt_insert_session->close();
                        // --- REDIRECIONAMENTO FINAL APÓS SUCESSO ---
                        header("Location: ../pagina_principal/index.php"); // Redireciona para o index.php
                        exit(); // CRÍTICO: Termina a execução do script para garantir o redirecionamento
                    } else {
                        $mensagem_erro = "Erro ao registrar a sessão no banco de dados. Por favor, tente novamente.";
                        error_log("Erro ao inserir sessão no DB durante login: " . $stmt_insert_session->error);
                    }
                } else {
                    $mensagem_erro = "Erro na preparação da inserção de sessão. Por favor, contate o suporte.";
                    error_log("Erro na preparação INSERT SESSION no login: " . $conexao->error);
                }

            } else {
                $mensagem_erro = "E-mail ou senha incorretos."; // Senha incorreta
            }
        }
    }
}

// Bloco para exibir mensagens de status vindas da URL (de outras páginas, como index.php ou perfil.php)
// Essas mensagens são geralmente para informar o usuário sobre o motivo do logout/redirecionamento.
if (isset($_GET['sessao_invalida']) && $_GET['sessao_invalida'] == 'true') {
    $mensagem_erro = "Sua sessão expirou ou foi invalidada. Faça login novamente.";
}
if (isset($_GET['logout_remoto']) && $_GET['logout_remoto'] == 'true') {
    $mensagem_erro = "Você foi desconectado remotamente. Faça login novamente.";
}
if (isset($_GET['excluido']) && $_GET['excluido'] == 'true') {
    $mensagem_erro = "Sua conta foi excluída com sucesso."; // Mudado para erro, pois é uma ação final.
    // Ou você pode ter uma div de sucesso dedicada para isso, se a estética permitir
    // Ex: <div class="mensagem sucesso">Sua conta foi excluída com sucesso.</div>
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
    <link rel="stylesheet" type="text/css" href="login.css"> 
    <style>
        /* Estilos para as mensagens de feedback - Mantenha este bloco ou mova para login.css */
        .mensagem { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; }
        .sucesso { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    
    <div class="form-container"> 

        <form method="post" action="login.php">
            <a href="../pagina_principal/pagina_principal.php" class="botao-voltar" title="Voltar para a página inicial">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="login-titulo">Login</h1>

            <?php
            // Exibe a mensagem de erro se houver
            if (!empty($mensagem_erro)):
                echo '<div class="mensagem erro">' . htmlspecialchars($mensagem_erro) . '</div>';
            endif;
            ?>

            <div class="input-container">
                <i class="fas fa-envelope"></i>
                <input value="<?php if (isset($_POST['email_digitado'])) echo htmlspecialchars($_POST['email_digitado']); ?>" type="text" name="email" placeholder="E-mail" required>
            </div>

            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" name="senha" id="senha" placeholder="Senha" required>
                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            </div>

            <div class="cadastroLogin">
                <span>Esqueceu a senha?</span><br>
                <span><a href="senha/gerenciar_senha.php">Redefine-a</a></span>
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

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('senha'); 

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>