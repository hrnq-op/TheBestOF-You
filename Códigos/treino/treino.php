<?php
session_start();
include "../conexao.php";
require_once '../libs/Parsedown.php';

if (!isset($_SESSION['id_usuario'])) {
    die("Usuário não autenticado.");
}

$usuario_id = $_SESSION['id_usuario'];

// --- BUSCAR TREINO ATUAL ---
$sql = "SELECT id_treino, arquivo_treino, divisao_treino, dias_de_treino, nivel_de_treino, enfase_muscular FROM treino WHERE id_usuario = ? AND situacao = 'A' LIMIT 1";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$treino_atual = $result->fetch_assoc();
$stmt->close();

$treino_texto = "Nenhum treino encontrado.";
$divisao_atual = $treino_atual['divisao_treino'] ?? '';
$dias_atuais = $treino_atual['dias_de_treino'] ?? '';
$nivel_atual = $treino_atual['nivel_de_treino'] ?? '';
$enfase_atual = $treino_atual['enfase_muscular'] ?? '';

if ($treino_atual && isset($treino_atual['arquivo_treino'])) {
    $caminho = "../montagem_treino/treinos_salvos/" . $treino_atual['arquivo_treino'];
    $treino_texto = file_exists($caminho) ? file_get_contents($caminho) : "Arquivo não encontrado: $caminho";
}

$resposta_deepseek = "";

// --- QUANDO O USUÁRIO ENVIA O PROMPT DE ALTERAÇÃO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_alteracao'])) {
    $alteracao_usuario = trim($_POST['chatTreino']);
    $dias_digitados = trim($_POST['dias'] ?? '');
    $enfase_digitada = trim($_POST['enfase_muscular'] ?? '');

    $dias_final = !empty($dias_digitados) ? intval($dias_digitados) : $dias_atuais;
    $enfase_final = !empty($enfase_digitada) ? $enfase_digitada : $enfase_atual;

    if (!empty($alteracao_usuario) && !empty($treino_texto) && !empty($divisao_atual) && !empty($dias_final) && !empty($nivel_atual)) {
        
        // Etapa 1: Buscar todos os nomes de exercícios do banco de dados
        $sql_nomes = "SELECT nome FROM exercicio ORDER BY nome ASC";
        $resultado_nomes = $conexao->query($sql_nomes);
        $lista_de_exercicios = [];
        while ($row = $resultado_nomes->fetch_assoc()) {
            $lista_de_exercicios[] = $row['nome'];
        }
        // Formata a lista para ser inserida no prompt
        $string_exercicios_formatada = "- " . implode("\n- ", $lista_de_exercicios);

        // Prompt "Nível Profissional" que inclui a lista de exercícios
        $prompt = <<<EOT
Você é um personal trainer de elite. Sua tarefa é criar um plano de treino estruturado, preciso e que utilize apenas exercícios pré-aprovados.

O treino base do usuário é:
$treino_texto

Siga TODAS as seguintes instruções para gerar o novo treino:
1.  **Dias de Treino:** O treino deve ter exatamente $dias_final dias.
2.  **Nível:** Adequado para um praticante de nível "$nivel_atual".
3.  **Ênfase Muscular:** Se houver, dê foco especial em: $enfase_final.
4.  **Alterações do Usuário:** Incorpore esta solicitação: "$alteracao_usuario".

**INSTRUÇÃO MAIS IMPORTANTE (OBRIGATÓRIO):**
Ao escolher os exercícios, você DEVE OBRIGATORIAMENTE usar apenas os nomes da "Lista de Exercícios Aprovados" abaixo. Use os nomes EXATAMENTE como aparecem na lista. Não abrevie, traduza, modifique ou invente nomes.

**Lista de Exercícios Aprovados:**
$string_exercicios_formatada

**FORMATO DA RESPOSTA (OBRIGATÓRIO):**
Sua resposta final deve ser APENAS em formato Markdown.
Para cada dia de treino, crie um subtítulo (ex: ### Dia 1: Peito e Tríceps) e, logo abaixo, uma tabela com as colunas "Exercício", "Séries", "Repetições" e "Execução". Na coluna "Execução", coloque apenas o texto "-".

**EXEMPLO DE FORMATAÇÃO:**
### Dia 1: Peito, Ombros e Tríceps
| Exercício | Séries | Repetições | Execução |
| :--- | :--- | :--- | :--- |
| Supino Reto com Barra | 4 | 8-12 | - |
| Elevação lateral com halteres | 3 | 10-12 | - |
| Tríceps testa (Skull Crushers) | 3 | 12-15 | - |

Agora, gere o novo plano de treino completo seguindo estritamente TODAS as regras acima.
EOT;

        $apiKey = ' '; // Substitua com sua chave
        
        $dados = [
            "model" => "deepseek-chat",
            "messages" => [
                ["role" => "system", "content" => "Você é um treinador profissional."],
                ["role" => "user", "content" => $prompt]
            ]
        ];
        
        $ch = curl_init("https://api.deepseek.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $apiKey"]);
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
            $_SESSION['novo_treino_gerado'] = $resposta_deepseek;
            $_SESSION['dias'] = $dias_final;
            $_SESSION['divisao'] = $divisao_atual;
            $_SESSION['nivel'] = $nivel_atual;
            $_SESSION['enfase_muscular'] = $enfase_final;
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

// --- QUANDO O USUÁRIO CLICA EM "SALVAR COMO NOVO TREINO" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_treino'])) {
    if (isset($_SESSION['novo_treino_gerado'], $_SESSION['dias'], $_SESSION['divisao'], $_SESSION['nivel'], $_SESSION['enfase_muscular'])) {
        $novo_treino = $_SESSION['novo_treino_gerado'];
        $dias = intval($_SESSION['dias']);
        $divisao = $_SESSION['divisao'];
        $nivel = $_SESSION['nivel'];
        $enfase = $_SESSION['enfase_muscular'];
        $pasta = "../montagem_treino/treinos_salvos/";
        if (!file_exists($pasta)) { mkdir($pasta, 0777, true); }
        $nome_arquivo = "treino_usuario_" . $usuario_id . "_" . date("Ymd_His") . ".txt";
        file_put_contents($pasta . $nome_arquivo, $novo_treino);

        $stmt_update = $conexao->prepare("UPDATE treino SET situacao = 'D' WHERE id_usuario = ?");
        $stmt_update->bind_param("i", $usuario_id);
        $stmt_update->execute();
        $stmt_update->close();

        $sql_insert = "INSERT INTO treino (id_usuario, divisao_treino, dias_de_treino, nivel_de_treino, arquivo_treino, enfase_muscular, situacao) VALUES (?, ?, ?, ?, ?, ?, 'A')";
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bind_param("isisss", $usuario_id, $divisao, $dias, $nivel, $nome_arquivo, $enfase);
        $stmt_insert->execute();
        $stmt_insert->close();
        
        unset($_SESSION['novo_treino_gerado'], $_SESSION['dias'], $_SESSION['divisao'], $_SESSION['nivel'], $_SESSION['enfase_muscular']);
        header("Location: treino.php");
        exit;
    }
}

/**
 * Função que processa o texto do treino e o transforma em uma tabela HTML
 * com links buscados do banco de dados.
 */
function formatarTreinoComLinks($texto_treino, $conexao) {
    if (empty(trim($texto_treino)) || $texto_treino === "Nenhum treino encontrado.") {
        $Parsedown = new Parsedown();
        return $Parsedown->text($texto_treino);
    }
    
    // Busca exata e rápida, pois a IA foi instruída a usar os nomes corretos.
    $sql_link = "SELECT link_video_execucao FROM exercicio WHERE nome = ? LIMIT 1";
    $stmt_link = $conexao->prepare($sql_link);

    if (!$stmt_link) {
        $Parsedown = new Parsedown();
        return "<p><strong>Aviso:</strong> A tabela 'exercicio' não foi encontrada. Exibindo em modo texto.</p>" . $Parsedown->text($texto_treino);
    }

    $html_output = "";
    $linhas = explode("\n", trim($texto_treino));
    $dentro_da_tabela = false;

    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if (strpos($linha, '###') === 0) {
            if ($dentro_da_tabela) { $html_output .= "</tbody></table>"; $dentro_da_tabela = false; }
            $html_output .= "<h3>" . trim(str_replace('###', '', $linha)) . "</h3>";
        } elseif (strpos($linha, '| Exercício |') === 0) {
            if ($dentro_da_tabela) { $html_output .= "</tbody></table>"; }
            $html_output .= "<table><thead><tr><th>Exercício</th><th>Séries</th><th>Repetições</th><th>Execução</th></tr></thead><tbody>";
            $dentro_da_tabela = true;
        } elseif (strpos($linha, '|') === 0 && strpos($linha, '---') === false) {
            $colunas = array_map('trim', explode('|', $linha));
            if (count($colunas) > 4) {
                $nome_exercicio_ia = $colunas[1];
                $series = $colunas[2];
                $repeticoes = $colunas[3];
                
                $stmt_link->bind_param("s", $nome_exercicio_ia);
                $stmt_link->execute();
                $resultado_link = $stmt_link->get_result();
                $link_execucao = $resultado_link->fetch_assoc()['link_video_execucao'] ?? null;
                
                $html_output .= "<tr>";
                $html_output .= "<td>" . htmlspecialchars($nome_exercicio_ia) . "</td>";
                $html_output .= "<td>" . htmlspecialchars($series) . "</td>";
                $html_output .= "<td>" . htmlspecialchars($repeticoes) . "</td>";
                $html_output .= "<td>";
                if ($link_execucao) {
                    $html_output .= "<a href='" . htmlspecialchars($link_execucao) . "' target='_blank' class='link-execucao'>Ver Vídeo</a>";
                } else {
                    $html_output .= "N/A";
                }
                $html_output .= "</td></tr>";
            }
        }
    }
    if ($dentro_da_tabela) { $html_output .= "</tbody></table>"; }
    $stmt_link->close();
    return $html_output;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Treino Atual</title>
    <link rel="stylesheet" href="treino.css?v=5"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header>
    <div class="logo"><a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a></div>
    <div class="site-name"><h1>Treino</h1></div>
    <nav class="nav-links"><a href="treinos_anteriores.php">Treinos Anteriores</a></nav>
    <div class="logo"><a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a></div>
</header>

<div class="qlqr">
    <h1>Plano Atual:</h1>
    <div class="dieta">
        <?= formatarTreinoComLinks($treino_texto, $conexao); ?>
    </div>

    <div class="info-dieta-atual">
        <p><strong>Divisão:</strong> <?= htmlspecialchars($divisao_atual) ?></p>
        <p><strong>Dias de Treino:</strong> <?= htmlspecialchars($dias_atuais) ?></p>
        <p><strong>Nível:</strong> <?= htmlspecialchars($nivel_atual) ?></p>
        <?php if (!empty($enfase_atual)): ?>
            <p><strong>Ênfase Muscular:</strong> <?= htmlspecialchars($enfase_atual) ?></p>
        <?php endif; ?>
    </div>

    <form method="post">

        <div class="form-group">
            <label for="chatTreino"><strong>Solicitar alteração:</strong></label>
            <div class="input-with-icon">
                <i class="fas fa-comment-dots"></i>
                <textarea id="chatTreino" name="chatTreino" class="styled-input" rows="4" placeholder="Descreva o que deseja alterar..." required></textarea>
            </div>
        </div>

        <div class="form-group">
            <label for="dias"><strong>Deseja alterar os dias de treino?</strong></label>
            <div class="input-with-icon">
                <i class="fas fa-calendar-alt"></i>
                <input type="number" name="dias" id="dias" class="styled-input" placeholder="(Opcional, atual: <?= htmlspecialchars($dias_atuais) ?>)" min="1">
            </div>
        </div>

        <div class="form-group">
            <label for="enfase_muscular"><strong>Deseja dar ênfase em quais músculos?</strong></label>
            <div class="input-with-icon">
                <i class="fas fa-dumbbell"></i>
                <input type="text" name="enfase_muscular" id="enfase_muscular" class="styled-input" placeholder="(Opcional, ex: Peito e ombros)">
            </div>
        </div>

        <div class="botoes">
            
            <button type="submit" name="enviar_alteracao"><i class="fas fa-paper-plane"></i> Gerar Alteração</button>
            <button class="outra" onclick="window.location.href='../selecao_treino/selecao_treino.php'" type="button"><i class="fas fa-sync-alt"></i> Começar outro treino</button>
            <a href="gerar_pdf_treino.php?treino=<?= urlencode($treino_texto) ?>&enfase=<?= urlencode($enfase_atual) ?>" target="_blank" class="botao-pdf"><i class="fas fa-file-pdf"></i> Gerar um PDF</a>
        </div>
    </form>

    <?php if (!empty($resposta_deepseek)): ?>
        <h2>Novo Treino Gerado:</h2>
        <div id="respostaDeepSeek" class="dieta" style="margin-top: 20px;">
            <?= formatarTreinoComLinks($resposta_deepseek, $conexao); ?>
        </div>
        <form method="post" style="margin-top: 20px;">
            <button type="submit" name="salvar_treino" class="salvar"><i class="fas fa-save"></i> Salvar como novo treino atual</button>
        </form>
    <?php endif; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const novaDietaDiv = document.getElementById("respostaDeepSeek");
        if (novaDietaDiv) {
            novaDietaDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>

</body>
</html>