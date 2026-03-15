<?php
session_start();
include('../conexao.php');
require_once '../libs/Parsedown.php';

if (!isset($_SESSION['id_usuario'])) {
    echo "<p>Erro: Usuário não está logado.</p>";
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// 🟢 ETAPA 1: SALVAR O TREINO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salvar_treino'])) {
    $id_treino_novo = $_POST['id_treino'];
    $treino_conteudo = $_POST['treino_conteudo'];
    $nome_arquivo = "treino_usuario_" . $id_treino_novo . "_" . time() . ".txt";

    $conexao->begin_transaction();

    try {
        // Desativar treino anterior
        $stmt_desativar = $conexao->prepare("UPDATE treino SET situacao = 'D' WHERE id_usuario = ? AND situacao = 'A'");
        $stmt_desativar->bind_param("i", $id_usuario);
        $stmt_desativar->execute();
        $stmt_desativar->close();

        // Ativar novo treino
        $stmt_ativar = $conexao->prepare("UPDATE treino SET arquivo_treino = ?, situacao = 'A' WHERE id_treino = ? AND id_usuario = ?");
        $caminho_db = "" . $nome_arquivo; // Caminho relativo para o banco
        $stmt_ativar->bind_param("sii", $caminho_db, $id_treino_novo, $id_usuario);
        $stmt_ativar->execute();
        $stmt_ativar->close();

        // Salvar associações de exercícios (Parsing simples)
        $linhas = explode("\n", $treino_conteudo);
        $exercicios_extraidos = [];
        foreach ($linhas as $linha) {
            if (stripos($linha, "Exercício:") !== false) {
                $nome_exercicio = trim(str_ireplace(["-", "*", "Exercício:"], "", $linha));
                $exercicios_extraidos[] = $nome_exercicio;
            }
        }

        foreach (array_unique($exercicios_extraidos) as $nome_exercicio) {
            $stmt_find = $conexao->prepare("SELECT id_exercicio FROM exercicio WHERE nome LIKE ?");
            $like_nome = "%" . trim($nome_exercicio) . "%";
            $stmt_find->bind_param("s", $like_nome);
            $stmt_find->execute();
            $result_ex = $stmt_find->get_result();

            if ($row_ex = $result_ex->fetch_assoc()) {
                $id_exercicio = $row_ex['id_exercicio'];
                $insert = $conexao->prepare("INSERT INTO treino_exercicio (id_treino, id_exercicio) VALUES (?, ?)");
                $insert->bind_param("ii", $id_treino_novo, $id_exercicio);
                $insert->execute();
                $insert->close();
            }
            $stmt_find->close();
        }

        $conexao->commit();

        // Salvar arquivo físico
        $caminho_pasta = "treinos_salvos/";
        if (!is_dir($caminho_pasta)) {
            mkdir($caminho_pasta, 0755, true);
        }
        file_put_contents($caminho_pasta . $nome_arquivo, $treino_conteudo);

        // Limpa sessão e redireciona
        unset($_SESSION['treino_temp']);
        header("Location: ../pagina_principal/index.php?status=treino_salvo");
        exit;

    } catch (Exception $e) {
        $conexao->rollback();
        die("Erro ao salvar o treino: " . $e->getMessage());
    }
}

// 🟢 ETAPA 2: BUSCAR DADOS DO USUÁRIO
$query_busca = "
    SELECT t.id_treino, t.divisao_treino, t.nivel_de_treino, t.dias_de_treino, t.enfase_muscular
    FROM treino t
    WHERE t.id_usuario = ?
    ORDER BY t.id_treino DESC
    LIMIT 1";
$stmt = $conexao->prepare($query_busca);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result_treino = $stmt->get_result();

if (!$result_treino || $result_treino->num_rows === 0) {
    echo "<p>Erro: Nenhum treino encontrado para este usuário.</p>";
    exit;
}

$row = $result_treino->fetch_assoc();
$id_treino = $row['id_treino'];
$divisao_treino = trim($row['divisao_treino']);
$nivel_de_treino = strtolower($row['nivel_de_treino']);
$dias_de_treino = (int) $row['dias_de_treino'];
$enfase = !empty($row['enfase_muscular']) ? strtolower($row['enfase_muscular']) : 'Nenhuma';

// Descrições
$descricoesDivisoes = [
    "Full Body" => "Treino de corpo inteiro em todas as sessões.",
    "Upper/Lower" => "Divisão entre parte superior e inferior.",
    "ABC" => "Divisão clássica em 3 dias: Peito/Tríceps, Costas/Bíceps, Pernas/Ombro.",
    "PPL" => "Push (Empurrar), Pull (Puxar), Legs (Pernas).",
    "ABCD" => "Divisão em 4 dias focada em grupos musculares menores.",
    "Bro Split" => "Divisão com 1 grupo muscular por dia.",
    "Full Body 2x" => "Corpo inteiro 2x por semana.",
    "ABC 2x" => "Treino ABC repetido duas vezes na semana.",
    "Upper/Lower 2x" => "Divisão Upper/Lower duas vezes na semana."
];

// Buscar exercícios
$exercicios = [];
$result_exercicios = $conexao->query("SELECT nome, grupo_muscular, link_video_execucao FROM exercicio");
while ($exercicio = $result_exercicios->fetch_assoc()) {
    $exercicios[] = [
        'nome' => $exercicio['nome'],
        'grupo_muscular' => $exercicio['grupo_muscular'],
        'link' => $exercicio['link_video_execucao']
    ];
}

$lista_exercicios = "";
foreach ($exercicios as $exercicio) {
    $lista_exercicios .= "Exercício: {$exercicio['nome']} | Grupos: {$exercicio['grupo_muscular']} | Vídeo: {$exercicio['link']}\n";
}

// 🟢 ETAPA 3: GERAÇÃO DO TREINO (Se não existir na sessão ou forçar novo)
if (!isset($_SESSION['treino_temp']) || (isset($_GET['gerar_novo']) && $_GET['gerar_novo'] == 1)) {

    $descricao_divisao = $descricoesDivisoes[$divisao_treino] ?? 'Padrão';

    $prompt = "
    Atue como um personal trainer de elite. Crie um plano de treino detalhado.
    
    DADOS:
    - Divisão: '{$divisao_treino}' ({$descricao_divisao})
    - Nível: '{$nivel_de_treino}'
    - Frequência: {$dias_de_treino} dias/semana
    - Ênfase: '{$enfase}'
    
    REGRAS:
    1. Use APENAS exercícios da lista abaixo.
    2. Formate a resposta preferencialmente em Markdown (pode usar tabelas ou listas).
    3. Inclua séries, repetições e o link do vídeo para cada exercício.
    
    LISTA DE EXERCÍCIOS:
    {$lista_exercicios}
    ";

    $apiKey = '';
    $url = "https://api.deepseek.com/chat/completions";

    $data = [
        'model' => 'deepseek-chat',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7,
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
        $_SESSION['treino_temp'] = $resposta_api['choices'][0]['message']['content'];
    } else {
        $_SESSION['treino_temp'] = "Erro ao gerar treino. Tente novamente.";
    }
}

$treino = $_SESSION['treino_temp'];

// Processar Markdown para HTML
$Parsedown = new Parsedown();
$treino_html = $Parsedown->text($treino);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Treino Gerado</title>
    <link rel="stylesheet" href="montagem_treino.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* Estilos Padronizados para Tabelas (caso a IA gere tabelas) */
        .treino table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .treino th, .treino td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .treino th {
            background-color: #00c853;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .treino tr:nth-child(even) { background-color: #f9f9f9; }
        .treino tr:hover { background-color: #f1f1f1; }

        /* Botões */
        .botao-pdf {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #e74c3c;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-family: inherit;
            font-weight: bold;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
            height: 44px; /* Altura fixa para alinhar com outros botões */
            box-sizing: border-box;
        }
        .botao-pdf:hover {
            background-color: #c0392b;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo"><a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a></div>
        <div class="site-name">Treino</div>
        <div class="logo"><a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a></div>
    </header>

    <div class="qlqr">
        <h1>Treino Personalizado</h1>
        <p><strong>Divisão:</strong> <?= htmlspecialchars($divisao_treino) ?></p>
        <p><strong>Nível:</strong> <?= ucfirst(htmlspecialchars($nivel_de_treino)) ?></p>
        <p><strong>Dias:</strong> <?= htmlspecialchars($dias_de_treino) ?></p>
        <p><strong>Ênfase:</strong> <?= htmlspecialchars($enfase) ?></p>

        <h2>Treino sugerido:</h2>
        <div class="treino"><?= $treino_html ?></div>

        <div class="botoes">
            <form method="post" id="formSalvar">
                <input type="hidden" name="salvar_treino" value="1">
                <input type="hidden" name="id_treino" value="<?= $id_treino ?>">
                <input type="hidden" name="treino_conteudo" value="<?= htmlspecialchars($treino, ENT_QUOTES) ?>">
                <button type="submit" class="salvar" id="btnSalvar"><i class="fas fa-arrow-right"></i> Avançar</button>
            </form>

            <?php if (!empty($treino)): ?>
                <a href="gerar_pdf_treino.php?treino=<?= urlencode($treino); ?>" target="_blank" class="botao-pdf">
                    <i class="fas fa-file-pdf"></i> Baixar PDF
                </a>
            <?php endif; ?>

            <form method="get" id="formGerar">
                <input type="hidden" name="gerar_novo" value="1">
                <button type="submit" class="outra" id="btnGerarOutra"><i class="fas fa-sync-alt"></i> Gerar outro treino</button>
            </form>
        </div>

        <div id="spinner" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; flex-direction:column;">
            <i class="fas fa-spinner fa-spin" style="color:white; font-size:48px;"></i>
            <p style="color:white; font-size:24px; margin-top:20px;">Gerando treino...</p>
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