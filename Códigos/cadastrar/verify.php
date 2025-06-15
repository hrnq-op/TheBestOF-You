<?php
// Inclui a conexão com o banco
require_once '../conexao.php'; // Substitua pelo seu arquivo de conexão

// Verifica se o token foi passado pela URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    
    $token = $_GET['token'];

    // --- Busca o token na tabela de pendentes ---
    $stmt_find = $conexao->prepare("SELECT id, nome, email, senha_hash FROM usuarios_pendentes WHERE verification_token = ?");
    if (!$stmt_find) {
        die("Erro ao preparar a busca: " . $conexao->error);
    }
    $stmt_find->bind_param("s", $token);
    $stmt_find->execute();
    $result = $stmt_find->get_result();

    // Se encontrou um usuário com o token
    if ($result->num_rows === 1) {
        
        $user_pending = $result->fetch_assoc();
        
        $nome = $user_pending['nome'];
        $email = $user_pending['email'];
        $senha_hash = $user_pending['senha_hash'];
        $pending_id = $user_pending['id'];

        // --- Insere o usuário na tabela principal `usuario` ---
        $stmt_insert = $conexao->prepare("INSERT INTO usuario (nome, email, senha) VALUES (?, ?, ?)");
        if (!$stmt_insert) {
            die("Erro ao preparar a inserção final: " . $conexao->error);
        }
        $stmt_insert->bind_param("sss", $nome, $email, $senha_hash);

        // Se a inserção na tabela principal funcionar
        if ($stmt_insert->execute()) {
            
            // --- Remove o registro da tabela de pendentes ---
            $stmt_delete = $conexao->prepare("DELETE FROM usuarios_pendentes WHERE id = ?");
            if (!$stmt_delete) {
                die("Erro ao preparar a exclusão: " . $conexao->error);
            }
            $stmt_delete->bind_param("i", $pending_id);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            // --- Sucesso ---
            echo "<h1>E-mail verificado com sucesso!</h1>";
            echo "<p>Sua conta foi ativada. Agora você já pode fazer o login.</p>";
            echo "<a href='../login/login.php' style='padding:10px 20px; background-color:#28a745; color:white; text-decoration:none; border-radius:5px;'>Ir para o Login</a>";

        } else {
            // Caso raro: não conseguiu inserir na tabela principal.
            // Poderia acontecer se, nesse meio tempo, outro usuário se cadastrou com o mesmo e-mail (pouco provável).
            echo "<h1>Erro na Ativação</h1>";
            echo "<p>Não foi possível ativar sua conta. O e-mail pode já ter sido registrado. Tente fazer o login ou se cadastrar novamente.</p>";
        }
        $stmt_insert->close();

    } else {
        // Token não encontrado ou já utilizado
        echo "<h1>Link de Verificação Inválido</h1>";
        echo "<p>Este link é inválido, expirou ou já foi utilizado. Por favor, tente se cadastrar novamente.</p>";
    }

    $stmt_find->close();
    $conexao->close();

} else {
    // Redireciona se não houver token
    echo "<h1>Erro</h1><p>Nenhum token de verificação fornecido.</p>";
}
?>
