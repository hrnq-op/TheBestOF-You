<?php
session_start();
include "../conexao.php";

if (!isset($_SESSION['id_usuario'])) {
    die("Usuário não autenticado.");
}

$usuario_id = $_SESSION['id_usuario'];

// Lógica para tornar uma dieta anterior na dieta atual
if (isset($_GET['usar']) && is_numeric($_GET['usar'])) {
    $id_dieta_para_ativar = intval($_GET['usar']);

    $conexao->begin_transaction();

    try {
        // 1. Desativar a dieta ativa
        $stmt_desativar = $conexao->prepare("UPDATE dieta SET situacao = 'D' WHERE id_usuario = ? AND situacao = 'A'");
        $stmt_desativar->bind_param("i", $usuario_id);
        $stmt_desativar->execute();
        $stmt_desativar->close();

        // 2. Ativar a dieta escolhida
        $stmt_ativar = $conexao->prepare("UPDATE dieta SET situacao = 'A' WHERE id_usuario = ? AND id_dieta = ?");
        $stmt_ativar->bind_param("ii", $usuario_id, $id_dieta_para_ativar);
        $stmt_ativar->execute();
        $stmt_ativar->close();

        $conexao->commit();

    } catch (mysqli_sql_exception $exception) {
        $conexao->rollback();
        die("Ocorreu um erro ao tentar alterar a dieta. Tente novamente.");
    }

    header("Location: dieta.php");
    exit;
}

// Buscar dietas desativadas
$stmt = $conexao->prepare("SELECT id_dieta, data_inicio, arquivo_dieta, objetivo FROM dieta WHERE id_usuario = ? AND (situacao = 'D' OR situacao = '') ORDER BY data_inicio DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$dietas_anteriores = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Parsedown para interpretar Markdown
require_once '../libs/Parsedown.php';
$Parsedown = new Parsedown();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dietas Anteriores</title>
    <link rel="stylesheet" href="dietas_anteriores.css">
</head>
<body>
<header>
    <div class="logo">
        <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
    </div>
    <div class="site-name"><h1>Dieta</h1></div>
    <nav class="nav-links">
        <a href="dieta.php">Dieta Atual</a>
    </nav>
    <div class="logo">
        <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
    </div>
</header>

<div class="container">
    <h1>Dietas Anteriores</h1>
    <?php if (count($dietas_anteriores) === 0): ?>
        <p>Você ainda não possui dietas anteriores.</p>
    <?php else: ?>
        <?php foreach ($dietas_anteriores as $d): ?>
            <div class="dieta-box">
                <div class="dieta-header" onclick="toggleConteudo('conteudo<?= $d['id_dieta'] ?>')">
                    <strong>Dieta <?= htmlspecialchars($d['objetivo'] ?? 'Não definido') ?> - <?= date('d/m/Y', strtotime($d['data_inicio'])) ?></strong>
                    <span>&#9660;</span>
                </div>
                <div id="conteudo<?= $d['id_dieta'] ?>" class="dieta-conteudo">
                    <?php
                    $caminho = "../montagem_dieta/" . $d['arquivo_dieta'];
                    if ($d['arquivo_dieta'] && file_exists($caminho)) {
                        $conteudo = file_get_contents($caminho);
                        echo $Parsedown->text($conteudo); // <-- agora renderiza Markdown
                    } else {
                        echo "<em>Arquivo da dieta não encontrado ou não especificado.</em>";
                    }
                    ?>
                    <br>
                    <a href="?usar=<?= $d['id_dieta'] ?>" onclick="return confirm('Tem certeza que deseja tornar esta a sua dieta ativa?');">Usar esta dieta</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <br>
    <a href="dieta.php" class="link-voltar">Voltar para dieta atual</a>
</div>

<script>
function toggleConteudo(id) {
    var el = document.getElementById(id);
    if (el.style.display === "none" || el.style.display === "") {
        el.style.display = "block";
    } else {
        el.style.display = "none";
    }
}
</script>
</body>
</html>
