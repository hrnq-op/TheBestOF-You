<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coleta Informações</title>
    <link rel="stylesheet" href="usuario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
</head>
<header>
    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo"> <!-- Logo esquerda -->
        </a>
    </div>
    <div class="site-name">
        Dieta
    </div>
    <div class="logo">
        <a href="../pagina_principal/index.php">
            <img src="imagens/Logo.png" alt="Logo"> <!-- Logo direita -->
        </a>
    </div>
</header>

<body>
    <h2>Preencha as informações abaixo:</h2>


    <form method="post" id="form-calc" class="form-usu">

        <div class="input-group">
            <i class="fas fa-weight icon"></i>
            <label>Peso Atual (kg):</label>
        </div>
        <input type="number" name="peso" step="0.1" class="animated-select" required>

        <div class="input-group">
            <i class="fas fa-ruler-vertical icon"></i>
            <label>Altura (cm):</label>
        </div>
        <input type="number" name="altura" class="animated-select" required>

        <div class="input-group">
            <i class="fas fa-calendar-alt icon"></i>
            <label>Idade:</label>
        </div>
        <input type="number" name="idade" class="animated-select" required>

        <div class="input-group">
            <i class="fas fa-venus-mars icon"></i>
            <label>Sexo:</label>
        </div>
        <select name="sexo" class="animated-select" required>
            <option value="masculino">Masculino</option>
            <option value="feminino">Feminino</option>
        </select>

        <div class="input-group">
            <i class="fas fa-book icon"></i>
            <label>Protocolo:</label>
        </div>
        <select name="protocolo" class="animated-select" required>
            <option value="harris">Harris-Benedict (Geral, populações variadas)</option>
            <option value="mifflin">Mifflin-St Jeor (Mais precisa para indivíduos comuns)</option>
            <option value="cunningham">Cunningham (Melhor para atletas e fisiculturistas)</option>
            <option value="owen">Owen (Estimativa rápida, menos precisa)</option>
        </select>

        <div class="input-group">
            <i class="fas fa-running icon"></i>
            <label>Nível de Atividade Física:</label>
        </div>
        <select name="nivel_atv_fisica" class="animated-select" required>
            <option value="1.2">Sedentário (pouca ou nenhuma atividade física)</option>
            <option value="1.375">Leve (1 a 3 dias por semana de exercício leve)</option>
            <option value="1.55">Moderado (3 a 5 dias por semana de treino moderado)</option>
            <option value="1.725">Ativo (treino intenso 6 a 7 dias por semana)</option>
            <option value="1.9">Muito Ativo (atletas ou trabalho físico pesado)</option>
        </select>

        <div class="input-group">
            <i class="fas fa-bullseye icon"></i>
            <label>Objetivo:</label>
        </div>
        <select name="objetivo" class="animated-select" required>
            <option value="cutting">Cutting</option>
            <option value="bulking">Bulking</option>
            <option value="manutencao">Manutenção</option>
        </select>

        <div class="button_container">
            <button type="submit" name="calcular">
                <i class="icon-button fas fa-calculator"></i> Calcular
            </button>

        </div>

    </form>


    <?php
    session_start();

    // Verificar se o usuário está logado
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: ../login/login.php");
        exit();
    }
    if (isset($_SESSION['mensagem'])) {
        echo '<div class="php-message">' . $_SESSION['mensagem'] . '</div>';
        unset($_SESSION['mensagem']);
    }

    include('../conexao.php');
    $id_usuario = $_SESSION['id_usuario']; // Obtém o ID do usuário da sessão
    $sql = "SELECT nome FROM usuario WHERE id_usuario = '$id_usuario' LIMIT 1";
    $resultado = $conexao->query($sql);

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

        echo "
        <div class='php-message'>
            <strong>Seu metabolismo basal é:</strong> " . round($mb, 2) . " kcal/dia<br>
            <strong>Seu gasto calórico total (TMB + nível de atividade) é:</strong> " . round($tmb_total, 2) . " kcal/dia
        </div>";


        echo "
        
        <form method='post' class='avancar'>
        <input type='hidden' name='peso' value='$peso'>
        <input type='hidden' name='altura' value='$altura'>
        <input type='hidden' name='idade' value='$idade'>
        <input type='hidden' name='sexo' value='$sexo'>
        <input type='hidden' name='protocolo' value='$protocolo'>
        <input type='hidden' name='nivel_atv_fisica' value='$nivel_atv'>
        <input type='hidden' name='objetivo' value='$objetivo'>
        <input type='hidden' name='mb' value='$mb'>
        <input type='hidden' name='tmb_total' value='$tmb_total'>
        <button type='submit' name='avancar' class='btn-avc'>Avançar</button>
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

        // Calcular macros com base no objetivo
        $prot_necessarias = 0;
        $carbo_necessarias = 0;
        $gord_necessarias = 0;

        if ($objetivo == 'cutting') {
            $prot_necessarias = round($peso * 1.8, 2);
            $carbo_necessarias = round($peso * 3, 2);
            $gord_necessarias = round($peso * 0.5, 2);
        } elseif ($objetivo == 'bulking') {
            $prot_necessarias = round($peso * 2, 2);
            $carbo_necessarias = round($peso * 4, 2);
            $gord_necessarias = round($peso * 1, 2);
        }

        $data_inicio = date('Y-m-d');
        $situacao = 'A';

        if (!$conexao) {
            die("<h3>Erro: Falha na conexão com o banco de dados.</h3>");
        }

        $sql_usuario_check = "SELECT id_usuario FROM usuario WHERE id_usuario = '" . $_SESSION['id_usuario'] . "'";
        $result_usuario_check = mysqli_query($conexao, $sql_usuario_check);

        if (mysqli_num_rows($result_usuario_check) > 0) {
            $sql_usuario = "UPDATE usuario SET
                peso = '$peso',
                altura = '$altura',
                idade = '$idade',
                sexo = '$sexo',
                protocolo = '$protocolo',
                nivel_atv_fisica = '$nivel_atv',
                metabolismo_basal = '$mb',
                gasto_calorico_total = '$tmb_total',
                prot_necessarias = '$prot_necessarias',
                carbo_necessarias = '$carbo_necessarias',
                gord_necessarias = '$gord_necessarias'
                WHERE id_usuario = '" . $_SESSION['id_usuario'] . "'";

            if (mysqli_query($conexao, $sql_usuario)) {
                echo "<p>Dados do usuário registrados com sucesso!</p>";
            } else {
                echo "<p>Erro ao atualizar os dados do usuário: " . mysqli_error($conexao) . "</p>";
            }
        } else {
            $sql_usuario_insert = "INSERT INTO usuario (peso, altura, idade, sexo, protocolo, nivel_atv_fisica, metabolismo_basal, gasto_calorico_total, prot_necessarias, carbo_necessarias, gord_necessarias) 
                VALUES ('$peso', '$altura', '$idade', '$sexo', '$protocolo', '$nivel_atv', '$mb', '$tmb_total', '$prot_necessarias', '$carbo_necessarias', '$gord_necessarias')";

            if (mysqli_query($conexao, $sql_usuario_insert)) {
                echo "<p>Dados do usuário inseridos com sucesso!</p>";
            } else {
                echo "<p>Erro ao inserir os dados do usuário: " . mysqli_error($conexao) . "</p>";
            }
        }

        $sql_check = "SELECT id_dieta FROM dieta WHERE id_usuario = '" . $_SESSION['id_usuario'] . "'";
        $result = mysqli_query($conexao, $sql_check);

        if (mysqli_num_rows($result) > 0) {
            $sql_dieta = "UPDATE dieta SET
                objetivo = '$objetivo',
                data_inicio = '$data_inicio',
                situacao = '$situacao'
                WHERE id_usuario = '" . $_SESSION['id_usuario'] . "'";

            if (mysqli_query($conexao, $sql_dieta)) {
                echo "<p>Objetivo e dados atualizados na tabela dieta com sucesso!</p>";
            } else {
                echo "<p>Erro ao atualizar os dados na tabela dieta: " . mysqli_error($conexao) . "</p>";
            }
        } else {
            $sql_dieta_insert = "INSERT INTO dieta (id_usuario, objetivo, data_inicio, situacao) 
                                 VALUES ('" . $_SESSION['id_usuario'] . "', '$objetivo', '$data_inicio', '$situacao')";

            if (mysqli_query($conexao, $sql_dieta_insert)) {
                echo "<p>Objetivo inserido na tabela dieta com sucesso!</p>";
            } else {
                echo "<p>Erro ao inserir o objetivo na tabela dieta: " . mysqli_error($conexao) . "</p>";
            }
        }

        header("Location: ../selecao_alimentos/selecao_alimentos.php");
    }
    ?>
    <script src="script.js"></script>
</body>

</html>