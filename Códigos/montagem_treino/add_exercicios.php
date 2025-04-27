<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Exercícios Manualmente</title>
</head>

<body>
    <h1>Adicionar Exercícios Manualmente</h1>
    <form method="post">
        Nome <input type="text" name="nome" required><br>
        Link vídeo execução <input type="text" name="link" required><br>
        Grupos musculares envolvidos <input type="text" name="muscular" required><br>
        <input type="submit" name="enviar" value="Adicionar Exercício">
    </form>

    <?php

    include "../conexao.php";

    if (isset($_POST['enviar'])) {
        $nome = $_POST['nome'];
        $link = $_POST['link'];
        $muscular = $_POST['muscular'];

        // Preparar a consulta para inserção no banco de dados
        $stmt = $conexao->prepare("INSERT INTO exercicio (nome, link_video_execucao, grupo_muscular) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $link, $muscular);

        // Executar a consulta
        if ($stmt->execute()) {
            echo "<p>Exercício adicionado com sucesso!</p>";
        } else {
            echo "<p>Erro ao adicionar exercício: " . $stmt->error . "</p>";
        }

        // Fechar a conexão com o banco de dados
        $stmt->close();
        $conexao->close();
    }

    ?>
</body>

</html>