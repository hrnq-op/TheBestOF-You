<?php
session_start();
include('../conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    echo "<p>Erro: Usuário não está logado.</p>";
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

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

        header("Location: ../pagina_principal/index.php");
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

// Monta o prompt e gera dieta via API OpenAI
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

// Dados para a API do OpenAI
$apiKey = "";
$url = "https://api.openai.com/v1/chat/completions";

$data = [
    "model" => "gpt-3.5-turbo",
    "store" => true,
    "messages" => [
        ["role" => "user", "content" => $prompt]
    ]
];

// Configurar cURL
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "<p>Erro ao conectar com a API: " . curl_error($ch) . "</p>";
    curl_close($ch);
    exit;
}

curl_close($ch);

// Interpretar resposta
$resposta = json_decode($response, true);
$dieta = $resposta['choices'][0]['text'] ?? "Não foi possível gerar a dieta.";
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Dieta Gerada</title>
    <link rel="stylesheet" href="montagem_dieta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        <form method="post" id="formSalvar">
            <input type="hidden" name="salvar_dieta" value="1">
            <input type="hidden" name="id_dieta" value="<?= $id_dieta ?>">
            <input type="hidden" name="dieta_conteudo" value="<?= htmlspecialchars($dieta, ENT_QUOTES) ?>">
            <button type="submit" class="salvar" id="btnSalvar"><i class="fas fa-arrow-right"></i> Avançar</button>
        </form>


        <form method="get" id="formGerar">
            <button type="submit" class="outra" id="btnGerarOutra"><i class="fas fa-sync-alt"></i> Gerar outra dieta</button>
        </form>
    </div>


    <div id="spinner" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div style="color:white; font-size:24px;">
            <i class="fas fa-spinner fa-spin"></i> Gerando dieta...
        </div>
    </div>


    <script>
        window.onload = function() {
            const btnGerarOutra = document.getElementById('btnGerarOutra');
            const spinner = document.getElementById('spinner');

            if (btnGerarOutra && spinner) {
                btnGerarOutra.addEventListener('click', function() {
                    spinner.style.display = 'flex';
                });
            }
        }
    </script>
</body>

</html>