<?php
session_start(); 

if(count($_POST) > 0 ){

    include('conexao.php');
    $erro = false;
    
    $senha = $_POST['senha'];
    $email = $_POST['email'];
    
    $sql_code = "SELECT * FROM usuario WHERE email = '$email' LIMIT 1";
    $sql_exec = $mysqli->query($sql_code) or die($mysqli->error);
    $usuario = $sql_exec->fetch_assoc();
    
    if($usuario && password_verify($senha, $usuario['senha'])){
        
        $_SESSION['usuario_id'] = $usuario['id_usuario']; 
       
        header("Location: index.php"); 
        exit();
    } else {
        echo "Falha no login!! Senha ou E-mail incorretos";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Logar usuario</h1>
    <form method="post" action="">
        <label>E-mail:</label>
        <input value="<?php if(isset($_POST['email'])) echo $_POST['email']; ?>" type="text" name="email"><br><br>

        <label>Senha :</label>
        <input value="<?php if(isset($_POST['senha'])) echo $_POST['senha']; ?>" type="password" name="senha"><br><br>

        <button type="submit">login</button> <br><br>
        <p>Não possui uma conta ? </p> <a href="cadastrar.php">cadastrar</a><br><br>
        <a href = "logout.php">Sair</a>
    </form>
</body>
</html>