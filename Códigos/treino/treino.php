<?php
session_start();
include '../conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login/login.php");
    exit();
}

$divisoes_sugeridas = [];
$mensagem = "";
$mostrar_divisoes = false;

// Lista de divisões + descrições COMPLETAS
$descricao_divisoes = [
    "Full Body" => "Treino de corpo inteiro em todas as sessões, trabalhando todos os grupos musculares principais em cada treino. Exercícios compostos como agachamento, supino e levantamento terra são a base. Ideal para iniciantes ou quem treina 2-3 vezes por semana.",

    "Upper/Lower" => "Divisão entre parte superior (Upper: peito, costas, ombros e braços) e inferior (Lower: quadríceps, posterior, glúteos e panturrilhas) do corpo. Permite maior volume por grupo muscular. Recomendado para intermediários.",

    "ABC" => "Divisão clássica em 3 dias:
    <br><strong>A - Peito/Tríceps</strong>: Supino, crucifixo, mergulho, tríceps testa
    <br><strong>B - Costas/Bíceps</strong>: Barra fixa, remada, puxada, rosca direta
    <br><strong>C - Pernas/Ombro</strong>: Agachamento, leg press, desenvolvimento, elevação lateral",

    "PPL" => "Divisão baseada em padrões de movimento:
    <br><strong>Push (Empurrar)</strong>: Peito, ombros e tríceps (supino, desenvolvimento, tríceps)
    <br><strong>Pull (Puxar)</strong>: Costas e bíceps (barra fixa, remada, rosca)
    <br><strong>Legs (Pernas)</strong>: Membros inferiores e core (agachamento, stiff, abdominal)
    Excelente para ganhos de força e hipertrofia.",

    "ABCD" => "Divisão em 4 dias com foco em grupos menores:
    <br><strong>A - Peito</strong>
    <br><strong>B - Costas</strong>
    <br><strong>C - Pernas</strong>
    <br><strong>D - Ombros/Braços</strong>
    Permite maior isolamento muscular.",

    "Bro Split" => "Divisão clássica de fisiculturismo com 1 grupo muscular por dia:
    <br>Seg: Peito
    <br>Ter: Costas
    <br>Qua: Pernas
    <br>Qui: Ombros
    <br>Sex: Braços
    <br>Permite alto volume por grupo muscular, ideal para avançados.",

    "Full Body 2x" => "Treino de corpo inteiro 2 vezes por semana, trabalhando todos os grupos musculares em cada sessão com exercícios compostos. Ideal para iniciantes ou quem tem pouco tempo.",

    "PPL 2x" => "Ciclo Push-Pull-Legs realizado duas vezes na semana (6 dias de treino). Permite alta frequência e volume para cada grupo muscular. Exemplo:
    <br>Seg: Push
    <br>Ter: Pull
    <br>Qua: Legs
    <br>Qui: Push
    <br>Sex: Pull
    <br>Sáb: Legs",

    "PPL + Upper/Lower" => "Combinação de divisões:
    <br>Seg: Push (Peito/Ombros/Tríceps)
    <br>Ter: Pull (Costas/Bíceps)
    <br>Qua: Lower (Pernas completas)
    <br>Qui: Upper (Superior completo)
    <br>Sex: Lower (Ênfase em posterior/glúteos)
    Excelente para equilíbrio muscular.",

    "Upper/Lower + Full Body" => "Combinação de treinos Upper/Lower e Full Body. Ideal para quem treina 4-5 vezes na semana. Exemplo:
    <br>Seg: Upper (Parte superior)
    <br>Ter: Lower (Parte inferior)
    <br>Qui: Full Body (Corpo inteiro)
    <br>Sex: Full Body (Corpo inteiro)
    Permite bom volume, recuperação e frequência de estímulo.",

    "PPL + Full Body" => "Combinação de Push, Pull, Legs e treinos de Corpo Inteiro. Indicado para quem busca estímulo frequente e recuperação adequada. Exemplo:
    <br>Seg: Push
    <br>Ter: Pull
    <br>Qua: Legs
    <br>Qui: Full Body",

    "PPL + Upper" => "Combinação de Push, Pull, Legs e treinos da parte de cima do corpo (Upper). Indicado para quem busca estímulo frequente e recuperação adequada. Exemplo:
    <br>Seg: Push
    <br>Ter: Pull
    <br>Qua: Legs
    <br>Qui: Upper",

    "ABC 2x" => "Treino ABC repetido duas vezes na semana. Para quem treina 6 vezes por semana e quer frequência alta. Exemplo:
    <br>Seg: A (Peito/Tríceps)
    <br>Ter: B (Costas/Bíceps)
    <br>Qua: C (Pernas/Ombro)
    <br>Qui: A
    <br>Sex: B
    <br>Sáb: C",

    "Upper/Lower 2x" => "Divisão Upper/Lower feita duas vezes na semana (4 dias). Excelente para intermediários que buscam evolução em força e hipertrofia. Exemplo:
    <br>Seg: Upper
    <br>Ter: Lower
    <br>Qui: Upper
    <br>Sex: Lower",

    "ABCDE" => "Divisão de 5 dias, cada um focado em um grupo muscular. Usada para alto volume de treino. Exemplo:
    <br>Seg: Peito
    <br>Ter: Costas
    <br>Qua: Pernas
    <br>Qui: Ombros
    <br>Sex: Braços
    Permite trabalhar cada grupo de forma isolada e intensa."
];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dias_treino = $_POST['dias_treino'];
    $nivel_treino = $_POST['nivel_treino'];
    $id_usuario = $_SESSION['id_usuario'];

    // Verifique se o valor de enfase está vindo corretamente
    $enfase = isset($_POST['enfase']) && !empty($_POST['enfase']) ? trim($_POST['enfase']) : null;

    if (isset($_POST['divisao_escolhida'])) {
        $divisao_escolhida = $_POST['divisao_escolhida'];

        // A query agora já deve tratar $enfase corretamente
        $sql = "INSERT INTO treino (id_usuario, divisao_treino, dias_de_treino, nivel_de_treino, enfase) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        // Use 's' para o parâmetro enfase (que pode ser NULL)
        $stmt->bind_param("isiss", $id_usuario, $divisao_escolhida, $dias_treino, $nivel_treino, $enfase);

        if ($stmt->execute()) {
            header("Location: ../montagem_treino/montagem_treino.php");
            exit();
        } else {
            $mensagem = "Erro ao salvar treino: " . $stmt->error;
        }
    } else {
        $divisoes_sugeridas = sugerirDivisoes($dias_treino, $nivel_treino);
        $mostrar_divisoes = true;
    }
}




function sugerirDivisoes($dias, $nivel)
{
    $sugestoes = [];

    switch ($nivel) {
        case "iniciante":
            switch ($dias) {
                case 2:
                    $sugestoes = ["Full Body 2x"];
                    break;
                case 3:
                    $sugestoes = ["Full Body", "ABC"];
                    break;
                case 4:
                    $sugestoes = ["Upper/Lower"];
                    break;
                case 5:
                    $sugestoes = ["Upper/Lower + Full Body", "ABC"];
                    break;
                case 6:
                    $sugestoes = ["ABC 2x", "Upper/Lower"];
                    break;
                case 7:
                    $sugestoes = ["Full Body", "Bro Split"];
                    break;
            }
            break;

        case "intermediario":
            switch ($dias) {
                case 2:
                    $sugestoes = ["Full Body"];
                    break;
                case 3:
                    $sugestoes = ["PPL", "Upper/Lower"];
                    break;
                case 4:
                    $sugestoes = ["Upper/Lower", "PPL + Full Body", "PPL + Upper"];
                    break;
                case 5:
                    $sugestoes = ["PPL + Upper/Lower", "ABCD"];
                    break;
                case 6:
                    $sugestoes = ["PPL 2x", "ABC 2x"];
                    break;
                case 7:
                    $sugestoes = ["Bro Split", "PPL + Upper/Lower"];
                    break;
            }
            break;

        case "avancado":
            switch ($dias) {
                case 2:
                    $sugestoes = ["Full Body"];
                    break;
                case 3:
                    $sugestoes = ["PPL"];
                    break;
                case 4:
                    $sugestoes = ["Upper/Lower 2x", "ABCD"];
                    break;
                case 5:
                    $sugestoes = ["ABCDE", "PPL + Upper/Lower"];
                    break;
                case 6:
                    $sugestoes = ["PPL 2x", "Bro Split"];
                    break;
                case 7:
                    $sugestoes = ["Bro Split", "ABCDE"];
                    break;
            }
            break;
    }

    return $sugestoes;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Configuração de Treino</title>
    <link href="treino.css?v=2" rel="stylesheet">
</head>

<body>

<header>
    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo"> <!-- Logo esquerda -->
        </a>
    </div>
    <div class="site-name">
        Treino
    </div>
    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo"> <!-- Logo direita -->
        </a>
    </div>
</header>

    <div class="container">
        <h1>Montagem do Seu Treino</h1>

        <div class="info-box">
            <h3>Sobre os níveis de treino:</h3>
            <p><strong>Iniciante:</strong> Até 1 ano de treino consistente</p>
            <p><strong>Intermediário:</strong> Entre 1 e 3 anos de treino consistente</p>
            <p><strong>Avançado:</strong> Mais de 3 anos de treino consistente</p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <?php if (!$mostrar_divisoes): ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="dias_treino">Quantos dias por semana você pode treinar?</label>
                    <select id="dias_treino" name="dias_treino" required>
                        <option value="">Selecione</option>
                        <?php for ($i = 2; $i <= 7; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> dias</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nivel_treino">Qual é o seu nível de treino?</label>
                    <select id="nivel_treino" name="nivel_treino" required>
                        <option value="">Selecione</option>
                        <option value="iniciante">Iniciante</option>
                        <option value="intermediario">Intermediário</option>
                        <option value="avancado">Avançado</option>
                    </select>
                </div>
                <label for="enfase">Deseja dar ênfase em algum grupamento muscular? (Separe por vírgula)</label><br>
                <input type="text" name="enfase" id="enfase" placeholder="Ex: Peito, Ombros, Posterior" style="width: 100%; margin-bottom: 20px;"><br>

                <button type="submit">Sugerir Divisão de Treino</button>
            </form>
        <?php endif; ?>

        <?php if ($mostrar_divisoes): ?>
            <h2>Divisões Sugeridas para <?= $dias_treino ?> dias (<?= ucfirst($nivel_treino) ?>)</h2>

            <?php foreach ($divisoes_sugeridas as $divisao):
                if (!isset($descricao_divisoes[$divisao])) continue; // pula se não tiver descrição
            ?>
                <div class="divisao-card">
                    <h3><?= $divisao ?></h3>
                    <p><?= $descricao_divisoes[$divisao] ?? 'Descrição detalhada desta divisão de treino.' ?></p>
                    <form method="post">
                        <input type="hidden" name="divisao_escolhida" value="<?= $divisao ?>">
                        <input type="hidden" name="dias_treino" value="<?= $dias_treino ?>">
                        <input type="hidden" name="nivel_treino" value="<?= $nivel_treino ?>">
                        <input type="hidden" name="enfase" value="<?= htmlspecialchars($enfase ?? '') ?>">
                        <button type="submit">Escolher esta divisão</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>