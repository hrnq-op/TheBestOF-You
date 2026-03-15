<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Escolha de Alimentos</title>
    <link rel="stylesheet" href="selecao_alimentos.css?=3">
</head>

<body>

<header>
    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo">
        </a>
    </div>
    <div class="site-name">Dieta</div>
    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo">
        </a>
    </div>
</header>

<div class="qlqr">
    <h1>Fale os alimentos que você gostaria de ter na sua dieta</h1>
    
    <form method="post" class="form-caixa">
        <div class="animated-select">
            <label for="alimentos">Alimentos (separe por vírgula):</label>
            <input type="text" id="alimentos" name="alimentos" required>
        </div>

        <div class="animated-select">
            <label for="refeicoes">Número de refeições por dia:</label>
            <input type="number" id="refeicoes" name="refeicoes" min="1" max="10" required>
        </div>

        <div class="animated-select">
            <button type="submit">Adicionar</button>
        </div>
    </form>

<?php
ob_start();
include('../conexao.php');
session_start();

$id_usuario = $_SESSION['id_usuario'] ?? null;
$alimentosEscolhidos = [];
$refeicoes = 0;

function obterIdAlimento($conexao, $nome, $id_dieta)
{
    $stmt = $conexao->prepare("SELECT id_alimentos FROM alimentos WHERE nome = ? AND id_dieta = ?");
    $stmt->bind_param("si", $nome, $id_dieta);
    $stmt->execute();
    $stmt->bind_result($id_alimento_encontrado);
    $stmt->fetch();
    $stmt->close();
    return $id_alimento_encontrado ?? null;
}

// ETAPA 1: Exibe a confirmação (Tabela com os alimentos)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["alimentos"], $_POST["refeicoes"]) && !isset($_POST["montar_dieta"])) {
    $alimentos = explode(",", $_POST["alimentos"]);
    $refeicoes = intval($_POST["refeicoes"]);

    foreach ($alimentos as $alimento) {
        $alimento = trim($alimento);
        if (!empty($alimento)) {
            $alimentosEscolhidos[] = $alimento;
        }
    }

    if (!empty($alimentosEscolhidos)) {
        // Div container para centralizar
        echo '<div style="max-width: 600px; margin: 0 auto;">';

        // Tabela usando a classe do seu CSS
        echo '<table class="tabela-alimentos">';
        echo '<thead><tr><th>Alimentos Escolhidos (' . htmlspecialchars($refeicoes) . ' refeições/dia)</th></tr></thead>';
        echo '<tbody>';

        foreach ($alimentosEscolhidos as $alimento) {
            echo '<tr><td>' . htmlspecialchars($alimento) . '</td></tr>';
        }

        echo '</tbody></table>';

        // Form para confirmar e avançar
        echo '<form method="post" style="text-align: center;">';
        foreach ($alimentosEscolhidos as $alimento) {
            echo '<input type="hidden" name="alimentos[]" value="' . htmlspecialchars($alimento) . '">';
        }
        echo '<input type="hidden" name="refeicoes" value="' . $refeicoes . '">';
        // Botão usando a classe do seu CSS
        echo '<button type="submit" name="montar_dieta" class="btn-avc btn-pequeno">Avançar</button>';
        echo '</form>';
        echo '</div>';
    }
}

// ETAPA 2: Salva no banco e redireciona
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["montar_dieta"])) {
    $alimentosEscolhidos = $_POST["alimentos"] ?? [];
    $refeicoes = intval($_POST["refeicoes"]);

    // Busca a última dieta criada para este usuário
    $stmt = $conexao->prepare("SELECT id_dieta FROM dieta WHERE id_usuario = ? ORDER BY id_dieta DESC LIMIT 1");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($id_dieta);
    $stmt->fetch();
    $stmt->close();

    if ($id_dieta) {
        // Atualiza o número de refeições
        $stmt = $conexao->prepare("UPDATE dieta SET refeicoes = ? WHERE id_dieta = ?");
        $stmt->bind_param("ii", $refeicoes, $id_dieta);
        $stmt->execute();
        $stmt->close();

        // Loop para salvar CADA alimento
        foreach ($alimentosEscolhidos as $alimento) {
            $alimento = trim($alimento);
            if (!empty($alimento)) {
                $id_alimento = obterIdAlimento($conexao, $alimento, $id_dieta);

                // Se o alimento ainda não existe nesta dieta, insere
                if (!$id_alimento) {
                    $stmt_ins = $conexao->prepare("INSERT INTO alimentos (nome, id_dieta) VALUES (?, ?)");
                    $stmt_ins->bind_param("si", $alimento, $id_dieta);
                    $stmt_ins->execute();
                    $stmt_ins->close();
                }
            }
        }
        
        // Finaliza conexão e redireciona (FORA DO LOOP para garantir que salvou todos)
        $conexao->close();
        header("Location: ../montagem_dieta/montagem_dieta.php?action=gerar_nova");
        exit();
    }
    
    // Fallback se não achar dieta
    $conexao->close();
    header("Location: ../montagem_dieta/montagem_dieta.php");
    exit();
}
?>
</div>

<div id="spinner" aria-hidden="true">
    <div class="loader" role="status" aria-label="Carregando"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. ANIMAÇÃO DE ENTRADA DOS CAMPOS
    // Seleciona todos os elementos com a classe .animated-select
    const elements = document.querySelectorAll('.animated-select');
    
    // Para cada elemento, adiciona a classe .show com um pequeno atraso (efeito cascata)
    elements.forEach((el, index) => {
        setTimeout(() => {
            el.classList.add('show');
        }, index * 200); // 200ms de diferença entre cada um
    });

    // 2. LÓGICA DO SPINNER
    function showSpinner() {
        var sp = document.getElementById('spinner');
        if (sp) sp.style.display = 'flex';
    }

    // Adiciona evento de submit em todos os formulários da página
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            showSpinner();
        }, { passive: true });
    });
});
</script>

</body>
</html>