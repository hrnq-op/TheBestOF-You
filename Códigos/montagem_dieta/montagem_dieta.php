<?php
session_start();
include('../conexao.php');
require_once '../libs/Parsedown.php'; // Movido para o topo para uso geral

if (!isset($_SESSION['id_usuario'])) {
    echo "<p>Erro: Usuário não está logado.</p>";
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// ETAPA 1: Se clicou em "Avançar" para SALVAR a dieta.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salvar_dieta'])) {
    $dieta_conteudo = $_POST['dieta_conteudo'] ?? '';
    $objetivo = $_POST['objetivo'] ?? '';
    $refeicoes = $_POST['refeicoes'] ?? 0;
    $id_dieta = $_POST['id_dieta'] ?? null;

    if (!empty($dieta_conteudo) && !empty($objetivo) && !empty($id_dieta)) {
        $nome_arquivo = "dieta_usuario_{$id_usuario}_" . time() . ".txt";
        $caminho_arquivo_fisico = "dietas_salvas/" . $nome_arquivo;
        $caminho_arquivo_db = "montagem_dieta/dietas_salvas/" . $nome_arquivo;

        if (!file_exists("dietas_salvas")) {
            mkdir("dietas_salvas", 0755, true);
        }
        file_put_contents($caminho_arquivo_fisico, $dieta_conteudo);

        $stmt = $conexao->prepare("UPDATE dieta SET arquivo_dieta = ?, situacao = 'A' WHERE id_dieta = ?");
        $stmt->bind_param("si", $caminho_arquivo_db, $id_dieta);
        $stmt->execute();
        $stmt->close();
        
        // Limpa a sessão para não mostrar a dieta antiga na próxima página
        unset($_SESSION['dieta_gerada_conteudo']);
        unset($_SESSION['dieta_gerada_contexto']);

        header("Location: ../pagina_principal/index.php");
        exit;
    }
}

// ETAPA 2: Se clicou em "Gerar outra dieta" para criar uma NOVA dieta
if (isset($_GET['action']) && $_GET['action'] === 'gerar_nova') {
    $stmt_data = $conexao->prepare("
        SELECT u.id_usuario, u.gasto_calorico_total, u.carbo_necessarias, u.prot_necessarias, u.gord_necessarias, 
               d.id_dieta, d.objetivo, d.refeicoes
        FROM dieta d INNER JOIN usuario u ON d.id_usuario = u.id_usuario
        WHERE u.id_usuario = ? ORDER BY d.id_dieta DESC LIMIT 1
    ");
    $stmt_data->bind_param("i", $id_usuario);
    $stmt_data->execute();
    $result_data = $stmt_data->get_result();
    $row = $result_data->fetch_assoc();
    $stmt_data->close();

    if ($row) {
        $id_dieta = $row['id_dieta'];
        $gasto_calorico = (float) $row['gasto_calorico_total'];
        $carbo_necessarias = (float) $row['carbo_necessarias'];
        $prot_necessarias = (float) $row['prot_necessarias'];
        $gord_necessarias = (float) $row['gord_necessarias'];
        $objetivo = strtolower($row['objetivo']);
        $refeicoes = (int) $row['refeicoes'];

        $alimentos = [];
        $result_alimentos = $conexao->prepare("SELECT nome FROM alimentos WHERE id_dieta = ?");
        $result_alimentos->bind_param("i", $id_dieta);
        $result_alimentos->execute();
        $result_alimentos_data = $result_alimentos->get_result();
        while ($row_alimento = $result_alimentos_data->fetch_assoc()) {
            $alimentos[] = $row_alimento['nome'];
        }
        $result_alimentos->close();

        $acao = ($objetivo === "cutting") ? "déficit calórico" : "superávit calórico";
        
        // CORREÇÃO: Prepara a string de alimentos ANTES de criar o prompt
        $string_alimentos = implode(", ", $alimentos);

        $prompt = <<<EOT
        Você é um nutricionista de elite. Sua tarefa é criar um plano alimentar estruturado e preciso, formatado exclusivamente como uma tabela Markdown.
        Crie uma dieta para um usuário com as seguintes especificações:
        - **Objetivo:** {$objetivo}
        - **Gasto Calórico Diário (base):** {$gasto_calorico} kcal
        - **Ação Metabólica:** Aplicar um {$acao} adequado.
        - **Meta de Macronutrientes:**
            - Carboidratos: ~{$carbo_necessarias}g
            - Proteínas: ~{$prot_necessarias}g
            - Gorduras: ~{$gord_necessarias}g
        - **Número de Refeições:** {$refeicoes}
        - **Alimentos Disponíveis:** Utilize principalmente os seguintes alimentos: {$string_alimentos}
        **FORMATO OBRIGATÓRIO DA RESPOSTA:**
        Sua resposta final deve ser APENAS uma tabela no formato Markdown, sem nenhum texto introdutório, conclusões ou resumos fora da tabela. A tabela deve ter exatamente as colunas "Refeição", "Descrição (Alimentos e Quantidades)", "Calorias", "Proteínas", "Carboidratos" e "Gorduras".
        **EXEMPLO DE FORMATAÇÃO:**
        | Refeição | Descrição (Alimentos e Quantidades) | Calorias | Proteínas | Carboidratos | Gorduras |
        | :--- | :--- | :--- | :--- | :--- | :--- |
        | Café da Manhã (07:00) | - 2 Ovos mexidos com tomate<br>- 1 Fatia de pão integral | 350 kcal | 20g | 30g | 15g |
        | Almoço (13:00) | - 120g de Filé de frango grelhado<br>- 100g de Arroz integral | 450 kcal | 40g | 50g | 8g |
        Agora, gere a nova dieta completa seguindo estritamente este formato.
        EOT;

        $apiKey = '';
        $url = "https://api.deepseek.com/v1/chat/completions";
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5, 'max_tokens' => 4096
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
            $_SESSION['dieta_gerada_conteudo'] = $resposta_api['choices'][0]['message']['content'];
        } else {
            $_SESSION['dieta_gerada_conteudo'] = "Não foi possível gerar a dieta. Tente novamente.";
        }
        
        $_SESSION['dieta_gerada_contexto'] = [
            'id_dieta' => $id_dieta,
            'gasto_calorico' => $gasto_calorico,
            'objetivo' => $objetivo,
            'refeicoes' => $refeicoes
        ];
        
        header('Location: montagem_dieta.php');
        exit;
    }
}

// ETAPA 3: Preparar dados para exibição (lendo da SESSÃO)
$dieta_html = "<p>Clique em 'Gerar outra dieta' para criar um plano alimentar personalizado com base nos alimentos que você selecionou.</p>";
$contexto = $_SESSION['dieta_gerada_contexto'] ?? null;

if (isset($_SESSION['dieta_gerada_conteudo'])) {
    $Parsedown = new Parsedown();
    $dieta_html = $Parsedown->text($_SESSION['dieta_gerada_conteudo']);
}
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
        <div class="logo"><a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a></div>
        <div class="site-name">Dieta Gerada</div>
        <div class="logo"><a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a></div>
    </header>
    <div class="qlqr">
        <h1>Dieta Personalizada</h1>

        <?php if ($contexto): ?>
            <p><strong>Gasto calórico:</strong> <?= htmlspecialchars($contexto['gasto_calorico']) ?> kcal</p>
            <p><strong>Objetivo:</strong> <?= ucfirst(htmlspecialchars($contexto['objetivo'])) ?></p>
            <p><strong>Refeições por dia:</strong> <?= $contexto['refeicoes'] ?></p>
        <?php endif; ?>

        <h2>Dieta sugerida:</h2>
        <div class="dieta"><?= $dieta_html ?></div>

        <div class="botes">
    <form method="get" id="formGerar">
        <input type="hidden" name="action" value="gerar_nova">
        <button type="submit" class="outra" id="btnGerarOutra"><i class="fas fa-sync-alt"></i> Gerar outra dieta</button>
    </form>

    <?php if (isset($_SESSION['dieta_gerada_conteudo']) && !str_contains($_SESSION['dieta_gerada_conteudo'], 'Não foi possível')): ?>
        <form method="post" id="formSalvar">
            <input type="hidden" name="salvar_dieta" value="1">
            <input type="hidden" name="id_dieta" value="<?= $contexto['id_dieta'] ?>">
            <input type="hidden" name="dieta_conteudo" value="<?= htmlspecialchars($_SESSION['dieta_gerada_conteudo'], ENT_QUOTES) ?>">
            <input type="hidden" name="objetivo" value="<?= htmlspecialchars($contexto['objetivo'], ENT_QUOTES) ?>">
            <input type="hidden" name="refeicoes" value="<?= $contexto['refeicoes'] ?>">
            <button type="submit" class="salvar" id="btnSalvar"><i class="fas fa-arrow-right"></i> Avançar</button>
        </form>
    <?php endif; ?>
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