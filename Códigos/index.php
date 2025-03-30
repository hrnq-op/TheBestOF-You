<?php
if(!isset($_SESSION))
session_start();
if(!isset($_SESSION['usuario_id']))
die('você não tem permição para navegar nesta pagina!!! Faça o login <a href = "login.php">Clique aqui</a>');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>SEJA BEM VINDO </h1>
    <a href = "logout.php">Sair</a>
</body>
</html>