<?php
session_start();
include "../conexao.php";

$usuario_logado = false;
$nome_usuario = "";

if (isset($_SESSION['id_usuario'])) {
    $id = $_SESSION['id_usuario'];
    $sql = "SELECT nome FROM usuario WHERE id_usuario = '$id'";
    $resultado = mysqli_query($conexao, $sql);

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <div class="logo">
            <img src="imagens/Logo.png" alt="Logo"> <!-- Logo do site -->
        </div>
        <div class="site-name">
            TheBestOF-You
        </div>
        <nav class="menu">
            <div class="auth-container">
                <a href="../login/login.php" class="auth-btn">Login</a>
            </div>
        </nav>

    </header>

    <main>
        <!-- Texto introdutório -->
        <section class="intro">
            <div class="intro-text">
                <h1>O melhor software de Nutrição e Treino para otimizar seus resultados</h1>

                <p>O <strong>TheBestOF-You</strong> é a plataforma ideal para quem quer alcançar seus objetivos de saúde e bem-estar de forma personalizada. Criamos planos de <strong>dieta</strong> e <strong>treinos de musculação</strong> exclusivos para você, levando em consideração suas necessidades e metas.</p>
                <p>Quer perder peso, ganhar massa muscular ou manter a forma? Nosso sistema inteligente vai ajudar a criar o plano perfeito para o seu perfil, com ajustes constantes para garantir que você continue evoluindo.</p>

                <div class="btn-container-bottom">
                    <a href="../login/login.php" class="btn-comecar">Comece Agora</a>
                </div>
            </div>

            <div class="intro-image">
                <img src="imagens/Imagem1.png" alt="Imagem" style="max-width: 100%; margin-left: 30px; margin-right: 60px; margin-bottom: 60px; height: 450px;">
            </div>
        </section>

    </main>

    <footer>
        <p>&copy; 2025 TheBestOF-You. Todos os direitos reservados.</p>
    </footer>

</body>

</html>