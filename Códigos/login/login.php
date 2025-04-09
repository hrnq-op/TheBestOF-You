<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="login.css">
</head>

<header>

    <h1 class="titulo">The Best Of-YOU</h1>

</header>

<body style="background-color: #2c2c54;">

    <form method="post" action="">

        <h1>login</h1>

        <div class="input-container">
            <i class="fas fa-envelope"></i>
            <input value="<?php if (isset($_POST['email'])) echo $_POST['email']; ?>" type="text" name="email" placeholder="E-mail">
        </div>

        <div class="input-container">
            <i class="fas fa-lock"></i>
            <input value="<?php if (isset($_POST['senha'])) echo $_POST['senha']; ?>" type="password" name="senha" placeholder="Senha">
        </div>

        <div class="buttonLogin">
            <button type="submit">Login</button>
        </div>

        <div class="cadastroLogin">
            <span>Não Possui Conta? <a href="../cadastrar/cadastrar.php">Cadastre-se</a></span>
        </div>

    </form>

</body>

</html>




<?php
session_start();

if (count($_POST) > 0) {

    include('../conexao.php');
    $erro = false;

    $senha = $_POST['senha'];
    $email = $_POST['email'];

    $sql_code = "SELECT * FROM usuario WHERE email = '$email' LIMIT 1";
    $sql_exec = $conexao->query($sql_code) or die($conexao->error);
    $usuario = $sql_exec->fetch_assoc();

    if ($usuario && password_verify($senha, $usuario['senha'])) {

        // Armazena o ID do usuário na sessão
        $_SESSION['usuario_id'] = $usuario['id_usuario'];

        // Redireciona para a página usuario.php após o login bem-sucedido
        header("Location: ../usuario/usuario.php");
        exit();
    } else {
        echo "Falha no login!! Senha ou E-mail incorretos";
    }
}
?>