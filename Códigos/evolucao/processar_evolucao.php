<?php
session_start();
include('../conexao.php');

if (isset($_POST['atualizar'])) {
    $usuario_id = $_SESSION['id_usuario'];
    $peso_atual = $_POST['peso'];
    $tempo_dieta = $_POST['tempo_dieta'];
    $data_fim = date('Y-m-d');

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
    $imagens_str = implode(',', $caminhos_imagens);

    // Buscar a última evolução do usuário
    $sql_ultima = "SELECT id_evolucao, peso_fim, objetivo, tempo_dieta FROM evolucao WHERE id_usuario = ? ORDER BY id_evolucao DESC LIMIT 1";
    $stmt_ultima = $conexao->prepare($sql_ultima);
    if (!$stmt_ultima) {
        die("Erro no prepare do SELECT: " . $conexao->error);
    }
    $stmt_ultima->bind_param("i", $usuario_id);
    $stmt_ultima->execute();
    $stmt_ultima->bind_result($id_evolucao_anterior, $peso_fim_anterior, $objetivo, $tempo_dieta_anterior);

    if ($stmt_ultima->fetch()) {
        $peso_inicio_novo = $peso_atual;
        if (empty($tempo_dieta)) {
            $tempo_dieta = $tempo_dieta_anterior;
        }
    } else {
        // Primeira evolução
        $peso_inicio_novo = $peso_atual;
        $objetivo = 'Não definido';
    }
    $stmt_ultima->close();

    // Atualizar a última evolução (peso_fim, data_fim, imagem)
    if (isset($id_evolucao_anterior)) {
        $stmt_update = $conexao->prepare("UPDATE evolucao SET peso_fim = ?, data_fim = ?, imagem = ? WHERE id_evolucao = ?");
        if (!$stmt_update) {
            die("Erro no prepare do UPDATE: " . $conexao->error);
        }
        $stmt_update->bind_param("dssi", $peso_atual, $data_fim, $imagens_str, $id_evolucao_anterior);
        if (!$stmt_update->execute()) {
            die("Erro ao executar UPDATE: " . $stmt_update->error);
        }
        $stmt_update->close();
    }

    // Inserir nova linha com peso_inicio = peso_fim anterior
    $stmt_insert = $conexao->prepare("INSERT INTO evolucao (id_usuario, peso_inicio, data_inicio, objetivo, tempo_dieta) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_insert) {
        die("Erro no prepare do INSERT: " . $conexao->error);
    }
    $stmt_insert->bind_param("idsss", $usuario_id, $peso_inicio_novo, $data_fim, $objetivo, $tempo_dieta);
    if (!$stmt_insert->execute()) {
        die("Erro ao executar INSERT: " . $stmt_insert->error);
    }
    $stmt_insert->close();

    header("Location: evolucao.php");
    exit();
}
