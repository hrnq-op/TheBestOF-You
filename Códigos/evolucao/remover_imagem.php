<?php
session_start();
include('../conexao.php');

$usuario_id = $_SESSION['id_usuario'] ?? $_POST['id_usuario'] ?? null;
$imagem = $_POST['imagem'] ?? '';

if (!$usuario_id || !$imagem) {
    die("Dados incompletos.");
}

// Encontrar o registro que possui essa imagem
$stmt = $conexao->prepare("SELECT id_evolucao, imagem FROM evolucao WHERE id_usuario = ? AND imagem LIKE ?");
$like = "%$imagem%";
$stmt->bind_param("is", $usuario_id, $like);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Imagem não encontrada.");
}

$row = $resultado->fetch_assoc();
$id_evolucao = $row['id_evolucao'];
$imagens_atual = explode(',', $row['imagem']);

// Remove a imagem da lista
$imagens_novas = array_filter($imagens_atual, function($i) use ($imagem) {
    return trim($i) !== trim($imagem);
});
$nova_string = implode(',', $imagens_novas);

// Atualizar banco
$stmt_update = $conexao->prepare("UPDATE evolucao SET imagem = ? WHERE id_evolucao = ?");
$stmt_update->bind_param("si", $nova_string, $id_evolucao);
$stmt_update->execute();
$stmt_update->close();

// Excluir fisicamente o arquivo
if (file_exists($imagem)) {
    unlink($imagem);
}

header("Location: evolucao.php");
exit();
