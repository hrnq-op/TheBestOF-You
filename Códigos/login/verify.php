<?php
// Inicia a sessão para podermos logar o usuário
session_start();

// Inclui o arquivo de conexão com o banco de dados
// O caminho '../' significa que o arquivo está um nível acima
include('../conexao.php');

// Verifica se um token foi passado pela URL (via método GET)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    
    $token = $_GET['token'];

    // Prepara uma consulta segura para encontrar o usuário com o token fornecido
    // E que ainda não tenha o e-mail verificado
    $sql_find_user = "SELECT id_usuario FROM usuario WHERE verification_token = ? AND email_verified_at IS NULL LIMIT 1";
    $stmt = $conexao->prepare($sql_find_user);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verifica se encontrou exatamente um usuário com esse token
    if ($result->num_rows === 1) {
        
        // Usuário encontrado! Pega o ID dele.
        $user = $result->fetch_assoc();
        $id_usuario = $user['id_usuario'];

        // Agora, atualiza a tabela para marcar o e-mail como verificado
        // Usamos NOW() para pegar a data e hora exatas da verificação
        // e limpamos o token para que não possa ser reutilizado
        $sql_update_user = "UPDATE usuario SET email_verified_at = NOW(), verification_token = NULL WHERE id_usuario = ?";
        $update_stmt = $conexao->prepare($sql_update_user);
        $update_stmt->bind_param("i", $id_usuario);

        // Se a atualização for bem-sucedida...
        if ($update_stmt->execute()) {
            
            // Verificação concluída com sucesso!
            
            // 1. Cria a sessão para logar o usuário automaticamente
            $_SESSION['id_usuario'] = $id_usuario;
            
            // 2. Redireciona para a página principal, já logado.
            header("Location: ../pagina_principal/index.php");
            exit();

        } else {
            // Se houver um erro ao atualizar o banco de dados
            // Redireciona para o login com uma mensagem de erro genérica
            header("Location: login.php?status=verify_error");
            exit();
        }

    } else {
        // Se o token não foi encontrado no banco de dados (inválido ou já usado)
        header("Location: login.php?status=invalid_token");
        exit();
    }

} else {
    // Se a página for acessada sem um token na URL
    header("Location: login.php");
    exit();
}
?>