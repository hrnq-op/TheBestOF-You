<?php
session_start();
include "../conexao.php";

if (!isset($_SESSION['id_usuario'])) {
    die("Usuário não autenticado.");
}

$usuario_id = $_SESSION['id_usuario'];

// Buscar dieta atual no banco de dados
$sql = "SELECT id_dieta, arquivo_dieta, data_inicio, objetivo, refeicoes FROM dieta WHERE id_usuario = ? AND situacao = 'A' LIMIT 1";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$dieta_atual_db = $result->fetch_assoc();
$stmt->close();

$dieta_texto = "Nenhuma dieta encontrada.";
$objetivo_atual = $dieta_atual_db['objetivo'] ?? '';
$refeicoes_atuais = $dieta_atual_db['refeicoes'] ?? '';

if ($dieta_atual_db) {
    $caminho = "../montagem_dieta/" . $dieta_atual_db['arquivo_dieta'];
    $dieta_texto = file_exists($caminho) ? file_get_contents($caminho) : "Arquivo não encontrado: $caminho";
}

$resposta_deepseek = "";

// Quando o usuário envia o prompt de alteração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_alteracao'])) {
    $alteracao_usuario = trim($_POST['chatDieta']);
    $refeicoes_digitadas = trim($_POST['refeicoes'] ?? '');

    $refeicoes_final = !empty($refeicoes_digitadas) ? intval($refeicoes_digitadas) : $refeicoes_atuais;

    if (!empty($alteracao_usuario) && !empty($dieta_texto) && !empty($objetivo_atual) && !empty($refeicoes_final)) {
        // CÓDIGO MODIFICADO E MELHORADO
$prompt = <<<EOT
Você é um nutricionista profissional.
A dieta base para as alterações é a seguinte:
$dieta_texto

As instruções do usuário são: "$alteracao_usuario".

Por favor, gere uma nova dieta com exatamente $refeicoes_final refeições por dia, seguindo as instruções do usuário e fornecendo uma estimativa de calorias, proteínas, carboidratos e gorduras para cada refeição.

**IMPORTANTE:** A sua resposta final deve ser APENAS uma tabela no formato Markdown, sem nenhum texto introdutório ou conclusivo. A tabela deve ter as colunas "Refeição", "Descrição (Alimentos e Quantidades)", "Calorias", "Proteínas", "Carboidratos" e "Gorduras".

Exemplo de como a resposta deve ser formatada:
| Refeição                      | Descrição (Alimentos e Quantidades)      | Calorias | Proteínas | Carboidratos | Gorduras |
|-------------------------------|------------------------------------------|----------|-----------|--------------|----------|
| Café da Manhã (07:00)         | - 2 Ovos mexidos com tomate<br>- 1 Fatia de pão integral | 350 kcal | 20g       | 30g          | 15g      |
| Almoço (13:00)                | - 120g de Filé de frango grelhado<br>- 100g de Arroz integral | 450 kcal | 40g       | 50g          | 8g       |

Agora, gere a nova dieta completa seguindo estritamente este formato de tabela Markdown.
EOT;

        $apiKey = ''; // Substitua com sua chave

        $dados = [
            "model" => "deepseek-chat",
            "messages" => [
                ["role" => "system", "content" => "Você é um nutricionista profissional."],
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
            $_SESSION['nova_dieta_gerada'] = $resposta_deepseek;
            $_SESSION['refeicoes'] = $refeicoes_final;
            $_SESSION['objetivo'] = $objetivo_atual;
        }
    }
}

// Quando o usuário clica em "Salvar como nova dieta"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_dieta'])) {
    if (
        isset($_SESSION['nova_dieta_gerada']) &&
        isset($_SESSION['refeicoes']) &&
        isset($_SESSION['objetivo'])
    ) {
        $nova_dieta = $_SESSION['nova_dieta_gerada'];
        $refeicoes = intval($_SESSION['refeicoes']);
        $objetivo = $_SESSION['objetivo'];

        $pasta = "../montagem_dieta/dietas_salvas/";
        if (!file_exists($pasta)) {
            mkdir($pasta, 0777, true);
        }

        $nome_arquivo = "dieta_usuario_" . $usuario_id . "_" . date("Ymd_His") . ".txt";
        $caminho_arquivo = $pasta . $nome_arquivo;
        file_put_contents($caminho_arquivo, $nova_dieta);
        $arquivo_para_db = "dietas_salvas/" . $nome_arquivo;

        // Desativa outras dietas
        $conexao->query("UPDATE dieta SET situacao = 'D' WHERE id_usuario = $usuario_id");

        // Salva nova como ativa
        $sql_insert = "INSERT INTO dieta (id_usuario, arquivo_dieta, data_inicio, situacao, objetivo, refeicoes) VALUES (?, ?, NOW(), 'A', ?, ?)";
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bind_param("issi", $usuario_id, $arquivo_para_db, $objetivo, $refeicoes);
        $stmt_insert->execute();
        $stmt_insert->close();

        unset($_SESSION['nova_dieta_gerada'], $_SESSION['refeicoes'], $_SESSION['objetivo']);
        header("Location: dieta.php");
        exit;
    }
}

// Se a nova dieta da API estiver na sessão, use-a para exibir na tela
if (isset($_SESSION['nova_dieta_gerada'])) {
    $dieta_texto_exibida = $_SESSION['nova_dieta_gerada'];
} else {
    // Caso contrário, use a dieta atual do banco de dados
    $dieta_texto_exibida = $dieta_texto;
}

require_once '../libs/Parsedown.php';
$Parsedown = new Parsedown();

// Converter Markdown para HTML
$dieta_html = $Parsedown->text($dieta_texto_exibida);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dieta Atual</title>
    <link rel="stylesheet" href="dieta.css?=3"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo">
        </a>
    </div>

    <div class="site-name">Dieta</div>

    <nav class="nav-links">
        <a href="dietas_anteriores.php">Dietas Anteriores</a>
    </nav>

    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo">
        </a>
    </div>
</header>

<div id="loader-wrapper">
    <div class="loader"></div>
</div>

<div class="qlqr">
    <h1>Plano Atual:</h1>

    <div class="dieta"><?= $dieta_html ?></div>

    <div class="info-dieta-atual">
        <p><strong>Objetivo atual:</strong> <?= htmlspecialchars($objetivo_atual) ?></p>
        <p><strong>Refeições atuais:</strong> <?= htmlspecialchars($refeicoes_atuais) ?></p>
    </div>

    <form method="post" id="alteracaoForm"> <div class="form-group">
            <label for="chatDieta"><strong>Solicitar alteração:</strong></label>
            <div class="input-with-icon">
                <i class="fas fa-pen-fancy"></i>
                <textarea id="chatDieta" name="chatDieta" class="styled-input" rows="4" placeholder="Descreva o que deseja alterar..." required></textarea>
            </div>
        </div>

        <div class="form-group">
            <label for="refeicoes"><strong>Deseja alterar o número de refeições?</strong></label>
            <div class="input-with-icon">
                <i class="fas fa-utensils"></i>
                <input type="number" name="refeicoes" id="refeicoes" class="styled-input" placeholder="(Opcional)" min="1">
            </div>
        </div>
        
        <div class="botoes">
            <button type="submit" name="enviar_alteracao" id="btnEnviarAlteracao">
            <i class="fas fa-paper-plane"></i> Enviar
            </button>
            <button class="outra" onclick="window.location.href='../usuario/usuario.php'" type="button">
                <i class="fas fa-sync-alt"></i> Começar outra dieta
            </button>
            <?php
             $texto_para_pdf = !empty($_SESSION['nova_dieta_gerada']) ? $_SESSION['nova_dieta_gerada'] : $dieta_texto;
            if (!empty($texto_para_pdf)): ?>
                <a href="gerar_pdf_dieta.php?dieta=<?= urlencode($texto_para_pdf); ?>" 
                class="botao-pdf"> <i class="fas fa-file-pdf"></i> Gerar PDF da Dieta
                </a>
           <?php endif; ?>
        </div>
    </form>

    <?php if (!empty($resposta_deepseek)): ?>
        <h2>Nova Dieta Gerada:</h2>
        <div id="respostaDeepSeek" class="dieta">
            <?= $Parsedown->text($resposta_deepseek) ?>
        </div>
        <form method="post">
            <button type="submit" name="salvar_dieta" class="salvar">
                <i class="fas fa-save"></i> Salvar como nova dieta atual
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
    // Pega os elementos do formulário e do loader pelos seus IDs
    const form = document.getElementById('alteracaoForm');
    const loader = document.getElementById('loader-wrapper');

    // Adiciona um "ouvinte" que espera pelo evento de 'submit' do formulário
    form.addEventListener('submit', function() {
        // Quando o formulário for enviado (ou seja, quando o botão "Enviar" for clicado):
        
        // 1. Pega o campo de texto principal
        const chatInput = document.getElementById('chatDieta');

        // 2. Verifica se o campo de texto não está vazio
        if (chatInput.value.trim() !== '') {
            // 3. Se não estiver vazio, mostra o spinner
            loader.style.display = 'flex';
        }
    });
</script>

</body>
</html>
