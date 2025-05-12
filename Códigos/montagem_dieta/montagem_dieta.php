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
    $dieta_conteudo = $_POST['dieta_conteudo'] ?? '';
    $objetivo = $_POST['objetivo'] ?? '';
    $refeicoes = $_POST['refeicoes'] ?? 0;

    if (!empty($dieta_conteudo) && !empty($objetivo)) {
        // Criar e salvar arquivo .txt
        $nome_arquivo = "dieta_usuario_{$id_usuario}_" . time() . ".txt";
        $caminho_arquivo = "dietas_salvas/" . $nome_arquivo;

        if (!file_exists("dietas_salvas")) {
            mkdir("dietas_salvas", 0777, true);
        }

        file_put_contents($caminho_arquivo, $dieta_conteudo);

        // Inserir nova dieta
        $data_inicio = date('Y-m-d');
        $situacao = 'A'; // Ativa

        $stmt = $conexao->prepare("INSERT INTO dieta (data_inicio, id_usuario, objetivo, situacao, refeicoes, dieta) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissis", $data_inicio, $id_usuario, $objetivo, $situacao, $refeicoes, $caminho_arquivo);
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

$acao = ($objetivo === "cutting") ? "déficit calórico" : "superávit calórico";

// Exemplo de prompt gerado dinamicamente
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

Evite termos vagos como 'porção média' ou 'quantidade moderada'. Sempre especifique as quantidades em gramas (g).
Apresente o conteúdo em formato de texto simples e organizado, apenas com tópicos e espaçamento.
Ao final, forneça um resumo com o total calórico e de macronutrientes da dieta completa.

Apresente o conteúdo em formato de texto simples e organizado, sem tabelas ou qualquer tipo de formatação. Use apenas tópicos e espaçamento adequado para facilitar a leitura.";

$apiKey = ''; // Substitua pela sua chave real

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key=$apiKey";

// Corpo da requisição no formato da API do Gemini
$data = [
    "contents" => [[
        "role" => "user",
        "parts" => [["text" => $prompt]]
    ]]
];


// Início da requisição cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "<p>Erro ao conectar com a API: " . curl_error($ch) . "</p>";
    curl_close($ch);
    exit;
}
echo "<pre>";
var_dump($response); // Adicione isso para ver a resposta bruta
echo "</pre>";

curl_close($ch);

// Interpretar resposta
$resposta = json_decode($response, true);

// A resposta da Gemini vem em 'candidates' → 'content' → 'parts'
$dieta = $resposta['candidates'][0]['content']['parts'][0]['text'] ?? "Não foi possível gerar a dieta.";

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Dieta Gerada</title>
    <link rel="stylesheet" href="montagem_dieta.css?=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <header>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo"> <!-- Logo esquerda -->
            </a>
        </div>
        <div class="site-name">
            Dieta
        </div>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo"> <!-- Logo direita -->
            </a>
        </div>
    </header>

    <div class="qlqr">
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
                <input type="hidden" name="objetivo" value="<?= htmlspecialchars($objetivo, ENT_QUOTES) ?>">
                <input type="hidden" name="refeicoes" value="<?= $refeicoes ?>">
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
    </div>
</body>

</html>