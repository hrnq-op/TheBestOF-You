<?php
session_start(); // Inicia a sessão

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: ../login/login.php");
    exit();
}

// Conecta ao banco de dados para buscar o nome do usuário
include('../conexao.php');
$id_usuario = $_SESSION['usuario_id']; // Obtém o ID do usuário da sessão
$sql = "SELECT nome FROM usuario WHERE id_usuario = '$id_usuario' LIMIT 1";
$resultado = $conexao->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    // Recupera o nome do usuário
    $usuario = $resultado->fetch_assoc();
    $nome_usuario = $usuario['nome'];
} else {
    // Caso o usuário não seja encontrado (isso não deveria acontecer se a sessão estiver correta)
    $nome_usuario = "Usuário não encontrado";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Principal - The Best Of-YOU</title>
    <link href="index.css?v=2" rel="stylesheet">
</head>

<body>

    <header>
        <div class="logo">
            <img src="imagens/logo.png" alt="Logo"> <!-- Logo do site -->
        </div>
        <div class="site-name">
            TheBestOF-You
        </div>
        <nav class="menu">
            <div class="nav-links">
                <!-- Alteração aqui para redirecionar para a página usuario.php na pasta usuario -->
                <a href="../usuario/usuario.php" class="auth-btn">Dieta</a>
                <a href="../treino/treino.php" class="auth-btn">Treino</a>
                <a href="evolucao.php" class="auth-btn">Evolução</a>
            </div>
            <div class="auth-container">
                <!-- Mostrar nome do usuário e opção de logout -->
                <span class="user-box">Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
                <a href="../login/logout.php" class="auth-btn">Sair</a>
            </div>
        </nav>
    </header>

    <main>
        <!-- Texto introdutório -->
        <section class="intro">
            <h1>Bem-vindo ao TheBestOF-You!</h1>
            <p>O <strong>TheBestOF-You</strong> é a plataforma ideal para quem quer alcançar seus objetivos de saúde e bem-estar de forma personalizada. Criamos planos de <strong>dieta</strong> e <strong>treinos</strong> exclusivos para você, levando em consideração suas necessidades e metas.</p>

            <p>Quer perder peso, ganhar massa muscular ou manter a forma? Nosso sistema inteligente vai ajudar a criar o plano perfeito para o seu perfil, com ajustes constantes para garantir que você continue evoluindo.</p>

            <p>Com o <strong>TheBestOF-You</strong>, você tem:</p>
            <ul>
                <li><strong>Dieta personalizada</strong>: Alimentos e porções ajustados às suas preferências e objetivos.</li>
                <li><strong>Treinos sob medida</strong>: Exercícios e planos de treino que se adaptam ao seu corpo e nível de atividade.</li>
                <li><strong>Acompanhamento da evolução</strong>: Monitore seu progresso e veja como você está alcançando suas metas.</li>
            </ul>

            <p>Transforme seus objetivos em conquistas com o <strong>TheBestOF-You</strong>!</p>

            <!-- Botão Comece Agora na parte inferior -->
            <div class="btn-container-bottom">
                <a href="../usuario/usuario.php" class="btn-comecar">Comece Agora</a>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 TheBestOF-You. Todos os direitos reservados.</p>
    </footer>

</body>

</html>