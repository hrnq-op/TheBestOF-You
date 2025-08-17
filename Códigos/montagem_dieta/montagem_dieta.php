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
    $id_dieta = $_POST['id_dieta'] ?? null;

    if (!empty($dieta_conteudo) && !empty($objetivo)) {
        // Criar e salvar arquivo .txt
        $nome_arquivo = "dieta_usuario_{$id_usuario}_" . time() . ".txt";
        $caminho_arquivo = "dietas_salvas/" . $nome_arquivo;

        if (!file_exists("dietas_salvas")) {
            mkdir("dietas_salvas", 0755, true); // Usando permissão mais segura
        }

        file_put_contents($caminho_arquivo, $dieta_conteudo);

        // Atualizar dieta existente
        $stmt = $conexao->prepare("UPDATE dieta SET arquivo_dieta = ? WHERE id_dieta = ?");
        $stmt->bind_param("si", $caminho_arquivo, $id_dieta);
        $stmt->execute();
        $stmt->close();

        // Define a data atual que será usada tanto para o fim da dieta anterior quanto para o início da nova
        $data_atual = date('Y-m-d');

        // ===============================================================================
        // NOVO CÓDIGO: Finaliza a evolução da dieta anterior
        // Antes de criar um novo registro de evolução, procuramos por um registro anterior
        // do mesmo usuário que ainda não tenha uma data_fim definida e a atualizamos.
        // ===============================================================================
        $update_evolucao_stmt = $conexao->prepare(
            "UPDATE evolucao SET data_fim = ? WHERE id_usuario = ? AND data_fim IS NULL"
        );
        $update_evolucao_stmt->bind_param("si", $data_atual, $id_usuario);
        $update_evolucao_stmt->execute();
        $update_evolucao_stmt->close();
        // ===============================================================================


        // Inserir NOVA evolução com data atual e peso inicial do usuário
        $peso_stmt = $conexao->prepare("SELECT peso FROM usuario WHERE id_usuario = ?");
        $peso_stmt->bind_param("i", $id_usuario);
        $peso_stmt->execute();
        $peso_stmt->bind_result($peso_inicial);
        $peso_stmt->fetch();
        $peso_stmt->close();

        $tempo_dieta_inicial = "Inicio";

        // Insere o novo registro da dieta que se inicia hoje
        $evolucao_stmt = $conexao->prepare("INSERT INTO evolucao (data_inicio, peso_inicio, id_usuario, objetivo, tempo_dieta) VALUES (?, ?, ?, ?, ?)");
        // Note que estamos usando $data_atual para a data_inicio
        $evolucao_stmt->bind_param("sdiss", $data_atual, $peso_inicial, $id_usuario, $objetivo, $tempo_dieta_inicial);
        $evolucao_stmt->execute();
        $evolucao_stmt->close();

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

Utilize como base os seguintes alimentos para montar a dieta: " . implode(", ", $alimentos) . ". A dieta deve ser dividida em exatamente {$refeicoes} refeições ao longo do dia.

Para cada refeição, descreva de forma clara:
- Os alimentos incluídos;
- As quantidades aproximadas;
- Os valores nutricionais de cada item (calorias, carboidratos, proteínas e gorduras).

Evite termos vagos como 'porção média' ou 'quantidade moderada'. Sempre especifique as quantidades em gramas (g).
Apresente o conteúdo em formato de texto simples e organizado, apenas com tópicos e espaçamento.
Ao final, forneça um resumo com o total calórico e de macronutrientes da dieta completa.
s
Apresente o conteúdo em html, utlizando tags";

$apiKey = ''; // ⚠️ Lembre-se de proteger sua chave de API!

// 1. URL correta da API DeepSeek
$url = "https://api.deepseek.com/chat/completions";

// 2. Estrutura de dados correta para a API DeepSeek (padrão OpenAI)
$data = [
    'model' => 'deepseek-chat', // Modelo adequado para essa tarefa
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'temperature' => 0.5, // Temperatura mais baixa para respostas mais diretas e menos criativas
    'max_tokens' => 4096 // Limite de tokens para a resposta
];

// 3. Cabeçalhos corretos, incluindo a autenticação "Bearer"
$headers = [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
];

// Início da requisição cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Aumentar o tempo de espera para a API processar

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "<p>Erro ao conectar com a API: " . curl_error($ch) . "</p>";
    curl_close($ch);
    exit;
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Interpretar resposta
$resposta_api = json_decode($response, true);

// 4. Extrair o texto da resposta no formato correto da API DeepSeek
if ($http_code == 200 && isset($resposta_api['choices'][0]['message']['content'])) {
    $dieta = $resposta_api['choices'][0]['message']['content'];
} else {
    // Exibe uma mensagem de erro mais detalhada para facilitar a depuração
    $dieta = "Não foi possível gerar a dieta. Código de status: {$http_code}.";
    if (isset($resposta_api['error']['message'])) {
         $dieta .= " Mensagem da API: " . $resposta_api['error']['message'];
    } else {
        $dieta .= " Resposta completa: " . htmlspecialchars($response);
    }
}

// Agora a variável $dieta contém a resposta da IA e pode ser exibida no seu HTML
// Exemplo:
// echo nl2br(htmlspecialchars($dieta));

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