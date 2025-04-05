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
    <h1>Sua dieta personalizada</h1>

    <?php
    include "conexao.php";

    // Pega o último usuário que cadastrou uma dieta
    $stmt = $conexao->query("
        SELECT u.id_usuario, u.gasto_calorico_total, d.id_dieta, d.objetivo, d.refeicoes
        FROM usuario u
        INNER JOIN dieta d ON u.id_usuario = d.id_usuario
        ORDER BY d.id_dieta DESC
        LIMIT 1
    ");

    if ($stmt && $stmt->num_rows > 0) {
        $row = $stmt->fetch_assoc();
        $id_usuario = $row['id_usuario'];
        $id_dieta = $row['id_dieta'];
        $gasto_calorico = $row['gasto_calorico_total'];
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

        $prompt = "Crie uma dieta personalizada de aproximadamente {$gasto_calorico} calorias para um usuário em fase de {$objetivo}, estabelecendo um {$acao} saudável. Use somente os seguintes alimentos: " . implode(", ", $alimentos) . ". Monte uma dieta diária detalhada com exatamente {$refeicoes} refeições organizadas muito bem. Em cada refeição, informe os alimentos, as quantidades e os valores nutricionais (calorias, carboidratos, proteínas, gorduras). Apresente o conteúdo em texto corrido, de forma clara, organizada e sem o uso de tabelas e sem formatação.";

        // Chave da API
        $apiKey = "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"; // Substitua pela sua chave
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

        echo "<p><strong>Gasto calórico estimado da dieta:</strong> " . htmlspecialchars($gasto_calorico) . " kcal</p>";
        echo "<p><strong>Objetivo:</strong> " . ucfirst(htmlspecialchars($objetivo)) . "</p>";
        echo "<p><strong>Refeições por dia:</strong> " . htmlspecialchars($refeicoes) . "</p>";
        echo "<h2>Dieta sugerida:</h2>";
        echo "<div class='dieta'>" . htmlspecialchars($dieta) . "</div>";
    }
    ?>
</body>

</html>