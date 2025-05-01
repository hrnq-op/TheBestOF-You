<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Escolha de Alimentos</title>
    <link rel="stylesheet" href="selecao_alimentos.css?=2">
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

    <header>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo">
            </a>
        </div>
        <div class="site-name">Dieta</div>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo">
            </a>
        </div>
    </header>

    <div class="qlqr">
        <h1>Fale os alimentos que você gostaria de ter na sua dieta</h1>
        <form method="post" class="form-caixa">
            <div class="animated-select">
                <label for="alimentos">Alimentos (separe por vírgula):</label>
                <input type="text" id="alimentos" name="alimentos" required>
            </div>

            <div class="animated-select">
                <label for="refeicoes">Número de refeições por dia:</label>
                <input type="number" id="refeicoes" name="refeicoes" min="1" max="10" required>
            </div>

            <div class="animated-select">
                <button type="submit">Adicionar</button>
            </div>
        </form>

        <?php
        ob_start();
        include('../conexao.php');
        session_start();

        $id_usuario = $_SESSION['id_usuario'] ?? null;
        $alimentosEscolhidos = [];
        $refeicoes = 0;

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

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["alimentos"], $_POST["refeicoes"]) && !isset($_POST["montar_dieta"])) {
            $alimentos = explode(",", $_POST["alimentos"]);
            $refeicoes = intval($_POST["refeicoes"]);

            foreach ($alimentos as $alimento) {
                $alimento = trim($alimento);
                if (!empty($alimento)) {
                    $alimentosEscolhidos[] = $alimento;
                }
            }

            if (!empty($alimentosEscolhidos)) {
                echo '<div style="max-width: 500px; margin: 30px auto; font-family: Inter, sans-serif;">';

                echo '<div style="
                    background-color: white;
                    padding: 1rem 1.5rem;
                    margin-bottom: 20px;
                    border-radius: 10px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    font-weight: 500;
                    font-size: 1.1rem;
                ">
                    <span style="color: #00b44b;">Número de refeições:</span> 
                    <span style="color: #000;">' . htmlspecialchars($refeicoes) . '</span>
                </div>';

                echo '<table style="
                    width: 100%;
                    border-collapse: collapse;
                    background-color: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                    animation: fadeIn 0.5s ease-out;
                ">';
                echo '<thead>';
                echo '<tr style="background-color: #00c853; color: white;">';
                echo '<th style="padding: 14px; text-align: left; font-size: 1rem; color: #00b44b;">Alimentos Escolhidos</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                // Tabela
                foreach ($alimentosEscolhidos as $alimento) {
                    echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                    echo '<td style="padding: 12px 14px; font-size: 0.95rem; color: #333;">' . htmlspecialchars($alimento) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';

                // Formulário só com botão (sem form-caixa)
                echo '<form method="post" style="margin-top: 20px; text-align: center;">';
                foreach ($alimentosEscolhidos as $alimento) {
                    echo '<input type="hidden" name="alimentos[]" value="' . htmlspecialchars($alimento) . '">';
                }
                echo '<input type="hidden" name="refeicoes" value="' . $refeicoes . '">';
                echo '<button type="submit" name="montar_dieta" class="btn-avc btn-pequeno">Avançar</button>';
                echo '</form>';
            }
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["montar_dieta"])) {
            $alimentosEscolhidos = $_POST["alimentos"] ?? [];
            $refeicoes = intval($_POST["refeicoes"]);

            $stmt = $conexao->prepare("SELECT id_dieta FROM dieta WHERE id_usuario = ? ORDER BY id_dieta DESC LIMIT 1");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->bind_result($id_dieta);
            $stmt->fetch();
            $stmt->close();

            if ($id_dieta) {
                $stmt = $conexao->prepare("UPDATE dieta SET refeicoes = ? WHERE id_dieta = ?");
                $stmt->bind_param("ii", $refeicoes, $id_dieta);
                $stmt->execute();
                $stmt->close();

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
            header("Location: ../montagem_dieta/montagem_dieta.php");
            exit();
        }
        ?>
    </div>

    <script src="script.js"></script>
</body>

</html>