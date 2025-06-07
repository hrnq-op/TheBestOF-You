<?php
session_start();
include('../conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    die("Usuário não autenticado.");
}

$usuario_id = $_SESSION['id_usuario'];
$peso_atual = $_POST['peso'] ?? null;
$tempo_dieta = $_POST['tempo_dieta'] ?? '';
$objetivo = $_POST['objetivo'] ?? 'Não definido';
$data_hoje = date('Y-m-d');
$id_evolucao = $_POST['id_evolucao'] ?? null;

// Upload múltiplo de imagens
$caminhos_imagens = [];
if (!empty($_FILES['imagem']['name'][0])) {
    $diretorio = "imagens_evolucao/";
    if (!file_exists($diretorio)) {
        mkdir($diretorio, 0777, true);
    }

    foreach ($_FILES['imagem']['name'] as $index => $nome) {
        $nome_temp = $_FILES['imagem']['tmp_name'][$index];
        $nome_arquivo = uniqid() . "_" . basename($nome);
        $caminho_final = $diretorio . $nome_arquivo;

        if (move_uploaded_file($nome_temp, $caminho_final)) {
            $caminhos_imagens[] = $caminho_final;
        }
    }
}
$imagens_novas = implode(',', $caminhos_imagens);

if ($id_evolucao) {
    // EDIÇÃO de registro específico

    // Buscar imagens anteriores
    $stmt_img = $conexao->prepare("SELECT imagem FROM evolucao WHERE id_evolucao = ?");
    $stmt_img->bind_param("i", $id_evolucao);
    $stmt_img->execute();
    $stmt_img->bind_result($imagens_anteriores);
    $stmt_img->fetch();
    $stmt_img->close();

    // Mesclar imagens antigas com as novas
    $todas_imagens = [];
    if (!empty($imagens_anteriores)) {
        $todas_imagens = explode(',', $imagens_anteriores);
    }
    if (!empty($caminhos_imagens)) {
        $todas_imagens = array_merge($todas_imagens, $caminhos_imagens);
    }
    $imagens_str = implode(',', $todas_imagens);

    $stmt_update = $conexao->prepare("UPDATE evolucao SET peso_inicio = ?, tempo_dieta = ?, objetivo = ?, imagem = ? WHERE id_evolucao = ?");
    $stmt_update->bind_param("dsssi", $peso_atual, $tempo_dieta, $objetivo, $imagens_str, $id_evolucao);

    if (!$stmt_update->execute()) {
        die("Erro ao editar evolução: " . $stmt_update->error);
    }

    $stmt_update->close();
    header("Location: evolucao.php");
    exit();
}

// FLUXO NORMAL - atualização da última evolução e nova inserção
$sql_ultima = "SELECT id_evolucao, peso_fim, objetivo, tempo_dieta FROM evolucao WHERE id_usuario = ? ORDER BY id_evolucao DESC LIMIT 1";
$stmt_ultima = $conexao->prepare($sql_ultima);
$stmt_ultima->bind_param("i", $usuario_id);
$stmt_ultima->execute();
$stmt_ultima->bind_result($id_evolucao_anterior, $peso_fim_anterior, $objetivo_ant, $tempo_dieta_ant);

if ($stmt_ultima->fetch()) {
    if (empty($tempo_dieta)) $tempo_dieta = $tempo_dieta_ant;
    if (empty($objetivo)) $objetivo = $objetivo_ant;
    $peso_inicio_novo = $peso_atual;
} else {
    $peso_inicio_novo = $peso_atual;
    $objetivo = 'Não definido';
}
$stmt_ultima->close();

// Atualiza a evolução anterior (peso_fim e data_fim somente)
if (isset($id_evolucao_anterior)) {
    $stmt_update = $conexao->prepare("UPDATE evolucao SET peso_fim = ?, data_fim = ? WHERE id_evolucao = ?");
    $stmt_update->bind_param("dsi", $peso_atual, $data_hoje, $id_evolucao_anterior);
    $stmt_update->execute();
    $stmt_update->close();
}

// Insere nova linha com imagens novas
$stmt_insert = $conexao->prepare("INSERT INTO evolucao (id_usuario, peso_inicio, data_inicio, objetivo, tempo_dieta, imagem) VALUES (?, ?, ?, ?, ?, ?)");
$stmt_insert->bind_param("idssss", $usuario_id, $peso_inicio_novo, $data_hoje, $objetivo, $tempo_dieta, $imagens_novas);
$stmt_insert->execute();
$stmt_insert->close();

header("Location: evolucao.php");
exit();
