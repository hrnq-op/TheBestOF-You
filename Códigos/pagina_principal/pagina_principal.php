<?php
session_start();
include "../conexao.php";

$usuario_logado = false;
$nome_usuario = "";

if (isset($_SESSION['id_usuario'])) {
    $id = $_SESSION['id_usuario'];
    $sql = "SELECT nome FROM usuario WHERE id_usuario = '$id'";
    $resultado = mysqli_query($conn, $sql);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $linha = mysqli_fetch_assoc($resultado);
        $nome_usuario = $linha['nome'];
        $usuario_logado = true;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheBestOF-You</title>
    <link href="principal.css?v=2" rel="stylesheet">
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
                <?php if ($usuario_logado): ?>
                    <a href="dieta.php" class="auth-btn">Dieta</a>
                    <a href="treino.php" class="auth-btn">Treino</a>
                    <a href="evolucao.php" class="auth-btn">Evolução</a>
                <?php endif; ?>
            </div>
            <div class="auth-container">
                <?php if ($usuario_logado): ?>
                    <div class="user-box">
                        <span>Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
                        <a href="logout.php" class="auth-btn">Sair</a>
                    </div>
                <?php else: ?>
                    <a href="../login/login.php" class="auth-btn">Login</a>
                    <!-- Removido "Comece Agora" conforme solicitado -->
                <?php endif; ?>
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
                <a href="../login/login.php" class="btn-comecar">Comece Agora</a>
            </div>
        </section>
    </main>
    <div class="benefit-container">
    <h2>Benefícios para pacientes</h2>
        <ul class="benefits-list">
            <li>✔️ Plano alimentar na palma da mão</li>
            <li>✔️ Receitas</li>
            <li>✔️ Acompanhamento da sua evolução</li>
            <li>✔️ Sugestões de treino </li>
        </ul>
            
    </div>

    <footer>
        <p>&copy; 2025 TheBestOF-You. Todos os direitos reservados.</p>
    </footer>

</body>

</html>