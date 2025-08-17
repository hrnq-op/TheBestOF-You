<?php
session_start();

// Verifica se o usuário está logado na sessão PHP
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login/login.php");
    exit();
}

include('../conexao.php'); // O caminho para conexao.php é relativo ao index.php

// --- NOVA VERIFICAÇÃO DE SESSÃO ATIVA NO BANCO DE DADOS ---
$id_usuario = $_SESSION['id_usuario'];
$session_id_atual = session_id(); // Obtém o ID da SESSÃO PHP atual

$stmt_check_active_session = $conexao->prepare("SELECT COUNT(*) FROM sessoes_ativas WHERE id_usuario = ? AND session_id = ?");
if (!$stmt_check_active_session) {
    error_log("Erro na preparação da consulta de sessão: " . $conexao->error);
    // Pode redirecionar ou mostrar um erro para o usuário
    session_destroy();
    header("Location: ../login/login.php?erro_db_sessao=true");
    exit();
}
$stmt_check_active_session->bind_param("is", $id_usuario, $session_id_atual);
$stmt_check_active_session->execute();
$stmt_check_active_session->bind_result($session_count);
$stmt_check_active_session->fetch();
$stmt_check_active_session->close();

if ($session_count == 0) {
    // A sessão não é mais ativa no DB (foi deslogada remotamente ou expirou/não registrada)
    session_destroy(); // Destroi a sessão atual no servidor
    // Redireciona com um parâmetro para o login.php saber o motivo
    header("Location: ../login/login.php?sessao_invalida=true"); 
    exit(); // É CRUCIAL usar exit() após o redirecionamento
}
// --- FIM DA NOVA VERIFICAÇÃO ---

// ... (Restante do seu código index.php, incluindo a busca de nome/foto e o HTML) ...

// Busca o nome do usuário e, se houver, a foto de perfil
$sql_usuario = "SELECT nome, foto_perfil FROM usuario WHERE id_usuario = ? LIMIT 1";
$stmt_usuario = $conexao->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$resultado_usuario = $stmt_usuario->get_result();

$foto_perfil_exibicao_index = '';
$nome_usuario = "Usuário";

if ($resultado_usuario && $resultado_usuario->num_rows > 0) {
    $usuario = $resultado_usuario->fetch_assoc();
    $nome_usuario = $usuario['nome'];
    $foto_perfil_salva_db = $usuario['foto_perfil'];

    if ($foto_perfil_salva_db) {
        $foto_perfil_exibicao_index = '../perfil/' . htmlspecialchars($foto_perfil_salva_db);
    } else {
        $foto_perfil_exibicao_index = '../perfil/uploads_perfil/foto_padrao.png'; 
    }
} else {
    $foto_perfil_exibicao_index = '../perfil/uploads_perfil/foto_padrao.png';
}

// Verifica se o arquivo da foto existe fisicamente no servidor para evitar quebras visuais
$base_path = realpath(__DIR__ . '/..'); // Caminho absoluto para a pasta pai (seu_projeto/)
// Constrói o caminho físico para file_exists, garantindo que seja absoluto ou relativo à raiz do projeto
$caminho_fisico_foto_server = str_replace('../', $base_path . '/', $foto_perfil_exibicao_index); // Ajuste cuidadoso aqui

$foto_perfil_existe = false;
if ($foto_perfil_exibicao_index && file_exists($caminho_fisico_foto_server)) {
    $foto_perfil_existe = true;
} else if (strpos($foto_perfil_exibicao_index, 'foto_padrao.png') !== false) {
    // Se for a foto padrão, verifica se o arquivo padrão existe
    if (file_exists($base_path . '/perfil/uploads_perfil/foto_padrao.png')) {
        $foto_perfil_existe = true;
    }
}

// O restante do HTML do index.php permanece o mesmo
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheBestOF-You</title>
    <link href="index.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header>
        <div class="logo">
            <img src="imagens/Logo.png" alt="Logo">
        </div>
        <div class="site-name">TheBestOF-You</div>

        <nav class="nav-links">
            <a href="../usuario/usuario.php?action=dieta">Dieta</a>
            <a href="../selecao_treino/selecao_treino.php?action=treino">Treino</a>
            <a href="../evolucao/evolucao.php">Evolução</a>
        </nav>

        <div class="auth-container">
            <a href="../perfil/perfil.php" class="perfil-icon">
                <?php if ($foto_perfil_existe): ?>
                    <img src="<?php echo $foto_perfil_exibicao_index; ?>" alt="Foto de Perfil">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
                <span class="tooltip-text">Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
            </a>
        </div>
    </header>

    <main>
        <section class="intro">
            <div class="intro-text">
                <h1><span class="tooltip-text">Bem-vindo, <?php echo htmlspecialchars($nome_usuario); ?> </span> à sua jornada personalizada de saúde e performance</h1>
                <p>Acesse seus planos personalizados de dieta, treino e acompanhe sua evolução em tempo real.</p>
                
                <ul>
                    <li>Planos de dieta feitos sob medida para você.</li>
                    <li>Treinos otimizados e adaptados ao seu ritmo.</li>
                    <li>Acompanhamento detalhado do seu progresso em tempo real.</li>
                    <li>Suporte completo em cada etapa da sua evolução.</li>
                </ul>
            </div>

            <div class="intro-image">
                <img src="imagens/imagem.png" alt="Imagem" style="max-width: 100%; height: auto;">
            </div>
        </section>
    </main>
    <footer>
        <p>&copy; 2025 TheBestOF-You. Todos os direitos reservados.</p>
    </footer>
</body>

</html>