<?php
session_start();
include "../conexao.php";
$usuario_id = $_SESSION['id_usuario']; // Você precisa já ter iniciado a sessão
$conexao->prepare("SELECT * FROM dieta WHERE id_usuario = :id ORDER BY data DESC");
$conexao->execute(['id' => $usuario_id]);
$dietas = $stmt->fetchAll();

$dieta_atual = $dietas[0] ?? null;
$dieta_texto = "Nenhuma dieta encontrada.";
if ($dieta_atual && file_exists($dieta_atual['caminho_arquivo'])) {
    $dieta_texto = nl2br(file_get_contents($dieta_atual['caminho_arquivo']));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Dieta</title>
    <link rel="stylesheet" href="dieta.css?v=2">
    <style>
        .chat-box {
            border: 1px solid #ccc;
            padding: 10px;
            background: #f9f9f9;
            margin-top: 15px;
        }

        #respostaDeepSeek {
            margin-top: 10px;
            white-space: pre-line;
        }
    </style>
</head>

<body>

    <header>
        <div class="logo">
            <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
        </div>
        <div class="site-name">Dieta</div>
        <div class="logo">
            <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
        </div>
    </header>

    <div class="container">
        <h2>Sua Dieta Atual</h2>
        <div class="chat-box">
            <p><strong>Dieta:</strong><br><?= $dieta_texto ?></p>

            <label for="chatDieta">Solicitar alteração:</label><br>
            <textarea id="chatDieta" rows="4" style="width:100%;"></textarea><br>
            <button onclick="enviarParaDeepSeek()">Enviar para DeepSeek</button>
            <div id="respostaDeepSeek"></div>
        </div>

        <h3>Dietas Anteriores</h3>
        <ul>
            <?php foreach ($dietas as $index => $d): ?>
                <?php if ($index === 0) continue; ?>
                <li>
                    <?= date('d/m/Y', strtotime($d['data'])) ?> -
                    <a href="<?= $d['caminho_arquivo'] ?>" target="_blank">Visualizar</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <br>
        <button onclick="window.location.href='../usuario/usuario.php'">Começar outra dieta</button>
    </div>

    <script>
        function enviarParaDeepSeek() {
            const texto = document.getElementById("chatDieta").value;
            if (!texto.trim()) {
                alert("Digite algo para enviar.");
                return;
            }

            // Simulação de resposta da API
            const respostaSimulada = "Sugestão de alteração: aumentar a quantidade de vegetais e reduzir o arroz.";

            // Aqui você implementaria a chamada real da API com fetch + backend intermediário em PHP.
            document.getElementById("respostaDeepSeek").innerText = "DeepSeek respondeu:\n" + respostaSimulada;
        }
    </script>

</body>

</html>