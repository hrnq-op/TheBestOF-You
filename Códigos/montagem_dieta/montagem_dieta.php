<?php
session_start();
include('../conexao.php');
require_once '../libs/Parsedown.php'; // 1. IMPORTANTE: Carrega a biblioteca de formatação

if (!isset($_SESSION['id_usuario'])) {
    echo "<p>Erro: Usuário não está logado.</p>";
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// 🟢 ETAPA 1: Se clicou em "Avançar" para SALVAR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salvar_dieta'])) {
    $dieta_conteudo = $_POST['dieta_conteudo'] ?? '';
    $objetivo = $_POST['objetivo'] ?? '';
    $refeicoes = $_POST['refeicoes'] ?? 0;
    $id_dieta = $_POST['id_dieta'] ?? null;

    if (!empty($dieta_conteudo) && !empty($objetivo)) {
        $nome_arquivo = "dieta_usuario_{$id_usuario}_" . time() . ".txt";
        $caminho_arquivo = "dietas_salvas/" . $nome_arquivo;

        if (!file_exists("dietas_salvas")) {
            mkdir("dietas_salvas", 0755, true);
        }

        file_put_contents($caminho_arquivo, $dieta_conteudo);

        $stmt = $conexao->prepare("UPDATE dieta SET arquivo_dieta = ? WHERE id_dieta = ?");
        $stmt->bind_param("si", $caminho_arquivo, $id_dieta);
        $stmt->execute();
        $stmt->close();

        $data_atual = date('Y-m-d');

        // Atualiza evolução anterior
        $update_evolucao_stmt = $conexao->prepare("UPDATE evolucao SET data_fim = ? WHERE id_usuario = ? AND data_fim IS NULL");
        $update_evolucao_stmt->bind_param("si", $data_atual, $id_usuario);
        $update_evolucao_stmt->execute();
        $update_evolucao_stmt->close();

        // Insere nova evolução
        $peso_stmt = $conexao->prepare("SELECT peso FROM usuario WHERE id_usuario = ?");
        $peso_stmt->bind_param("i", $id_usuario);
        $peso_stmt->execute();
        $peso_stmt->bind_result($peso_inicial);
        $peso_stmt->fetch();
        $peso_stmt->close();

        $tempo_dieta_inicial = "Inicio";
        $evolucao_stmt = $conexao->prepare("INSERT INTO evolucao (data_inicio, peso_inicio, id_usuario, objetivo, tempo_dieta) VALUES (?, ?, ?, ?, ?)");
        $evolucao_stmt->bind_param("sdiss", $data_atual, $peso_inicial, $id_usuario, $objetivo, $tempo_dieta_inicial);
        $evolucao_stmt->execute();
        $evolucao_stmt->close();

        $conexao->close();
        
        // Limpa a sessão da dieta gerada ao salvar
        unset($_SESSION['dieta_temp']); 

        header("Location: ../pagina_principal/index.php");
        exit;
    } else {
        echo "<p>Erro ao salvar a dieta.</p>";
    }
}

// 🟢 ETAPA 2: Preparação dos dados para geração
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

$alimentos = [];
$result = $conexao->query("SELECT nome FROM alimentos WHERE id_dieta = $id_dieta");
while ($row_alim = $result->fetch_assoc()) {
    $alimentos[] = $row_alim['nome'];
}
$conexao->close();

if (empty($alimentos)) {
    echo "<p>Nenhum alimento foi enviado.</p>";
    exit;
}

$acao = ($objetivo === "cutting") ? "déficit calórico" : "superávit calórico";

// 🟢 ETAPA 3: Geração da Dieta (Só gera se não existir na sessão ou se forçar nova)
// Isso evita que a dieta mude sozinha se o usuário der F5
if (!isset($_SESSION['dieta_temp']) || (isset($_GET['gerar_nova']) && $_GET['gerar_nova'] == 1)) {
    
    // 2. IMPORTANTE: Prompt ajustado para pedir TABELA MARKDOWN
    $prompt = "
    Atue como um nutricionista esportivo de elite.
    Crie uma dieta estruturada para um usuário em fase de **{$objetivo}**.
    
    **Dados do Usuário:**
    - Gasto Calórico Base: {$gasto_calorico} kcal
    - Estratégia: {$acao}
    - Refeições diárias: {$refeicoes}
    - Metas: Carboidratos ~{$carbo_necessarias}g, Proteínas ~{$prot_necessarias}g, Gorduras ~{$gord_necessarias}g.
    
    **Alimentos Obrigatórios:** " . implode(", ", $alimentos) . ".
    
    **FORMATO OBRIGATÓRIO DE SAÍDA:**
    Responda APENAS com uma Tabela Markdown. Não adicione textos de introdução ou conclusão.
    A tabela deve ter as colunas: 'Refeição', 'Descrição (Alimentos e Quantidades em gramas)', 'Calorias', 'Proteínas', 'Carboidratos', 'Gorduras'.
    ";

    $apiKey = '';
    $url = "https://api.deepseek.com/chat/completions";

    $data = [
        'model' => 'deepseek-chat',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.5,
        'max_tokens' => 4096
    ];

    $headers = ["Content-Type: application/json", "Authorization: Bearer " . $apiKey];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resposta_api = json_decode($response, true);

    if ($http_code == 200 && isset($resposta_api['choices'][0]['message']['content'])) {
        $_SESSION['dieta_temp'] = $resposta_api['choices'][0]['message']['content'];
    } else {
        $_SESSION['dieta_temp'] = "Erro ao gerar: " . ($resposta_api['error']['message'] ?? 'Desconhecido');
    }
}

$dieta = $_SESSION['dieta_temp'];

// 3. IMPORTANTE: Processa o Markdown para HTML
$Parsedown = new Parsedown();
$dieta_html = $Parsedown->text($dieta);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dieta Gerada</title>
    <link rel="stylesheet" href="montagem_dieta.css?=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        .dieta table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .dieta th, .dieta td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .dieta th {
            background-color: #00c853;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .dieta tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .dieta tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo"><a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a></div>
        <div class="site-name">Dieta</div>
        <div class="logo"><a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a></div>
    </header>

    <div class="qlqr">
        <h1>Dieta Personalizada</h1>
        <p><strong>Gasto calórico:</strong> <?= htmlspecialchars($gasto_calorico) ?> kcal</p>
        <p><strong>Objetivo:</strong> <?= ucfirst(htmlspecialchars($objetivo)) ?></p>
        <p><strong>Refeições por dia:</strong> <?= $refeicoes ?></p>
        <p><strong>Macronutrientes alvo:</strong> Carboidratos: <?= $carbo_necessarias ?>g | Proteínas: <?= $prot_necessarias ?>g | Gorduras: <?= $gord_necessarias ?>g</p>

        <h2>Dieta sugerida:</h2>
        
        <div class="dieta"><?= $dieta_html ?></div>

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
                <input type="hidden" name="gerar_nova" value="1">
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