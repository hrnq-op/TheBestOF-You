<?php
// ===================================================================================
// PARTE 1: LÓGICA PHP
// ===================================================================================

// Inicia a sessão obrigatoriamente no topo do arquivo.
session_start();

// --- Processamento Principal do Formulário ---

// Verifica se o formulário foi enviado (se há dados no POST)
if (count($_POST) > 0) {

    // Inclui a conexão com o banco
    include('../conexao.php'); 

    $email_digitado = $_POST['email'];
    $senha_digitada = $_POST['senha'];

    // Usando Prepared Statements para segurança.
    // A coluna 'email_verified_at' não é mais necessária para a lógica de login.
    $sql_code = "SELECT id_usuario, nome, email, senha FROM usuario WHERE email = ? LIMIT 1";
    $stmt = $conexao->prepare($sql_code);
    $stmt->bind_param("s", $email_digitado);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    // 1. VERIFICA SE O USUÁRIO EXISTE E SE A SENHA ESTÁ CORRETA
    if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
        
        // Login bem-sucedido.
        // A verificação de e-mail foi removida. O usuário é logado diretamente.
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        header("Location: ../pagina_principal/index.php");
        exit();

    } else {
        // E-mail ou senha incorretos.
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
            // Bloco para exibir mensagens de status vindas da URL.
            // As mensagens relacionadas à verificação de e-mail foram removidas.
            if (isset($_GET['status'])):
                if ($_GET['status'] == 'error'):
                    echo '<div class="mensagem erro">E-mail ou senha incorretos. Tente novamente.</div>';
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
                <span>Esqueceu a senha?</span><br>
                <span><a href="senha/gerenciar_senha.php">Redefine-a</a></span>
            </div>
            <div class="cadastroLogin">
                <span>Não possui conta?</span><br>
                <span><a href="../cadastrar/cadastrar.php">Cadastre-se</a></span>
            </div>
        </form>
    </div>
</body>
</html>
