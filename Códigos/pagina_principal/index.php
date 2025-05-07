<?php
session_start(); // Inicia a sessão

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: ../login/login.php");
    exit();
}

// Conecta ao banco de dados para buscar o nome do usuário
include('../conexao.php');
$id_usuario = $_SESSION['id_usuario']; // Obtém o ID do usuário da sessão
$sql = "SELECT nome FROM usuario WHERE id_usuario = '$id_usuario' LIMIT 1";
$resultado = $conexao->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    // Recupera o nome do usuário
    $usuario = $resultado->fetch_assoc();
    $nome_usuario = $usuario['nome'];
} else {
    // Caso o usuário não seja encontrado
    $nome_usuario = "Usuário não encontrado";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheBestOF-You</title>
    <link href="index.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <div class="logo">
            <img src="imagens/Logo.png" alt="Logo">
        </div>
        <div class="site-name">TheBestOF-You</div>

        <nav class="nav-links">
            <a href="../usuario/usuario.php">Dieta</a>
            <a href="../treino/treino.php">Treino</a>
            <a href="evolucao.php">Evolução</a>
        </nav>

        <div class="auth-container">
            <div class="user-box">
                <span>Bem-vindo, Henrique!</span>
                <a href="../login/logout.php" class="auth-btn">Sair</a>
            </div>
        </div>

        </div>
    </header>


    <main>
        <!-- Texto introdutório -->
        <section class="intro">
            <div class="intro-text">
            <h1>Bem-vindo à sua jornada personalizada de saúde e performance</h1>

                <p>Agora que você está logado, já pode acessar seus planos personalizados de <strong>dieta</strong>, <strong>treino</strong> e acompanhar sua <strong>evolução</strong> em tempo real.</p>
                <p>Estamos aqui para te ajudar em cada passo da sua jornada. Aproveite todas as funcionalidades disponíveis, atualize seu progresso e mantenha o foco nas suas metas. Vamos juntos conquistar o melhor de você!</p>

            </div>

            <div class="intro-image">
                <img src="imagens/imagem.png" alt="Imagem" style="max-width: 100%; margin-left: 50px; height: auto;">
            </div>
        </section>

    </main>
    <footer>
        <p>&copy; 2025 TheBestOF-You. Todos os direitos reservados.</p>
    </footer>

</body>

</html>