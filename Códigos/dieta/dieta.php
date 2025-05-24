<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dieta</title>
    <link rel="stylesheet" href="dieta.css?v=2">
</head>
<body>

<header>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo"> <!-- Logo esquerda -->
            </a>
        </div>
        <div class="site-name">
            Dieta
        </div>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo"> <!-- Logo direita -->
            </a>
        </div>
    </header>
<div class="tab-pane fade" id="dieta" role="tabpanel">
            <h4>Dieta Anterior</h4>
            <p>Aqui será carregada a dieta anterior do usuário com um chat interativo.</p>
            <div class="border p-3 bg-light rounded">
                <p><strong>Dieta:</strong> Frango, arroz, legumes...</p>
                <label for="chatDieta">Solicitar alteração:</label>
                <textarea id="chatDieta" class="form-control mb-2" rows="3"></textarea>
                <button class="btn btn-secondary">Enviar para DeepSeek API</button>
            </div>
        </div>

    
</body>
</html>