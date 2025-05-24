<?php
// secao_evolucao.php
session_start();
// Simula dados do usuário (substituir por integração com banco de dados futuramente)
$user_progress = [
    ['data' => '2025-01', 'peso' => 80, 'gordura' => 20],
    ['data' => '2025-02', 'peso' => 78, 'gordura' => 19],
    ['data' => '2025-03', 'peso' => 76, 'gordura' => 18],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treino</title>
    <link rel="stylesheet" href="treino.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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

        <!-- Aba Treino Anterior -->
        <div class="tab-pane fade" id="treino" role="tabpanel">
            <h4>Treino Anterior</h4>
            <p>Aqui será carregado o treino anterior do usuário com um chat interativo.</p>
            <div class="border p-3 bg-light rounded">
                <p><strong>Treino:</strong> Segunda: Peito / Terça: Costas...</p>
                <label for="chatTreino">Solicitar alteração:</label>
                <textarea id="chatTreino" class="form-control mb-2" rows="3"></textarea>
                <button class="btn btn-secondary">Enviar para DeepSeek API</button>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
