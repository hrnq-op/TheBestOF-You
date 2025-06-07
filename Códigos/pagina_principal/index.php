<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login/login.php");
    exit();
}

include('../conexao.php');
$id_usuario = $_SESSION['id_usuario'];

// Verifica se foi clicado em Dieta ou Treino
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'dieta':
            // Verifica se existe dieta cadastrada
           $sql_dieta = "SELECT id_dieta FROM dieta 
              WHERE id_usuario = ? 
              AND arquivo_dieta IS NOT NULL 
              AND arquivo_dieta != '' 
              ORDER BY id_dieta DESC 
              LIMIT 1";

            $stmt_dieta = $conexao->prepare($sql_dieta);
            $stmt_dieta->bind_param("i", $id_usuario);
            $stmt_dieta->execute();
            $resultado_dieta = $stmt_dieta->get_result();

            if ($resultado_dieta && $resultado_dieta->num_rows > 0) {
                header("Location: ../dieta/dieta.php");
            } else {
                header("Location: ../usuario/usuario.php"); // Página para criar nova dieta
            }
            exit();

        case 'treino':
            // Verifica se existe treino cadastrado
            $sql_treino = "SELECT id_treino FROM treino 
                           WHERE id_usuario = ? 
                           AND arquivo_treino IS NOT NULL 
                           AND arquivo_treino != '' 
                           ORDER BY id_treino DESC 
                           LIMIT 1";
            $stmt_treino = $conexao->prepare($sql_treino);
            $stmt_treino->bind_param("i", $id_usuario);
            $stmt_treino->execute();
            $resultado_treino = $stmt_treino->get_result();

            if ($resultado_treino && $resultado_treino->num_rows > 0) {
                header("Location: ../treino/treino.php");
            } else {
                header("Location: ../selecao_treino/selecao_treino.php");
            }
            exit();
    }
}

// Busca o nome do usuário
$sql_usuario = "SELECT nome FROM usuario WHERE id_usuario = ? LIMIT 1";
$stmt_usuario = $conexao->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$resultado_usuario = $stmt_usuario->get_result();

if ($resultado_usuario && $resultado_usuario->num_rows > 0) {
    $usuario = $resultado_usuario->fetch_assoc();
    $nome_usuario = $usuario['nome'];
} else {
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
            <a href="index.php?action=dieta">Dieta</a>
            <a href="index.php?action=treino">Treino</a>
            <a href="../evolucao/evolucao.php">Evolução</a>
        </nav>

        <div class="auth-container">
            <div class="user-box">
                <span>Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
                <a href="../login/logout.php" class="auth-btn">Sair</a>
            </div>
        </div>
    </header>

    <main>
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