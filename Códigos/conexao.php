<?php
$host = "localhost";
$user = "root";
$pass = "";
$banco = "thebestofyou";

// Fazendo a conexão com o banco de dados
$conexao = mysqli_connect($host, $user, $pass, $banco);

// Verificando se a conexão foi bem-sucedida
if (!$conexao) {
    die("Falha na conexão com o banco de dados: " . mysqli_connect_error());
}
