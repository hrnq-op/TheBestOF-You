<?php
session_start();
include('../conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    echo "<p>Erro: Usuário não está logado.</p>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salvar_treino'])) {
    $id_treino = $_POST['id_treino'];
    $treino_conteudo = $_POST['treino_conteudo'];

    // Caminho do arquivo que será salvo no banco
    $nome_arquivo = "treino_usuario_" . $id_treino . ".txt";
    $situacao = "A";

    // Atualizar o nome do arquivo no banco
    $stmt = $conexao->prepare("UPDATE treino SET arquivo_treino = ?, situacao = ? WHERE id_treino = ?");
    $stmt->bind_param("ssi", $nome_arquivo, $situacao, $id_treino);
    $stmt->execute();
    $stmt->close();

    // Criar o arquivo .txt com o treino
    $caminho_pasta = "../montagem_treino/treinos_salvos/";
    if (!is_dir($caminho_pasta)) {
        mkdir($caminho_pasta, 0777, true);
    }
    file_put_contents($caminho_pasta . $nome_arquivo, $treino_conteudo);


    // Redireciona para a página inicial
    header("Location: ../pagina_principal/index.php");
    exit;
}


$id_usuario = $_SESSION['id_usuario'];

// 🟢 ETAPA 1: Buscar o treino do usuário
$stmt = $conexao->query("
    SELECT t.id_treino, t.divisao_treino, t.nivel_de_treino, t.dias_de_treino, t.enfase
    FROM treino t
    WHERE t.id_usuario = $id_usuario
    ORDER BY t.id_treino DESC
    LIMIT 1
");

if (!$stmt || $stmt->num_rows === 0) {
    echo "<p>Erro: Nenhum treino encontrado para este usuário.</p>";
    exit;
}

$row = $stmt->fetch_assoc();
$id_treino = $row['id_treino'];
$divisao_treino = trim($row['divisao_treino']);
$nivel_de_treino = strtolower($row['nivel_de_treino']);
$dias_de_treino = (int) $row['dias_de_treino'];
$enfase = strtolower($row['enfase']);

// 🟢 ETAPA 2: Descrição das divisões
$descricoesDivisoes = [
    "Full Body" => "Treino de corpo inteiro em todas as sessões, trabalhando todos os grupos musculares principais em cada treino. Exercícios compostos como agachamento, supino e levantamento terra são a base. Ideal para iniciantes ou quem treina 2-3 vezes por semana.",
    "Upper/Lower" => "Divisão entre parte superior (Upper: peito, costas, ombros e braços) e inferior (Lower: quadríceps, posterior, glúteos e panturrilhas) do corpo. Permite maior volume por grupo muscular. Recomendado para intermediários.",
    "ABC" => "Divisão clássica em 3 dias: A - Peito/Tríceps, B - Costas/Bíceps, C - Pernas/Ombro.",
    "PPL" => "Divisão baseada em padrões de movimento: Push (Peito, Ombros, Tríceps), Pull (Costas, Bíceps), Legs (Pernas e Core). Excelente para força e hipertrofia.",
    "ABCD" => "Divisão em 4 dias focada em grupos musculares menores: Peito, Costas, Pernas e Ombros/Braços.",
    "Bro Split" => "Divisão clássica com 1 grupo muscular por dia. Permite alto volume. Ideal para avançados.",
    "Full Body 2x" => "Treino de corpo inteiro realizado 2 vezes por semana. Indicado para iniciantes ou quem tem pouco tempo.",
    "PPL 2x" => "Ciclo Push-Pull-Legs realizado duas vezes por semana, focando alta frequência e volume.",
    "PPL + Upper/Lower" => "Combinação de Push, Pull, Legs e Upper/Lower, equilibrando volume e recuperação.",
    "Upper/Lower + Full Body" => "Combinação de Upper/Lower e Full Body, ideal para 4-5 treinos semanais.",
    "PPL + Full Body" => "Combinação de Push, Pull, Legs e treinos de corpo inteiro. Foco em estímulo frequente.",
    "PPL + Upper" => "Combinação de Push, Pull, Legs e treinos de Upper (parte superior).",
    "ABC 2x" => "Treino ABC repetido duas vezes na semana. Alta frequência para quem treina 6x/semana.",
    "Upper/Lower 2x" => "Divisão Upper/Lower duas vezes na semana. Excelente para intermediários.",
    "ABCDE" => "Divisão de 5 dias com foco em um grupo por treino: Peito, Costas, Pernas, Ombros e Braços."
];

// 🟢 ETAPA 3: Buscar exercícios disponíveis
$exercicios = [];
$result = $conexao->query("SELECT nome, grupo_muscular, link_video_execucao FROM exercicio");

while ($exercicio = $result->fetch_assoc()) {
    $exercicios[] = [
        'nome' => $exercicio['nome'],
        'grupo_muscular' => $exercicio['grupo_muscular'],
        'link' => $exercicio['link_video_execucao']
    ];
}

// 🟢 ETAPA 4: Montar lista de exercícios
$lista_exercicios = "";

foreach ($exercicios as $exercicio) {
    $grupos = explode(",", $exercicio['grupo_muscular']);
    $grupos_formatados = array_map('trim', $grupos);
    $grupos_listados = "- " . implode("\n- ", $grupos_formatados);

    $lista_exercicios .= "🏋️ Exercício: {$exercicio['nome']}\n"
        . "Grupos Musculares:\n{$grupos_listados}\n"
        . "🎥 Vídeo: {$exercicio['link']}\n\n";
}

// 🟢 ETAPA 5: Montar o Prompt Completo
$descricao_divisao = $descricoesDivisoes[$divisao_treino] ?? '';

$prompt = "
Você é um gerador de treinos personalizado para academia.

Divisão escolhida: '{$divisao_treino}'.
Descrição da divisão: {$descricao_divisao}

Nível de treino: '{$nivel_de_treino}'
Dias de treino por semana: '{$dias_de_treino}'
Ênfase no treino: '{$enfase}'

⚡ IMPORTANTE:
- NÃO invente exercícios novos.
- USE apenas os exercícios listados abaixo.
- PARA CADA EXERCÍCIO mostre:
  - Nome do exercício
  - Lista de grupos musculares separados
  - Link de execução
  - Quantidade de séries e repetições recomendadas.

⚡ MODELO DE FORMATO ESPERADO (se fosse uma divisão PPL):

Dia 1 - Push (Peito, Ombros, Tríceps)

Exercício: Supino Reto 
Execução: 🎥 https://link.com 
Grupos Musculares Trabalhados: Peito,Ombro,Tríceps

Séries: 4
Repetições: 8-10

[SEGUIR ESTE PADRÃO]

---

Lista de exercícios disponíveis:

$lista_exercicios
";

// 🟢 ETAPA 6: Chamar a API
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

curl_close($ch);

// Interpretar resposta
$resposta = json_decode($response, true);

// A resposta da Gemini vem em 'candidates' → 'content' → 'parts'
$treino = $resposta['candidates'][0]['content']['parts'][0]['text'] ?? "Não foi possível gerar o treino.";
// 🟢 ETAPA 8: Salvar exercícios relacionados no banco
$linhas = explode("\n", $treino);
$exercicios_extraidos = [];

foreach ($linhas as $linha) {
    if (stripos($linha, "Exercício:") !== false) {
        $nome_exercicio = trim(str_ireplace("Exercício:", "", $linha));
        $exercicios_extraidos[] = $nome_exercicio;
    }
}

foreach ($exercicios_extraidos as $nome_exercicio) {
    $stmt = $conexao->prepare("SELECT id_exercicio FROM exercicio WHERE nome LIKE ?");
    $like_nome = "%$nome_exercicio%";
    $stmt->bind_param("s", $like_nome);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $id_exercicio = $row['id_exercicio'];

        $insert = $conexao->prepare("INSERT INTO treino_exercicio (id_treino, id_exercicio) VALUES (?, ?)");
        $insert->bind_param("ii", $id_treino, $id_exercicio);
        $insert->execute();
        $insert->close();
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Treino Gerado</title>
    <link rel="stylesheet" href="montagem_treino.css?v=2">
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
            Treino
        </div>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo"> <!-- Logo direita -->
            </a>
        </div>
    </header>

    <div class="qlqr">
        <h1>Treino Personalizado</h1>
        <p><strong>Divisão de Treino:</strong> <?= htmlspecialchars($divisao_treino) ?></p>
        <p><strong>Nível de Treino:</strong> <?= ucfirst(htmlspecialchars($nivel_de_treino)) ?></p>
        <p><strong>Dias de Treino:</strong> <?= htmlspecialchars($dias_de_treino) ?></p>
        <p><strong>Ênfase:</strong> <?= htmlspecialchars($enfase) ?></p>

        <h2>Treino sugerido:</h2>
        <div class="treino"><?= nl2br(htmlspecialchars($treino)) ?></div>

        <div class="botoes">
            <form method="post" id="formSalvar">
                <input type="hidden" name="salvar_treino" value="1">
                <input type="hidden" name="id_treino" value="<?= $id_treino ?>">
                <input type="hidden" name="treino_conteudo" value="<?= htmlspecialchars($treino, ENT_QUOTES) ?>">
                <button type="submit" class="salvar" id="btnSalvar"><i class="fas fa-arrow-right"></i> Avançar</button>
            </form>

            <form method="get" id="formGerar">
                <button type="submit" class="outra" id="btnGerarOutra"><i class="fas fa-sync-alt"></i> Gerar outro treino</button>
            </form>
        </div>

        <div id="spinner" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
            <div style="color:white; font-size:24px;">
                <i class="fas fa-spinner fa-spin"></i> Gerando treino...
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