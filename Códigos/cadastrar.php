<?php

function limpar_texto($str){ 
    return preg_replace("/[^0-9]/", "", $str); 
}

if(count($_POST) > 0 ){

include('conexao.php');
$erro = false;

    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $nome = $_POST['nome'];

    if(empty($senha)){
        $erro = "Preencha a senha";
    }
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
        $erro = "Preencha o e-mail verdadeiro";
    }

    if(!empty($telefone)) {
    
        $telefone = limpar_texto($telefone);
        if(strlen($telefone) != 11){
            $erro = "O telefone deve ser preenchido no padrão : (12) 98888-8888 ";
        }
    
    }

    if($erro){
        echo "<p><b> Erro :$erro</b></p>";
    } else{
        $sql_code="INSERT INTO usuario (email, senha, nome, telefone)
         VALUES('$email','$senha','$nome','$telefone')";
         $deu_certo = $mysqli -> query($sql_code) or die(mysqli->error);
         if($deu_certo){
            echo "<p><b>Você foi cadastrado com sucesso!</b></p>";
            unset($_POST);
         }
    }
    //$mysqli->query("INSERT INTO usuario(email, senha) VALUES ('$email', '$senha') ");
}
?>


<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cadastrar</title>
</head>
<body>
    <h1>Cadastrar usuario</h1>
<form method = "POST" action = "">
        
        <label>nome:</label>
        <input value="<?php if(isset($_POST['nome'])) echo $_POST['nome']; ?>" type ="text" name="nome"><br><br>

        <label>E-mail:</label>
        <input value="<?php if(isset($_POST['email'])) echo $_POST['email']; ?>" type ="text" name="email"><br><br>

        <label>Telefone:</label>
        <input  value="<?php if(isset($_POST['telefone'])) echo $_POST['telefone']; ?>" placeholder = "(12) 98888-8888" type ="text" name="telefone"><br><br>

        <label>Senha :</label>
        <input value="<?php if(isset($_POST['senha'])) echo $_POST['senha']; ?>" type ="password" name="senha"><br><br>

        <button type="submit" name="enviar">cadastrar</button> <br><br>
        <p>Já possui uma conta ? </p> <a href = "login.php">login</a><br>
       
    </form>
</body>
</html>