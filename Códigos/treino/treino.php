<?php
session_start();
include "../conexao.php";

if (!isset($_SESSION['id_usuario'])) {
    die("Usuário não autenticado.");
}

$usuario_id = $_SESSION['id_usuario'];

// --- BUSCAR TREINO ATUAL ---
// MELHORIA: Adicionado 'enfase_muscular' à busca
$sql = "SELECT id_treino, arquivo_treino, divisao_treino, dias_de_treino, nivel_de_treino, enfase_muscular FROM treino WHERE id_usuario = ? AND situacao = 'A' LIMIT 1";
$stmt = $conexao->prepare($sql);
if (!$stmt) {
    die("Erro no prepare: " . $conexao->error);
}
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$treino_atual = $result->fetch_assoc();
$stmt->close();

$treino_texto = "Nenhum treino encontrado.";
$divisao_atual = $treino_atual['divisao_treino'] ?? '';
$dias_atuais = $treino_atual['dias_de_treino'] ?? '';
$nivel_atual = $treino_atual['nivel_de_treino'] ?? '';
// MELHORIA: Captura a ênfase muscular atual
$enfase_atual = $treino_atual['enfase_muscular'] ?? '';

if ($treino_atual && isset($treino_atual['arquivo_treino'])) {
    $caminho = "../montagem_treino/treinos_salvos/" . $treino_atual['arquivo_treino'];
    $treino_texto = file_exists($caminho) ? file_get_contents($caminho) : "Arquivo não encontrado: $caminho";
}

$resposta_deepseek = "";

// --- QUANDO O USUÁRIO ENVIA O PROMPT DE ALTERAÇÃO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_alteracao'])) {
    $alteracao_usuario = trim($_POST['chatTreino']);
    
    // CORREÇÃO: Captura os inputs com nomes corretos
    $dias_digitados = trim($_POST['dias'] ?? '');
    $enfase_digitada = trim($_POST['enfase_muscular'] ?? ''); // <-- CORREÇÃO

    // Define os valores finais a serem usados, priorizando os digitados pelo usuário
    $dias_final = !empty($dias_digitados) ? intval($dias_digitados) : $dias_atuais;
    $enfase_final = !empty($enfase_digitada) ? $enfase_digitada : $enfase_atual; // <-- CORREÇÃO

    if (!empty($alteracao_usuario) && !empty($treino_texto) && !empty($divisao_atual) && !empty($dias_final) && !empty($nivel_atual)) {
        
        // Monta o prompt para a IA
        $prompt = <<<EOT
Você é um treinador profissional.
Aqui está um plano de treino base:
$treino_texto

Adapte esse treino com $dias_final dias de treino por semana, no nível "$nivel_atual".
EOT;
        
        // MELHORIA: Adiciona a ênfase muscular ao prompt, se existir
        if (!empty($enfase_final)) {
            $prompt .= "\nDê uma ênfase especial nos seguintes grupos musculares: $enfase_final.";
        }
        
        $prompt .= "\nRealize também as seguintes alterações pedidas pelo usuário: $alteracao_usuario";
        $prompt .= "\nGere o novo plano de treino com base em todas essas instruções.";


        $apiKey = ''; // Substitua com sua chave

        $dados = [
            "model" => "deepseek-chat",
            "messages" => [
                ["role" => "system", "content" => "Você é um treinador profissional."],
                ["role" => "user", "content" => $prompt]
            ]
        ];

        $ch = curl_init("https://api.deepseek.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));

        $resposta = curl_exec($ch);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($erro) {
            $resposta_deepseek = "Erro ao acessar a API: $erro";
        } else {
            $resposta_array = json_decode($resposta, true);
            $resposta_deepseek = $resposta_array['choices'][0]['message']['content'] ?? 'Sem resposta da API.';
            
            // Salva tudo na sessão para o próximo passo
            $_SESSION['novo_treino_gerado'] = $resposta_deepseek;
            $_SESSION['dias'] = $dias_final;
            $_SESSION['divisao'] = $divisao_atual;
            $_SESSION['nivel'] = $nivel_atual;
            $_SESSION['enfase_muscular'] = $enfase_final; // <-- CORREÇÃO
        }
    }
}

// --- QUANDO O USUÁRIO CLICA EM "SALVAR COMO NOVO TREINO" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_treino'])) {
    if (
        isset($_SESSION['novo_treino_gerado']) &&
        isset($_SESSION['dias']) &&
        isset($_SESSION['divisao']) &&
        isset($_SESSION['nivel']) &&
        isset($_SESSION['enfase_muscular']) // <-- CORREÇÃO
    ) {
        $novo_treino = $_SESSION['novo_treino_gerado'];
        $dias = intval($_SESSION['dias']);
        $divisao = $_SESSION['divisao'];
        $nivel = $_SESSION['nivel'];
        $enfase = $_SESSION['enfase_muscular']; // <-- CORREÇÃO

        // Cria a pasta de treinos se não existir
        $pasta = "../montagem_treino/treinos_salvos/";
        if (!file_exists($pasta)) {
            mkdir($pasta, 0777, true);
        }

        // Salva o treino gerado em um arquivo de texto
        $nome_arquivo = "treino_usuario_" . $usuario_id . "_" . date("Ymd_His") . ".txt";
        $caminho_arquivo = $pasta . $nome_arquivo;
        file_put_contents($caminho_arquivo, $novo_treino);

        // --- ATUALIZAÇÃO E INSERÇÃO NO BANCO DE DADOS ---

        // CORREÇÃO: Desativa outros treinos usando prepared statement
        $stmt_update = $conexao->prepare("UPDATE treino SET situacao = 'D' WHERE id_usuario = ?");
        $stmt_update->bind_param("i", $usuario_id);
        $stmt_update->execute();
        $stmt_update->close();

        // CORREÇÃO: Salva o novo treino como ativo com a query e bind_param corretos
        $sql_insert = "INSERT INTO treino (id_usuario, divisao_treino, dias_de_treino, nivel_de_treino, arquivo_treino, enfase_muscular, situacao) VALUES (?, ?, ?, ?, ?, ?, 'A')";
        $stmt_insert = $conexao->prepare($sql_insert);
        if (!$stmt_insert) {
            die("Erro no prepare do insert: " . $conexao->error);
        }
        // TIPO DOS PARÂMETROS: i=integer, s=string. A ordem deve bater com os '?' da query.
        $stmt_insert->bind_param("isisss", $usuario_id, $divisao, $dias, $nivel, $nome_arquivo, $enfase);
        $stmt_insert->execute();
        $stmt_insert->close();
        
        // Limpa a sessão e recarrega a página
        unset($_SESSION['novo_treino_gerado'], $_SESSION['dias'], $_SESSION['divisao'], $_SESSION['nivel'], $_SESSION['enfase_muscular']);
        header("Location: treino.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Treino Atual</title>
    <link rel="stylesheet" href="treino.css?=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header>
    <div class="logo">
        <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
    </div>
    <div class="site-name"><h1>Treino</h1></div>
    <nav class="nav-links">
        <a href="treinos_anteriores.php">Treinos Anteriores</a>
    </nav>
    <div class="logo">
        <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
    </div>
</header>

<div class="qlqr">
    <h1>Plano Atual:</h1>

    <div class="dieta"><?= nl2br(htmlspecialchars($treino_texto)) ?></div>

    <div class="info-dieta-atual">
        <p><strong>Divisão:</strong> <?= htmlspecialchars($divisao_atual) ?></p>
        <p><strong>Dias de Treino:</strong> <?= htmlspecialchars($dias_atuais) ?></p>
        <p><strong>Nível:</strong> <?= htmlspecialchars($nivel_atual) ?></p>
        <?php if (!empty($enfase_atual)): ?>
            <p><strong>Ênfase Muscular:</strong> <?= htmlspecialchars($enfase_atual) ?></p>
        <?php endif; ?>
    </div>

    <form method="post">
        <label for="chatTreino"><strong>Solicitar alteração:</strong></label>
        <textarea id="chatTreino" name="chatTreino" rows="4" placeholder="Descreva o que deseja alterar..." required></textarea>

        <br><br>
        <label for="dias"><strong>Deseja alterar os dias de treino?</strong></label><br>
        <input type="number" name="dias" id="dias" class="styled-input" placeholder="(Opcional, atual: <?= htmlspecialchars($dias_atuais) ?>)" min="1"><br>
        
        <label for="enfase_muscular"><strong>Deseja dar ênfase em quais músculos?</strong></label><br>
        <input type="text" name="enfase_muscular" id="enfase_muscular" class="styled-input" placeholder="(Opcional, ex: Peito e ombros)" value="<?= htmlspecialchars($enfase_atual) ?>">

        <div class="botoes">
            <button type="submit" name="enviar_alteracao">
                <i class="fas fa-paper-plane"></i> Gerar Alteração
            </button>
            <button class="outra" onclick="window.location.href='../selecao_treino/selecao_treino.php'" type="button">
                <i class="fas fa-sync-alt"></i> Começar outro treino
            </button>
        </div>
    </form>

    <?php if (!empty($resposta_deepseek)): ?>
        <h2>Novo Treino Gerado:</h2>
        <div id="respostaDeepSeek" class="dieta" style="margin-top: 20px;">
            <?= nl2br(htmlspecialchars($resposta_deepseek)) ?>
        </div>
        <form method="post" style="margin-top: 20px;">
            <button type="submit" name="salvar_treino" class="salvar">
                <i class="fas fa-save"></i> Salvar como novo treino atual
            </button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>