<?php
session_start();
include "conexao.php";

if (!isset($_SESSION['usuario_id'])) {
    echo "<p>Erro: Usuário não está logado.</p>";
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

// 🟢 ETAPA 1: Se clicou em "Avançar"
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salvar_dieta'])) {
    $id_dieta = $_POST['id_dieta'] ?? null;
    $dieta_conteudo = $_POST['dieta_conteudo'] ?? '';

    if ($id_dieta && !empty($dieta_conteudo)) {
        // Criar e salvar arquivo .txt
        $nome_arquivo = "dieta_usuario_{$id_usuario}_dieta_{$id_dieta}_" . time() . ".txt";
        $caminho_arquivo = "dietas_salvas/" . $nome_arquivo;

        if (!file_exists("dietas_salvas")) {
            mkdir("dietas_salvas", 0777, true);
        }

        file_put_contents($caminho_arquivo, $dieta_conteudo);

        // Atualizar caminho na tabela
        $stmt = $conexao->prepare("UPDATE dieta SET dieta = ? WHERE id_dieta = ?");
        $stmt->bind_param("si", $caminho_arquivo, $id_dieta);
        $stmt->execute();
        $stmt->close();
        $conexao->close();

        header("Location: treino.php");
        exit;
    } else {
        echo "<p>Erro ao salvar a dieta.</p>";
    }
}

// 🟢 ETAPA 2: Gerar dieta apenas para exibição
$stmt = $conexao->query("
    SELECT u.id_usuario, u.gasto_calorico_total, u.carbo_necessarias, u.prot_necessarias, u.gord_necessarias, 
           d.id_dieta, d.objetivo, d.refeicoes
    FROM dieta d
    INNER JOIN usuario u ON d.id_usuario = u.id_usuario
    WHERE u.id_usuario = $id_usuario
    ORDER BY d.id_dieta DESC
    LIMIT 1
");

if (!$stmt || $stmt->num_rows === 0) {
    echo "<p>Erro: Nenhum usuário com dieta cadastrada.</p>";
    exit;
}

$row = $stmt->fetch_assoc();
$id_dieta = $row['id_dieta'];
$gasto_calorico = (float) $row['gasto_calorico_total'];
$carbo_necessarias = (float) $row['carbo_necessarias'];
$prot_necessarias = (float) $row['prot_necessarias'];
$gord_necessarias = (float) $row['gord_necessarias'];
$objetivo = strtolower($row['objetivo']);
$refeicoes = (int) $row['refeicoes'];

// Alimentos
$alimentos = [];
$result = $conexao->query("SELECT nome FROM alimentos WHERE id_dieta = $id_dieta");
while ($row = $result->fetch_assoc()) {
    $alimentos[] = $row['nome'];
}
$conexao->close();

if (empty($alimentos)) {
    echo "<p>Nenhum alimento foi enviado.</p>";
    exit;
}

// Monta o prompt e gera dieta via API
$acao = ($objetivo === "cutting") ? "déficit calórico" : "superávit calórico";
$prompt = "Elabore uma dieta personalizada para um usuário que está na fase de {$objetivo}. Considere que o gasto calórico total diário desse usuário é de {$gasto_calorico} calorias. Com base nisso, defina um {$acao} adequado.

A dieta também deve se aproximar das seguintes necessidades diárias de macronutrientes:
- Carboidratos: {$carbo_necessarias}g
- Proteínas: {$prot_necessarias}g
- Gorduras: {$gord_necessarias}g

Utilize exclusivamente os seguintes alimentos para montar a dieta: " . implode(", ", $alimentos) . ". A dieta deve ser dividida em exatamente {$refeicoes} refeições ao longo do dia.

Para cada refeição, descreva de forma clara:
- Os alimentos incluídos;
- As quantidades aproximadas;
- Os valores nutricionais de cada item (calorias, carboidratos, proteínas e gorduras).

Apresente o conteúdo em formato de texto simples e organizado, sem tabelas ou qualquer tipo de formatação. Use apenas tópicos e espaçamento adequado para facilitar a leitura.";

$apiKey = "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";
$url = "https://openrouter.ai/api/v1/chat/completions";

$data = [
    "model" => "mistralai/mistral-7b-instruct",
    "messages" => [
        ["role" => "system", "content" => "Você é um nutricionista especializado em dietas personalizadas com base em fases como cutting e bulking."],
        ["role" => "user", "content" => $prompt]
    ]
];

$options = [
    "http" => [
        "header" => [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ],
        "method" => "POST",
        "content" => json_encode($data),
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if (!$response) {
    echo "<p>Erro ao gerar a dieta. Verifique sua chave da API ou a conexão.</p>";
    exit;
}

$resposta = json_decode($response, true);
$dieta = $resposta['choices'][0]['message']['content'] ?? "Não foi possível gerar a dieta.";
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Dieta Gerada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .dieta {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            white-space: pre-wrap;
        }

        .botoes {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        button.salvar {
            background-color: #4CAF50;
            color: white;
        }

        button.outra {
            background-color: #007BFF;
            color: white;
        }
    </style>
</head>

<body>
    <h1>Dieta Personalizada</h1>
    <p><strong>Gasto calórico:</strong> <?= htmlspecialchars($gasto_calorico) ?> kcal</p>
    <p><strong>Objetivo:</strong> <?= ucfirst(htmlspecialchars($objetivo)) ?></p>
    <p><strong>Refeições por dia:</strong> <?= $refeicoes ?></p>
    <p><strong>Macronutrientes alvo:</strong><br>
        Carboidratos: <?= $carbo_necessarias ?>g<br>
        Proteínas: <?= $prot_necessarias ?>g<br>
        Gorduras: <?= $gord_necessarias ?>g
    </p>

    <h2>Dieta sugerida:</h2>
    <div class="dieta"><?= nl2br(htmlspecialchars($dieta)) ?></div>

    <div class="botoes">
        <!-- Botão AVANÇAR -->
        <form method="post">
            <input type="hidden" name="salvar_dieta" value="1">
            <input type="hidden" name="id_dieta" value="<?= $id_dieta ?>">
            <input type="hidden" name="dieta_conteudo" value="<?= htmlspecialchars($dieta, ENT_QUOTES) ?>">
            <button type="submit" class="salvar">Avançar</button>
        </form>

        <!-- Botão GERAR OUTRA -->
        <form method="get">
            <button type="submit" class="outra">Gerar outra dieta</button>
        </form>
    </div>
</body>

</html>