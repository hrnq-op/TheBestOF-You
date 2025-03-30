<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calcular Metabolismo Basal</title>
</head>

<body>
    <h2>Preencha as informações abaixo</h2>

    <form method="post">
        <label>Peso Atual (kg):</label> <input type="number" name="peso" step="0.1" required><br><br>

        <label>Altura (cm):</label> <input type="number" name="altura" required><br><br>

        <label>Idade:</label> <input type="number" name="idade" required><br><br>

        <label>Sexo:</label>
        <select name="sexo" required>
            <option value="masculino">Masculino</option>
            <option value="feminino">Feminino</option>
        </select><br><br>

        <label>Protocolo:</label>
        <select name="protocolo" required>
            <option value="harris">Harris-Benedict (Geral, populações variadas)</option>
            <option value="mifflin">Mifflin-St Jeor (Mais precisa para indivíduos comuns)</option>
            <option value="cunningham">Cunningham (Melhor para atletas e fisiculturistas)</option>
            <option value="owen">Owen (Estimativa rápida, menos precisa)</option>
        </select><br><br>

        <label>Nível de Atividade Física:</label>
        <select name="nivel_atv_fisica" required>
            <option value="1.2">Sedentário (pouca ou nenhuma atividade física)</option>
            <option value="1.375">Leve (1 a 3 dias por semana de exercício leve)</option>
            <option value="1.55">Moderado (3 a 5 dias por semana de treino moderado)</option>
            <option value="1.725">Ativo (treino intenso 6 a 7 dias por semana)</option>
            <option value="1.9">Muito Ativo (atletas ou trabalho físico pesado)</option>
        </select><br><br>

        <label>Objetivo:</label>
        <select name="objetivo" required>
            <option value="cutting">Cutting (definição, déficit calórico)</option>
            <option value="bulking">Bulking (ganho de massa, superávit calórico)</option>
            <option value="manutencao">Manutenção (manter o peso atual)</option>
        </select><br><br>

        <button type="submit" name="calcular">Calcular</button>
    </form>

    <?php
    include "conexao.php";

    if (isset($_POST['calcular'])) {

        $peso = $_POST['peso'];
        $altura = $_POST['altura'];
        $idade = $_POST['idade'];
        $sexo = $_POST['sexo'];
        $protocolo = $_POST['protocolo'];
        $nivel_atv = $_POST['nivel_atv_fisica'];
        $objetivo = $_POST['objetivo'];

        if (!$peso || !$altura || !$idade) {
            echo "<h3>Erro: Preencha todos os campos corretamente.</h3>";
            exit;
        }

        switch ($protocolo) {
            case 'harris':
                $mb = ($sexo == 'masculino')
                    ? 88.36 + (13.4 * $peso) + (4.8 * $altura) - (5.7 * $idade)
                    : 447.6 + (9.2 * $peso) + (3.1 * $altura) - (4.3 * $idade);
                break;
            case 'mifflin':
                $mb = ($sexo == 'masculino')
                    ? (10 * $peso) + (6.25 * $altura) - (5 * $idade) + 5
                    : (10 * $peso) + (6.25 * $altura) - (5 * $idade) - 161;
                break;
            case 'cunningham':
                $mb = 500 + (22 * $peso * 0.75); // Aproximando a massa magra
                break;
            case 'owen':
                $mb = ($sexo == 'masculino')
                    ? 879 + (10.2 * $peso)
                    : 795 + (7.18 * $peso);
                break;
        }

        $tmb_total = $mb * $nivel_atv;

        echo "<h3>Seu metabolismo basal é: " . round($mb, 2) . " kcal/dia</h3>";
        echo "<h3>Seu gasto calórico total (TMB + nível de atividade) é: " . round($tmb_total, 2) . " kcal/dia</h3>";

        echo "<form method='post'>
            <input type='hidden' name='peso' value='$peso'>
            <input type='hidden' name='altura' value='$altura'>
            <input type='hidden' name='idade' value='$idade'>
            <input type='hidden' name='sexo' value='$sexo'>
            <input type='hidden' name='protocolo' value='$protocolo'>
            <input type='hidden' name='nivel_atv_fisica' value='$nivel_atv'>
            <input type='hidden' name='objetivo' value='$objetivo'>
            <input type='hidden' name='mb' value='$mb'>
            <input type='hidden' name='tmb_total' value='$tmb_total'>
            <button type='submit' name='avancar'>Avançar</button>
        </form>";
    }

    if (isset($_POST['avancar'])) {
        $peso = $_POST['peso'];
        $altura = $_POST['altura'];
        $idade = $_POST['idade'];
        $sexo = $_POST['sexo'];
        $protocolo = $_POST['protocolo'];
        $nivel_atv = $_POST['nivel_atv_fisica'];
        $objetivo = $_POST['objetivo'];
        $mb = $_POST['mb'];
        $tmb_total = $_POST['tmb_total'];

        if (!$conexao) {
            die("<h3>Erro: Falha na conexão com o banco de dados.</h3>");
        }

        $sql = "INSERT INTO usuario (peso, altura, idade, sexo, protocolo, nivel_atv_fisica, objetivo, metabolismo_basal, gasto_calorico_total)
                VALUES ('$peso', '$altura', '$idade', '$sexo', '$protocolo', '$nivel_atv', '$objetivo', '$mb', '$tmb_total')";

        if (mysqli_query($conexao, $sql)) {
            echo "<p>Conta cadastrada com sucesso!</p>";
        } else {
            echo "<p>Erro ao cadastrar conta: " . mysqli_error($conexao) . "</p>";
        }
    }
    ?>
</body>

</html>