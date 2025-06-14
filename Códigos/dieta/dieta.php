<?php
session_start();
include "../conexao.php";

if (!isset($_SESSION['id_usuario'])) {
    die("Usuário não autenticado.");
}

$usuario_id = $_SESSION['id_usuario'];

// Buscar dieta atual
$sql = "SELECT id_dieta, arquivo_dieta, data_inicio, objetivo, refeicoes FROM dieta WHERE id_usuario = ? AND situacao = 'A' LIMIT 1";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$dieta_atual = $result->fetch_assoc();
$stmt->close();

$dieta_texto = "Nenhuma dieta encontrada.";
$objetivo_atual = $dieta_atual['objetivo'] ?? '';
$refeicoes_atuais = $dieta_atual['refeicoes'] ?? '';

if ($dieta_atual) {
    $caminho = "../montagem_dieta/" . $dieta_atual['arquivo_dieta'];
    $dieta_texto = file_exists($caminho) ? file_get_contents($caminho) : "Arquivo não encontrado: $caminho";
}

$resposta_deepseek = "";

// Quando usuário envia o prompt de alteração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_alteracao'])) {
    $alteracao_usuario = trim($_POST['chatDieta']);
    $refeicoes_digitadas = trim($_POST['refeicoes'] ?? '');

    $refeicoes_final = !empty($refeicoes_digitadas) ? intval($refeicoes_digitadas) : $refeicoes_atuais;

    if (!empty($alteracao_usuario) && !empty($dieta_texto) && !empty($objetivo_atual) && !empty($refeicoes_final)) {
        $prompt = <<<EOT
Você é um nutricionista profissional.
Aqui está uma dieta base:
$dieta_texto

Adapte essa dieta para o objetivo "$objetivo_atual", com $refeicoes_final refeições por dia, e faça as alterações a seguir:
$alteracao_usuario

Gere a nova dieta com base nas instruções acima.
EOT;

        $apiKey = 'SUA_CHAVE_API_DEEPSEEK'; // Substitua com sua chave

        $dados = [
            "model" => "deepseek-chat",
            "messages" => [
                ["role" => "system", "content" => "Você é um nutricionista profissional."],
                ["role" => "user", "content" => $prompt]
            ]
        ];

        $ch = curl_init("https://api.deepseek.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));

        $resposta = curl_exec($ch);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($erro) {
            $resposta_deepseek = "Erro ao acessar a API: $erro";
        } else {
            $resposta_array = json_decode($resposta, true);
            $resposta_deepseek = $resposta_array['choices'][0]['message']['content'] ?? 'Sem resposta da API.';
            $_SESSION['nova_dieta_gerada'] = $resposta_deepseek;
            $_SESSION['refeicoes'] = $refeicoes_final;
            $_SESSION['objetivo'] = $objetivo_atual;
        }
    }
}

// Quando usuário clica em "Salvar como nova dieta"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_dieta'])) {
    if (
        isset($_SESSION['nova_dieta_gerada']) &&
        isset($_SESSION['refeicoes']) &&
        isset($_SESSION['objetivo'])
    ) {
        $nova_dieta = $_SESSION['nova_dieta_gerada'];
        $refeicoes = intval($_SESSION['refeicoes']);
        $objetivo = $_SESSION['objetivo'];

        $pasta = "../montagem_dieta/dietas_salvas/";
        if (!file_exists($pasta)) {
            mkdir($pasta, 0777, true);
        }

        $nome_arquivo = "dieta_usuario_" . $usuario_id . "_" . date("Ymd_His") . ".txt";
        $caminho_arquivo = $pasta . $nome_arquivo;
        file_put_contents($caminho_arquivo, $nova_dieta);
        $arquivo_para_db = "dietas_salvas/" . $nome_arquivo;

        // Desativa outras dietas
        $conexao->query("UPDATE dieta SET situacao = 'D' WHERE id_usuario = $usuario_id");

        // Salva nova como ativa
        $sql_insert = "INSERT INTO dieta (id_usuario, arquivo_dieta, data_inicio, situacao, objetivo, refeicoes) VALUES (?, ?, NOW(), 'A', ?, ?)";
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bind_param("issi", $usuario_id, $arquivo_para_db, $objetivo, $refeicoes);
        $stmt_insert->execute();
        $stmt_insert->close();

        unset($_SESSION['nova_dieta_gerada'], $_SESSION['refeicoes'], $_SESSION['objetivo']);
        header("Location: dieta.php");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dieta Atual</title>
    <link rel="stylesheet" href="dieta.css?=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo">
        </a>
    </div>

    <div class="site-name">Dieta</div>

    <nav class="nav-links">
        <a href="dietas_anteriores.php">Dietas Anteriores</a>
    </nav>

    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo">
        </a>
    </div>
</header>

<div class="qlqr">
    <h1>Plano Atual:</h1>

    <div class="dieta"><?= nl2br(htmlspecialchars($dieta_texto)) ?></div>

   <div class="info-dieta-atual">
    <p><strong>Objetivo atual:</strong> <?= htmlspecialchars($objetivo_atual) ?></p>
    <p><strong>Refeições atuais:</strong> <?= htmlspecialchars($refeicoes_atuais) ?></p>
</div>

<form method="post">
    <label for="chatDieta"><strong>Solicitar alteração:</strong></label>
    <textarea id="chatDieta" name="chatDieta" rows="4" placeholder="Descreva o que deseja alterar..."></textarea>

    <br><br>
    <label for="refeicoes"><strong>Deseja alterar o número de refeições?</strong></label><br>
    <input type="number" name="refeicoes" id="refeicoes" class="styled-input" placeholder="(opcional)" min="1">

    <div class="botoes">
        <button type="submit" name="enviar_alteracao">
            <i class="fas fa-paper-plane"></i> Enviar
        </button>
        <button class="outra" onclick="window.location.href='../usuario/usuario.php'" type="button">
            <i class="fas fa-sync-alt"></i> Começar outra dieta
        </button>
    </div>
</form>


    <?php if (!empty($resposta_deepseek)): ?>
        <h2>Nova Dieta Gerada:</h2>
        <div id="respostaDeepSeek" class="dieta">
            <?= nl2br(htmlspecialchars($resposta_deepseek)) ?>
        </div>
        <form method="post">
                <button type="submit" name="salvar_dieta" class="salvar">
                    <i class="fas fa-save"></i> Salvar como nova dieta atual
                </button>
            </form>
    <?php endif; ?>
</div>

</body>
</html>
