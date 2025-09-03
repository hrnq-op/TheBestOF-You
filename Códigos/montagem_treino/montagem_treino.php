<?php
session_start();
include('../conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    echo "<p>Erro: Usuário não está logado.</p>";
    exit;
}

$id_usuario = $_SESSION['id_usuario']; // Definido no início para ser usado em todo o script

// --- LÓGICA DE SALVAMENTO MODIFICADA ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salvar_treino'])) {
    $id_treino_novo = $_POST['id_treino'];
    $treino_conteudo = $_POST['treino_conteudo'];
    $nome_arquivo = "treino_usuario_" . $id_treino_novo . ".txt";

    // Inicia uma transação para garantir a integridade dos dados
    $conexao->begin_transaction();

    try {
        // ETAPA 1: Desativar qualquer treino que já esteja ativo para este usuário.
        $stmt_desativar = $conexao->prepare("UPDATE treino SET situacao = 'D' WHERE id_usuario = ? AND situacao = 'A'");
        if (!$stmt_desativar) {
            throw new Exception("Erro ao preparar a desativação: " . $conexao->error);
        }
        $stmt_desativar->bind_param("i", $id_usuario);
        $stmt_desativar->execute();
        $stmt_desativar->close();

        // ETAPA 2: Ativar o novo treino e definir o nome do arquivo.
        $stmt_ativar = $conexao->prepare("UPDATE treino SET arquivo_treino = ?, situacao = 'A' WHERE id_treino = ? AND id_usuario = ?");
        if (!$stmt_ativar) {
            throw new Exception("Erro ao preparar a ativação: " . $conexao->error);
        }
        $stmt_ativar->bind_param("sii", $nome_arquivo, $id_treino_novo, $id_usuario);
        $stmt_ativar->execute();
        $stmt_ativar->close();

        // Se tudo deu certo, confirma as alterações no banco
        $conexao->commit();

    } catch (Exception $e) {
        // Se algo deu errado, desfaz todas as alterações
        $conexao->rollback();
        die("Erro ao salvar o treino: " . $e->getMessage());
    }

    // Criar o arquivo .txt com o treino
    $caminho_pasta = "../montagem_treino/treinos_salvos/";
    if (!is_dir($caminho_pasta)) {
        mkdir($caminho_pasta, 0777, true);
    }
    file_put_contents($caminho_pasta . $nome_arquivo, $treino_conteudo);

    // Redireciona para a página inicial
    header("Location: ../pagina_principal/index.php?status=treino_salvo");
    exit;
}


// 🟢 ETAPA 1: Buscar o treino do usuário (com Prepared Statement para segurança)
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
$result_exercicios = $conexao->query("SELECT nome, grupo_muscular, link_video_execucao FROM exercicio");

while ($exercicio = $result_exercicios->fetch_assoc()) {
    $exercicios[] = [
        'nome' => $exercicio['nome'],
        'grupo_muscular' => $exercicio['grupo_muscular'],
        'link' => $exercicio['link_video_execucao']
    ];
}

// 🟢 ETAPA 4: Montar lista de exercícios para o prompt
$lista_exercicios = "";
foreach ($exercicios as $exercicio) {
    $lista_exercicios .= "Exercício: {$exercicio['nome']} | Grupos Musculares: {$exercicio['grupo_muscular']} | Vídeo: {$exercicio['link']}\n";
}

// 🟢 ETAPA 5: Montar o Prompt Completo
$descricao_divisao = $descricoesDivisoes[$divisao_treino] ?? 'Sem descrição detalhada.';

$prompt = "
Você é um especialista em educação física e personal trainer. Crie um plano de treino detalhado para academia.

INFORMAÇÕES DO ALUNO:
- Divisão de Treino: '{$divisao_treino}'
- Descrição da Divisão: {$descricao_divisao}
- Nível de Experiência: '{$nivel_de_treino}'
- Dias de Treino por Semana: {$dias_de_treino}
- Músculos para dar Ênfase: '{$enfase}'

REGRAS OBRIGATÓRIAS:
1.  Use APENAS exercícios da lista fornecida abaixo. NÃO invente exercícios.
2.  Para cada dia de treino, liste os exercícios.
3.  Para cada exercício, especifique:
    - Nome do Exercício
    - Séries e Repetições (ajustado para o nível de experiência)
    - O link para o vídeo de execução.
4.  O output deve ser apenas o plano de treino, de forma clara e organizada por dia.

MODELO DE RESPOSTA ESPERADO:

Dia 1: Push (Peito, Ombros e Tríceps)

- Exercício: Supino Reto
  Séries: 4x8-12
  Execução: [Link do vídeo]

- Exercício: Desenvolvimento com Halteres
  Séries: 3x10-15
  Execução: [Link do vídeo]
...e assim por diante.

---
LISTA DE EXERCÍCIOS DISPONÍVEIS:
{$lista_exercicios}
";


// 🟢 ETAPA 6: Chamar a API do DeepSeek
$apiKey = ''; // ⚠️ SUA CHAVE DE API - É mais seguro usar variáveis de ambiente!

// URL correta da API DeepSeek para chat
$url = "https://api.deepseek.com/chat/completions";

// Estrutura de dados correta para a API DeepSeek (padrão OpenAI)
$data = [
    'model' => 'deepseek-chat', // ou 'deepseek-reasoner' para tarefas mais complexas
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'temperature' => 0.7, // Ajusta a criatividade da resposta
    'max_tokens' => 4096 // Limite máximo de tokens na resposta
];

// Monta os cabeçalhos da requisição, incluindo a autorização
$headers = [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey // O formato correto é "Bearer [sua_chave]"
];

// Início da requisição cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Aumenta o tempo limite para 120 segundos

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "<p>Erro ao conectar com a API: " . curl_error($ch) . "</p>";
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decodifica a resposta da API
$resposta_api = json_decode($response, true);

// Extrai o texto da resposta no formato correto da API DeepSeek/OpenAI
if (isset($resposta_api['choices'][0]['message']['content'])) {
    $treino = $resposta_api['choices'][0]['message']['content'];
} else {
    // Se houver um erro, exibe a resposta da API para depuração
    $treino = "Não foi possível gerar o treino. Resposta do servidor: \n" . htmlspecialchars(print_r($resposta_api, true));
}
// 🟢 ETAPA 8: Salvar exercícios relacionados no banco
$linhas = explode("\n", $treino);
$exercicios_extraidos = [];

foreach ($linhas as $linha) {
    if (stripos($linha, "Exercício:") !== false) {
        $nome_exercicio = trim(str_ireplace("Exercício:", "", $linha));
        $exercicios_extraidos[] = $nome_exercicio;
    }
}

foreach (array_unique($exercicios_extraidos) as $nome_exercicio) { // Usar array_unique para não inserir duplicados
    $stmt_find = $conexao->prepare("SELECT id_exercicio FROM exercicio WHERE nome LIKE ?");
    $like_nome = "%" . trim($nome_exercicio) . "%";
    $stmt_find->bind_param("s", $like_nome);
    $stmt_find->execute();
    $result_ex = $stmt_find->get_result();

    if ($row_ex = $result_ex->fetch_assoc()) {
        $id_exercicio = $row_ex['id_exercicio'];

        $insert = $conexao->prepare("INSERT INTO treino_exercicio (id_treino, id_exercicio) VALUES (?, ?)");
        $insert->bind_param("ii", $id_treino, $id_exercicio);
        $insert->execute();
        $insert->close();
    }
    $stmt_find->close();
}
require_once '../libs/Parsedown.php';
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
</head>

<body>

    <header>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo">
            </a>
        </div>
        <div class="site-name">Treino</div>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo">
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
        <div class="treino"><?= $treino_html ?></div>

        <div class="botoes">
            <form method="post" id="formSalvar">
                <input type="hidden" name="salvar_treino" value="1">
                <input type="hidden" name="id_treino" value="<?= $id_treino ?>">
                <input type="hidden" name="treino_conteudo" value="<?= htmlspecialchars($treino, ENT_QUOTES) ?>">
                <button type="submit" class="salvar" id="btnSalvar"><i class="fas fa-arrow-right"></i> Avançar</button>
            </form>

            <form method="post" id="formGerar"> <button type="submit" class="outra" id="btnGerarOutra"><i class="fas fa-sync-alt"></i> Gerar outro treino</button>
            </form>
        </div>

        <div id="spinner" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; display:flex; justify-content:center; align-items:center; flex-direction: column;">
             <i class="fas fa-spinner fa-spin" style="color:white; font-size:48px;"></i>
             <p style="color:white; font-size:24px; margin-top: 20px;">Gerando novo treino, aguarde...</p>
        </div>

        <script>
            // Esconde o spinner no carregamento inicial da página
            document.getElementById('spinner').style.display = 'none';

            document.getElementById('formGerar').addEventListener('submit', function(e) {
                // Ao clicar em "Gerar outro treino", mostra o spinner
                document.getElementById('spinner').style.display = 'flex';
            });
        </script>
    </div>
</body>

</html>