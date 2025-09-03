<?php
session_start();
include('../conexao.php'); // Inclui a conexão com o banco de dados

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$mensagem_sucesso = "";
$mensagem_erro = "";
$session_id_atual = session_id(); // O ID da sessão atual do usuário, CRÍTICO para verificar se é a sessão logada

// --- Lógica para DESLOGAR UM DISPOSITIVO ---
if (isset($_POST['deslogar_dispositivo'])) {
    $sessao_a_deslogar_id = $_POST['sessao_id'];

    // Para segurança, verifique se a sessão a ser deslogada pertence ao usuário logado
    $stmt_get_session_info = $conexao->prepare("SELECT session_id FROM sessoes_ativas WHERE id_sessao = ? AND id_usuario = ?");
    $stmt_get_session_info->bind_param("ii", $sessao_a_deslogar_id, $id_usuario);
    $stmt_get_session_info->execute();
    $result_session_info = $stmt_get_session_info->get_result();
    $session_to_delete_row = $result_session_info->fetch_assoc();
    $stmt_get_session_info->close();

    if ($session_to_delete_row) { // Se a sessão existe e pertence ao usuário
        $session_id_to_delete = $session_to_delete_row['session_id'];

        $stmt_delete_session = $conexao->prepare("DELETE FROM sessoes_ativas WHERE id_sessao = ?");
        $stmt_delete_session->bind_param("i", $sessao_a_deslogar_id);
        
        if ($stmt_delete_session->execute()) {
            $mensagem_sucesso = "Dispositivo deslogado com sucesso!";
            
            // Se a sessão deslogada for a sessão ATUAL do usuário, ele deve ser expulso
            if ($session_id_to_delete == $session_id_atual) {
                session_destroy(); // Destroi a sessão atual
                header("Location: ../login/login.php?logout_remoto=true");
                exit();
            }
        } else {
            $mensagem_erro = "Erro ao deslogar o dispositivo.";
        }
        $stmt_delete_session->close();
    } else {
        $mensagem_erro = "Sessão inválida ou não pertence a você.";
    }
}

// --- Lógica para EXCLUIR CONTA ---
if (isset($_POST['excluir_conta'])) {
    // INÍCIO DA TRANSAÇÃO
    $conexao->begin_transaction();
    try {
        // 1. Excluir dados relacionados (dieta, treino, evolução, etc.)
        $stmt_dieta = $conexao->prepare("DELETE FROM dieta WHERE id_usuario = ?");
        $stmt_dieta->bind_param("i", $id_usuario);
        $stmt_dieta->execute();

        $stmt_treino = $conexao->prepare("DELETE FROM treino WHERE id_usuario = ?");
        $stmt_treino->bind_param("i", $id_usuario);
        $stmt_treino->execute();

        // 2. Excluir a foto de perfil do servidor, se existir
        $sql_foto = "SELECT foto_perfil FROM usuario WHERE id_usuario = ?";
        $stmt_foto = $conexao->prepare($sql_foto);
        $stmt_foto->bind_param("i", $id_usuario);
        $stmt_foto->execute();
        $resultado_foto = $stmt_foto->get_result();
        if ($resultado_foto->num_rows > 0) {
            $row_foto = $resultado_foto->fetch_assoc();
            $caminho_foto = $row_foto['foto_perfil'];
            if ($caminho_foto && file_exists($caminho_foto) && $caminho_foto != 'uploads_perfil/foto_padrao.png') { // Caminho para sua foto padrão
                unlink($caminho_foto); // Deleta o arquivo físico
            }
        }
        
        // 3. Excluir sessões ativas do usuário
        $stmt_sessoes_delete = $conexao->prepare("DELETE FROM sessoes_ativas WHERE id_usuario = ?");
        $stmt_sessoes_delete->bind_param("i", $id_usuario);
        $stmt_sessoes_delete->execute();

        // 4. Excluir o usuário da tabela 'usuario'
        $stmt_usuario_delete = $conexao->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt_usuario_delete->bind_param("i", $id_usuario);
        $stmt_usuario_delete->execute();

        // COMITAR A TRANSAÇÃO
        $conexao->commit();

        session_destroy(); // Destroi a sessão do usuário
        header("Location: ../login/login.php?excluido=true"); // Redireciona para a página de login
        exit();

    } catch (Exception $e) {
        // ROLLBACK EM CASO DE ERRO
        $conexao->rollback();
        $mensagem_erro = "Erro ao excluir a conta: " . $e->getMessage();
    }
}

// --- Lógica para ATUALIZAR PERFIL (Nome e Foto) ---
if (isset($_POST['atualizar_perfil'])) {
    $novo_nome = trim($_POST['nome']);
    $foto_temp_name = $_FILES['foto_perfil']['tmp_name'];
    $foto_name = $_FILES['foto_perfil']['name'];
    $foto_error = $_FILES['foto_perfil']['error'];
    $foto_size = $_FILES['foto_perfil']['size'];

    if (empty($novo_nome)) {
        $mensagem_erro = "O nome não pode estar vazio.";
    } else {
        $caminho_foto_db = null;
        if ($foto_error === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($foto_name, PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($extensao, $permitidos)) {
                $mensagem_erro = "Tipo de arquivo não permitido. Use JPG, JPEG, PNG ou GIF.";
            } elseif ($foto_size > $max_size) {
                $mensagem_erro = "O arquivo é muito grande. Máximo 5MB.";
            } else {
                $diretorio_uploads = 'uploads_perfil/'; // Caminho relativo a perfil.php
                if (!is_dir($diretorio_uploads)) {
                    mkdir($diretorio_uploads, 0777, true);
                }

                $novo_nome_foto = uniqid() . '.' . $extensao;
                $caminho_destino = $diretorio_uploads . $novo_nome_foto;

                if (move_uploaded_file($foto_temp_name, $caminho_destino)) {
                    $caminho_foto_db = $caminho_destino;

                    $sql_foto_antiga = "SELECT foto_perfil FROM usuario WHERE id_usuario = ?";
                    $stmt_foto_antiga = $conexao->prepare($sql_foto_antiga);
                    $stmt_foto_antiga->bind_param("i", $id_usuario);
                    $stmt_foto_antiga->execute();
                    $resultado_foto_antiga = $stmt_foto_antiga->get_result();
                    if ($resultado_foto_antiga->num_rows > 0) {
                        $row_foto_antiga = $resultado_foto_antiga->fetch_assoc();
                        $foto_antiga = $row_foto_antiga['foto_perfil'];
                        if ($foto_antiga && file_exists($foto_antiga) && $foto_antiga != 'uploads_perfil/foto_padrao.png') {
                            unlink($foto_antiga);
                        }
                    }
                } else {
                    $mensagem_erro = "Erro ao fazer upload da foto.";
                }
            }
        } else if ($foto_error !== UPLOAD_ERR_NO_FILE) {
            $mensagem_erro = "Erro no upload da foto: " . $foto_error;
        }

        if (empty($mensagem_erro)) {
            $conexao->begin_transaction();
            try {
                if ($caminho_foto_db) {
                    $sql_update = "UPDATE usuario SET nome = ?, foto_perfil = ? WHERE id_usuario = ?";
                    $stmt_update = $conexao->prepare($sql_update);
                    $stmt_update->bind_param("ssi", $novo_nome, $caminho_foto_db, $id_usuario);
                } else {
                    $sql_update = "UPDATE usuario SET nome = ? WHERE id_usuario = ?";
                    $stmt_update = $conexao->prepare($sql_update);
                    $stmt_update->bind_param("si", $novo_nome, $id_usuario);
                }

                if ($stmt_update->execute()) {
                    $conexao->commit();
                    $mensagem_sucesso = "Perfil atualizado com sucesso!";
                    $_SESSION['nome_usuario'] = $novo_nome;
                } else {
                    throw new Exception("Erro ao atualizar o perfil no banco de dados.");
                }
            } catch (Exception | mysqli_sql_exception $e) {
                $conexao->rollback();
                $mensagem_erro = "Erro ao atualizar o perfil: " . $e->getMessage();
            }
        }
    }
}

// --- Busca os dados atuais do usuário para exibir no formulário ---
$sql_usuario_atual = "SELECT nome, email, foto_perfil FROM usuario WHERE id_usuario = ?";
$stmt_usuario_atual = $conexao->prepare($sql_usuario_atual);
$stmt_usuario_atual->bind_param("i", $id_usuario);
$stmt_usuario_atual->execute();
$resultado_usuario_atual = $stmt_usuario_atual->get_result();

$usuario_dados = null;
if ($resultado_usuario_atual && $resultado_usuario_atual->num_rows > 0) {
    $usuario_dados = $resultado_usuario_atual->fetch_assoc();
} else {
    $mensagem_erro = "Dados do usuário não encontrados.";
}

$foto_perfil_exibicao = $usuario_dados['foto_perfil'] ? $usuario_dados['foto_perfil'] : 'uploads_perfil/foto_padrao.png';

// --- Busca as SESSÕES ATIVAS do usuário no banco de dados ---
$dispositivos_conectados = [];
$stmt_sessoes = $conexao->prepare("SELECT id_sessao, session_id, user_agent, ip_address, login_at FROM sessoes_ativas WHERE id_usuario = ? ORDER BY login_at DESC");
$stmt_sessoes->bind_param("i", $id_usuario);
$stmt_sessoes->execute();
$resultado_sessoes = $stmt_sessoes->get_result();

while ($row = $resultado_sessoes->fetch_assoc()) {
    $ativo_agora = ($row['session_id'] == $session_id_atual); // Verifica se é a sessão atual

    // Analisar user_agent para um nome mais amigável e ícone
    $nome_dispositivo = "Dispositivo Desconhecido";
    $icon_class = "fas fa-question-circle";

    if (stripos($row['user_agent'], 'Windows') !== false) {
        $nome_dispositivo = "Windows PC";
        $icon_class = "fab fa-windows";
    } elseif (stripos($row['user_agent'], 'Macintosh') !== false) {
        $nome_dispositivo = "Mac";
        $icon_class = "fab fa-apple";
    } elseif (stripos($row['user_agent'], 'Linux') !== false) {
        $nome_dispositivo = "Linux PC";
        $icon_class = "fab fa-linux";
    } elseif (stripos($row['user_agent'], 'Android') !== false) {
        $nome_dispositivo = "Android Smartphone";
        $icon_class = "fas fa-mobile-alt";
    } elseif (stripos($row['user_agent'], 'iPhone') !== false) {
        $nome_dispositivo = "iPhone";
        $icon_class = "fas fa-mobile-alt";
    }

    if (stripos($row['user_agent'], 'Chrome') !== false) {
        $nome_dispositivo .= " (Chrome)";
    } elseif (stripos($row['user_agent'], 'Firefox') !== false) {
        $nome_dispositivo .= " (Firefox)";
    } elseif (stripos($row['user_agent'], 'Safari') !== false) {
        $nome_dispositivo .= " (Safari)";
    } elseif (stripos($row['user_agent'], 'Edge') !== false) {
        $nome_dispositivo .= " (Edge)";
    }

    $login_time = new DateTime($row['login_at']);
    $formated_time = $login_time->format('d/m/Y H:i');

    $dispositivos_conectados[] = [
        'id_sessao' => $row['id_sessao'],
        'nome' => $nome_dispositivo,
        'local' => $row['ip_address'],
        'ultimo_acesso' => $formated_time,
        'ativo' => $ativo_agora,
        'icon' => $icon_class
    ];
}
$stmt_sessoes->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - TheBestOF-You</title>
    <link href="../index/index.css" rel="stylesheet">
    <link href="perfil.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header>
        <div class="logo">
            <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
        </div>
        <div class="site-name">
            <h1>Perfil</h1>
        </div>
        <div class="logo">
            <a href="../pagina_principal/index.php"><img src="imagens/Logo.png" alt="Logo"></a>
        </div>
    </header>

    <main class="perfil-main">
        <div class="perfil-container">
            <h2>Configurações do Perfil</h2>

            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="mensagem sucesso"><?php echo $mensagem_sucesso; ?></div>
            <?php endif; ?>
            <?php if (!empty($mensagem_erro)): ?>
                <div class="mensagem erro"><?php echo $mensagem_erro; ?></div>
            <?php endif; ?>

            <form action="perfil.php" method="POST" enctype="multipart/form-data" class="perfil-form">
                <div class="form-group foto-perfil-preview">
                    <label for="foto_perfil">Foto de Perfil:</label>
                    <img src="<?php echo htmlspecialchars($foto_perfil_exibicao); ?>" alt="Foto de Perfil Atual" class="current-profile-pic" id="profilePicPreview">
                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                    <small>Clique na foto ou aqui para alterar (máx. 5MB)</small>
                </div>

                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario_dados['nome']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario_dados['email']); ?>" disabled>
                    <small>O email não pode ser alterado por aqui.</small>
                </div>

                <button type="submit" name="atualizar_perfil" class="btn-atualizar">Atualizar Perfil</button>
            </form>

            <div class="perfil-actions-group">
                 <a href="../login/logout.php" class="btn-logout">Sair da Conta</a>
            </div>

            <div class="connected-devices-section">
                <h3>Dispositivos Conectados</h3>
                <p>Gerencie os dispositivos que estão logados na sua conta. Você pode deslogar sessões ativas remotamente.</p>
                <div class="device-list">
                    <?php if (empty($dispositivos_conectados)): ?>
                        <p class="no-devices">Nenhum dispositivo conectado encontrado.</p>
                    <?php else: ?>
                        <?php foreach ($dispositivos_conectados as $device): ?>
                            <div class="device-item <?php echo $device['ativo'] ? 'active-device' : ''; ?>">
                                <div class="device-info">
                                    <i class="<?php echo htmlspecialchars($device['icon']); ?> device-icon"></i>
                                    <div>
                                        <h4><?php echo htmlspecialchars($device['nome']); ?></h4>
                                        <p><?php echo htmlspecialchars($device['local']); ?> - Último acesso: <?php echo htmlspecialchars($device['ultimo_acesso']); ?></p>
                                    </div>
                                </div>
                                <?php if (!$device['ativo']): ?>
                                    <form action="perfil.php" method="POST" onsubmit="return confirm('Tem certeza que deseja deslogar este dispositivo?');" style="margin:0;">
                                        <input type="hidden" name="sessao_id" value="<?php echo htmlspecialchars($device['id_sessao']); ?>">
                                        <button type="submit" name="deslogar_dispositivo" class="btn-deslogar-dispositivo">Deslogar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="active-tag">Ativo agora</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="excluir-conta-section">
                <h3>Excluir Conta</h3>
                <p>Esta ação é irreversível e excluirá todos os seus dados. Por favor, prossiga com cautela.</p>
                <form action="perfil.php" method="POST" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja EXCLUIR sua conta? Todos os seus dados serão perdidos de forma permanente!');">
                    <button type="submit" name="excluir_conta" class="btn-excluir">Excluir Minha Conta</button>
                </form>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('foto_perfil').addEventListener('change', function(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('profilePicPreview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        });
        document.getElementById('profilePicPreview').addEventListener('click', function() {
            document.getElementById('foto_perfil').click();
        });
    </script>
</body>

</html>