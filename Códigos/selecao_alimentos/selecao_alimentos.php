<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Escolha de Alimentos</title>
    <link rel="stylesheet" href="selecao_alimentos.css?v=2">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>

    <?php
    ob_start();
    include('../conexao.php');
    session_start();

    // Simula o ID do usuário (use sessão real no sistema final)
    $id_usuario = $_SESSION['id_usuario'] ?? null;

    $alimentosEscolhidos = [];
    $refeicoes = 0;

    // Função para obter o ID do alimento com base no nome e dieta
    function obterIdAlimento($conexao, $nome, $id_dieta)
    {
        global $id_alimento;
        $stmt = $conexao->prepare("SELECT id_alimentos FROM alimentos WHERE nome = ? AND id_dieta = ?");
        $stmt->bind_param("si", $nome, $id_dieta);
        $stmt->execute();
        $stmt->bind_result($id_alimento);
        $stmt->fetch();
        $stmt->close();
        return $id_alimento ?? null;
    }

    // Quando enviar alimentos e número de refeições
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["alimentos"], $_POST["refeicoes"]) && !isset($_POST["montar_dieta"])) {
        $alimentos = explode(",", $_POST["alimentos"]);
        $refeicoes = intval($_POST["refeicoes"]);

        foreach ($alimentos as $alimento) {
            $alimento = trim($alimento);
            if (!empty($alimento)) {
                $alimentosEscolhidos[] = $alimento;
            }
        }
    }

    // Quando clicar em "Avançar"
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["montar_dieta"])) {
        $alimentosEscolhidos = $_POST["alimentos"] ?? [];
        $refeicoes = intval($_POST["refeicoes"]);

        // 1. Pega o último id_dieta do usuário
        $stmt = $conexao->prepare("SELECT id_dieta FROM dieta WHERE id_usuario = ? ORDER BY id_dieta DESC LIMIT 1");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($id_dieta);
        $stmt->fetch();
        $stmt->close();

        if ($id_dieta) {
            // 2. Atualiza o número de refeições
            $stmt = $conexao->prepare("UPDATE dieta SET refeicoes = ? WHERE id_dieta = ?");
            $stmt->bind_param("ii", $refeicoes, $id_dieta);
            $stmt->execute();
            $stmt->close();

            // 3. Insere os alimentos com vínculo ao id_dieta
            foreach ($alimentosEscolhidos as $alimento) {
                $alimento = trim($alimento);
                if (!empty($alimento)) {
                    $id_alimento = obterIdAlimento($conexao, $alimento, $id_dieta);

                    if (!$id_alimento) {
                        $stmt = $conexao->prepare("INSERT INTO alimentos (nome, id_dieta) VALUES (?, ?)");
                        $stmt->bind_param("si", $alimento, $id_dieta);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        $conexao->close();

        // Redireciona para montagem da dieta
        header("Location: ../montagem_dieta/montagem_dieta.php");
        exit();
    }
    ?>

    <h1>Fale os alimentos que você gostaria de ter na sua dieta</h1>
    <form method="post">
        <label for="alimentos">Alimentos (separe por vírgula):</label>
        <input type="text" id="alimentos" name="alimentos" required> <br><br>

        <label for="refeicoes">Número de refeições por dia:</label>
        <input type="number" id="refeicoes" name="refeicoes" min="1" max="10" required> <br><br>

        <button type="submit">Adicionar</button>
    </form>

    <?php if (!empty($alimentosEscolhidos)): ?>
        <h2>Resumo dos Alimentos Escolhidos</h2>
        <table>
            <tr>
                <th>Alimentos</th>
            </tr>
            <?php foreach ($alimentosEscolhidos as $alimento): ?>
                <tr>
                    <td><?= htmlspecialchars($alimento) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2>Número de refeições escolhidas = <?= $refeicoes ?></h2>

        <form method="post">
            <?php foreach ($alimentosEscolhidos as $alimento): ?>
                <input type="hidden" name="alimentos[]" value="<?= htmlspecialchars($alimento) ?>">
            <?php endforeach; ?>
            <input type="hidden" name="refeicoes" value="<?= $refeicoes ?>">
            <button type="submit" name="montar_dieta">Avançar</button>
        </form>
    <?php endif; ?>

</body>

</html>