<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Montagem da Dieta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            line-height: 1.6;
        }

        h1,
        h2 {
            color: #333;
        }

        p {
            margin-bottom: 10px;
        }

        .dieta {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <h1>Dieta Personalizada</h1>

    <?php
    include "conexao.php";

    // Busca os dados do último usuário com dieta cadastrada
    $stmt = $conexao->query("
        SELECT u.id_usuario, u.gasto_calorico_total, u.carbo_necessarias, u.prot_necessarias, u.gord_necessarias, d.id_dieta, d.objetivo, d.refeicoes
        FROM dieta d
        INNER JOIN usuario u ON d.id_usuario = u.id_usuario
        ORDER BY d.id_dieta DESC
        LIMIT 1
    ");

    if ($stmt && $stmt->num_rows > 0) {
        $row = $stmt->fetch_assoc();
        $id_usuario = $row['id_usuario'];
        $id_dieta = $row['id_dieta'];
        $gasto_calorico = (float) $row['gasto_calorico_total'];
        $carbo_necessarias = (float) $row['carbo_necessarias'];
        $prot_necessarias = (float) $row['prot_necessarias'];
        $gord_necessarias = (float) $row['gord_necessarias'];
        $objetivo = $row['objetivo'];
        $refeicoes = $row['refeicoes'];
    } else {
        echo "<p>Erro: Nenhum usuário com dieta cadastrada.</p>";
        exit;
    }

    // Busca os alimentos relacionados à dieta
    $alimentos = [];
    $result = $conexao->query("SELECT nome FROM alimentos WHERE id_dieta = $id_dieta");

    while ($row = $result->fetch_assoc()) {
        $alimentos[] = $row['nome'];
    }

    $conexao->close();

    if (empty($alimentos)) {
        echo "<p>Nenhum alimento foi enviado.</p>";
    } else {
        // Monta o prompt para a IA
        $objetivo = strtolower($objetivo);
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

        // API OpenRouter
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

        echo "<p><strong>Seu gasto calórico:</strong> " . htmlspecialchars($gasto_calorico) . " kcal</p>";
        echo "<p><strong>Objetivo:</strong> " . ucfirst(htmlspecialchars($objetivo)) . "</p>";
        echo "<p><strong>Refeições por dia:</strong> " . htmlspecialchars($refeicoes) . "</p>";
        echo "<p><strong>Macronutrientes alvo:</strong><br>
              Carboidratos: {$carbo_necessarias}g<br>
              Proteínas: {$prot_necessarias}g<br>
              Gorduras: {$gord_necessarias}g</p>";
        echo "<h2>Dieta sugerida:</h2>";
        echo "<div class='dieta'>" . htmlspecialchars($dieta) . "</div>";
    }
    ?>
</body>

</html>