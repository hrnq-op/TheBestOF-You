<?php
session_start();

// --- INÍCIO: Bloco de Segurança (do Script 1) ---

// 1. Verifica se o usuário está logado na sessão PHP
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login/login.php");
    exit();
}

include('../conexao.php'); // O caminho para conexao.php é relativo ao index.php
$id_usuario = $_SESSION['id_usuario'];

// 2. Verificação de Sessão Ativa no Banco de Dados (Segurança Avançada)
$session_id_atual = session_id(); 

$stmt_check_active_session = $conexao->prepare("SELECT COUNT(*) FROM sessoes_ativas WHERE id_usuario = ? AND session_id = ?");
if (!$stmt_check_active_session) {
    error_log("Erro na preparação da consulta de sessão: " . $conexao->error);
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
    session_destroy(); 
    header("Location: ../login/login.php?sessao_invalida=true"); 
    exit();
}
// --- FIM: Bloco de Segurança ---


// --- INÍCIO: Lógica de Roteamento (do Script 2) ---
// Verifica se uma ação foi clicada antes de carregar o restante da página
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'dieta':
            // Verifica se existe dieta cadastrada
            $sql_dieta = "SELECT id_dieta FROM dieta WHERE id_usuario = ? AND arquivo_dieta IS NOT NULL AND arquivo_dieta != '' ORDER BY id_dieta DESC LIMIT 1";
            $stmt_dieta = $conexao->prepare($sql_dieta);
            $stmt_dieta->bind_param("i", $id_usuario);
            $stmt_dieta->execute();
            $resultado_dieta = $stmt_dieta->get_result();

            if ($resultado_dieta && $resultado_dieta->num_rows > 0) {
                header("Location: ../dieta/dieta.php");
            } else {
                // Redireciona para a página de criação se não houver dieta
                header("Location: ../usuario/usuario.php"); 
            }
            exit();

        case 'treino':
            // Verifica se existe treino cadastrado
            $sql_treino = "SELECT id_treino FROM treino WHERE id_usuario = ? AND arquivo_treino IS NOT NULL AND arquivo_treino != '' ORDER BY id_treino DESC LIMIT 1";
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
// --- FIM: Lógica de Roteamento ---


// --- INÍCIO: Busca de Dados para Exibição (Mescla dos Scripts 1 e 2) ---
// Busca o nome e a foto do usuário em uma única consulta
$sql_usuario = "SELECT nome, foto_perfil FROM usuario WHERE id_usuario = ? LIMIT 1";
$stmt_usuario = $conexao->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$resultado_usuario = $stmt_usuario->get_result();

$nome_usuario = "Usuário";
$foto_perfil_exibicao_index = '../perfil/uploads_perfil/foto_padrao.png'; // Caminho padrão

if ($resultado_usuario && $resultado_usuario->num_rows > 0) {
    $usuario = $resultado_usuario->fetch_assoc();
    $nome_usuario = $usuario['nome'];
    $foto_perfil_salva_db = $usuario['foto_perfil'];

    if ($foto_perfil_salva_db) {
        $foto_perfil_exibicao_index = '../perfil/' . htmlspecialchars($foto_perfil_salva_db);
    }
}

// Verifica se o arquivo da foto existe fisicamente no servidor
$base_path = realpath(__DIR__ . '/..');
$caminho_fisico_foto_server = str_replace('../', $base_path . '/', $foto_perfil_exibicao_index);

$foto_perfil_existe = false;
if (file_exists($caminho_fisico_foto_server)) {
    $foto_perfil_existe = true;
} else {
    // Se o arquivo do usuário não existir, volta para o padrão e verifica novamente
    $foto_perfil_exibicao_index = '../perfil/uploads_perfil/foto_padrao.png';
    if (file_exists($base_path . '/perfil/uploads_perfil/foto_padrao.png')) {
        $foto_perfil_existe = true;
    }
}
// --- FIM: Busca de Dados ---

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
            <a href="?action=dieta">Dieta</a>
            <a href="?action=treino">Treino</a>
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