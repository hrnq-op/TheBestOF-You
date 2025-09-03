<?php
session_start();
include "../conexao.php";

if (!isset($_SESSION['id_usuario'])) {
    die("Usuário não autenticado.");
}

$usuario_id = $_SESSION['id_usuario'];

// Tornar um treino anterior o atual
if (isset($_GET['usar']) && is_numeric($_GET['usar'])) {
    $id_treino_para_ativar = intval($_GET['usar']);

    $conexao->begin_transaction();
    try {
        // Etapa 1: Desativar o treino atualmente ativo.
        $stmt_desativar = $conexao->prepare("UPDATE treino SET situacao = 'D' WHERE id_usuario = ? AND situacao = 'A'");
        if ($stmt_desativar) {
            $stmt_desativar->bind_param("i", $usuario_id);
            $stmt_desativar->execute();
            $stmt_desativar->close();
        } else {
            throw new Exception("Falha ao preparar a desativação do treino: " . $conexao->error);
        }

        // Etapa 2: Ativar o treino escolhido.
        $stmt_ativar = $conexao->prepare("UPDATE treino SET situacao = 'A' WHERE id_usuario = ? AND id_treino = ?");
        if ($stmt_ativar) {
            $stmt_ativar->bind_param("ii", $usuario_id, $id_treino_para_ativar);
            $stmt_ativar->execute();
            $stmt_ativar->close();
        } else {
            throw new Exception("Falha ao preparar a ativação do treino: " . $conexao->error);
        }

        $conexao->commit();

    } catch (Exception $e) { // Captura qualquer exceção
        $conexao->rollback();
        die("Erro ao ativar treino anterior: " . $e->getMessage());
    }

    header("Location: treino.php");
    exit;
}

// Buscar treinos anteriores (inativos ou com situação vazia)
$stmt = $conexao->prepare("SELECT * FROM treino WHERE id_usuario = ? AND (situacao = 'D' OR situacao = '') ORDER BY id_treino DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$treinos_anteriores = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once '../libs/Parsedown.php';
$Parsedown = new Parsedown();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Treinos Anteriores</title>
    <link rel="stylesheet" href="treinos_anteriores.css">
</head>
<body>
<header>
    <div class="logo">
        <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
    </div>
    <div class="site-name"><h1>Treino</h1></div>
    <nav class="nav-links">
        <a href="treino.php">Treino Atual</a>
    </nav>
    <div class="logo">
        <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
    </div>
</header>

<div class="container">
    <h1>Treinos Anteriores</h1>

    <?php if (count($treinos_anteriores) === 0): ?>
        <p>Você ainda não possui treinos anteriores.</p>
    <?php else: ?>
        <?php foreach ($treinos_anteriores as $t): ?>
            <div class="dieta-box">
                <div class="dieta-header" onclick="toggleConteudo('conteudo<?= $t['id_treino'] ?>')">
                    <strong><?= htmlspecialchars($t['divisao_treino']) ?> - <?= $t['dias_de_treino'] ?> Dias - Nível de Treino: <?= htmlspecialchars($t['nivel_de_treino']) ?></strong>
                    <span>&#9660;</span>
                </div>
                <div id="conteudo<?= $t['id_treino'] ?>" class="dieta-conteudo" style="display: none;">
                    <?php if (!empty($t['enfase_muscular'])): ?>
                        <p><strong>Ênfase:</strong> <?= htmlspecialchars($t['enfase_muscular']) ?></p>
                        <hr>
                    <?php endif; ?>
                    <p>
                        <?php
                        // Verifica se o arquivo de treino existe antes de tentar lê-lo
                        if (!empty($t['arquivo_treino'])) {
                            $caminho = "../montagem_treino/treinos_salvos/" . $t['arquivo_treino'];
                            if (file_exists($caminho)) {
                                echo $Parsedown->text(file_get_contents($caminho));
                            } else {
                                echo "<em>Arquivo do treino ('" . htmlspecialchars($t['arquivo_treino']) . "') não encontrado.</em>";
                            }
                        } else {
                            echo "<em>Nenhum arquivo de treino associado.</em>";
                        }
                        ?>
                    </p>
                    <a href="?usar=<?= $t['id_treino'] ?>" class="usar-treino" onclick="return confirm('Tem certeza que deseja definir este como seu treino atual?')">Usar este treino</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="treino.php" class="link-voltar">Voltar para treino atual</a>
</div>

<script>
function toggleConteudo(id) {
    var el = document.getElementById(id);
    var header = el.previousElementSibling; // Pega o header para mudar a seta
    var seta = header.querySelector('span');
    
    if (el.style.display === "none" || el.style.display === "") {
        el.style.display = "block";
        seta.innerHTML = '&#9650;'; // Seta para cima
    } else {
        el.style.display = "none";
        seta.innerHTML = '&#9660;'; // Seta para baixo
    }
}
</script>
</body>
</html>