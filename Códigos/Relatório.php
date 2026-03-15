<?php
include "conexao.php"; // sua conexão com o banco

// Relatório de Usuários
$sqlUsuarios = "SELECT id_usuario, nome, email, idade, peso, altura, gasto_calorico_total, sexo 
                FROM usuario";
$resultUsuarios = $conexao->query($sqlUsuarios);

// Relatório de Dietas
$sqlDietas = "SELECT d.id_dieta, d.data_inicio, u.nome, d.objetivo, d.situacao, d.refeicoes 
              FROM dieta d
              JOIN usuario u ON d.id_usuario = u.id_usuario";
$resultDietas = $conexao->query($sqlDietas);

// Relatório de Alimentos
$sqlAlimentos = "SELECT a.nome AS alimento, d.id_dieta, u.nome AS usuario
                 FROM alimentos a
                 JOIN dieta d ON a.id_dieta = d.id_dieta
                 JOIN usuario u ON d.id_usuario = u.id_usuario";
$resultAlimentos = $conexao->query($sqlAlimentos);

// Relatório de Treinos
$sqlTreinos = "SELECT t.id_treino, u.nome, t.divisao_treino, t.dias_de_treino, t.nivel_de_treino, t.enfase_muscular, t.situacao
               FROM treino t
               JOIN usuario u ON t.id_usuario = u.id_usuario";
$resultTreinos = $conexao->query($sqlTreinos);

// Relatório de Evolução
$sqlEvolucao = "SELECT e.id_evolucao, u.nome, e.data_inicio, e.peso_inicio, e.data_fim, e.peso_fim, e.objetivo, e.tempo_dieta
                FROM evolucao e
                JOIN usuario u ON e.id_usuario = u.id_usuario";
$resultEvolucao = $conexao->query($sqlEvolucao);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório do Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { margin-top: 40px; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>

    <h1>📊 Relatório Geral - TheBestOfYou</h1>

    <h2>👤 Usuários</h2>
    <table>
        <tr><th>ID</th><th>Nome</th><th>Email</th><th>Idade</th><th>Peso</th><th>Altura</th><th>Gasto Calórico</th><th>Sexo</th></tr>
        <?php while($row = $resultUsuarios->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_usuario'] ?></td>
                <td><?= $row['nome'] ?></td>
                <td><?= $row['email'] ?></td>
                <td><?= $row['idade'] ?></td>
                <td><?= $row['peso'] ?></td>
                <td><?= $row['altura'] ?></td>
                <td><?= $row['gasto_calorico_total'] ?></td>
                <td><?= $row['sexo'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>🥗 Dietas</h2>
    <table>
        <tr><th>ID Dieta</th><th>Data Início</th><th>Usuário</th><th>Objetivo</th><th>Situação</th><th>Refeições</th></tr>
        <?php while($row = $resultDietas->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_dieta'] ?></td>
                <td><?= $row['data_inicio'] ?></td>
                <td><?= $row['nome'] ?></td>
                <td><?= $row['objetivo'] ?></td>
                <td><?= $row['situacao'] ?></td>
                <td><?= $row['refeicoes'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>🍎 Alimentos nas Dietas</h2>
    <table>
        <tr><th>Alimento</th><th>ID Dieta</th><th>Usuário</th></tr>
        <?php while($row = $resultAlimentos->fetch_assoc()): ?>
            <tr>
                <td><?= $row['alimento'] ?></td>
                <td><?= $row['id_dieta'] ?></td>
                <td><?= $row['usuario'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>🏋️ Treinos</h2>
    <table>
        <tr><th>ID Treino</th><th>Usuário</th><th>Divisão</th><th>Dias</th><th>Nível</th><th>Ênfase Muscular</th><th>Situação</th></tr>
        <?php while($row = $resultTreinos->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_treino'] ?></td>
                <td><?= $row['nome'] ?></td>
                <td><?= $row['divisao_treino'] ?></td>
                <td><?= $row['dias_de_treino'] ?></td>
                <td><?= $row['nivel_de_treino'] ?></td>
                <td><?= $row['enfase_muscular'] ?></td>
                <td><?= $row['situacao'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>📈 Evolução</h2>
    <table>
        <tr><th>ID Evolução</th><th>Usuário</th><th>Data Início</th><th>Peso Inicial</th><th>Data Fim</th><th>Peso Final</th><th>Objetivo</th><th>Tempo Dieta</th></tr>
        <?php while($row = $resultEvolucao->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id_evolucao'] ?></td>
                <td><?= $row['nome'] ?></td>
                <td><?= $row['data_inicio'] ?></td>
                <td><?= $row['peso_inicio'] ?></td>
                <td><?= $row['data_fim'] ?></td>
                <td><?= $row['peso_fim'] ?></td>
                <td><?= $row['objetivo'] ?></td>
                <td><?= $row['tempo_dieta'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

</body>
</html>
