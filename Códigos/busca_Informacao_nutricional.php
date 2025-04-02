<?php
ob_start(); // Evita erro de cabeçalhos já enviados
include "conexao.php";

// Função para verificar se o alimento já está cadastrado
function obterIdAlimento($conexao, $nome)
{
    global $id_alimentos;
    $stmt = $conexao->prepare("SELECT id_alimentos FROM alimentos WHERE nome = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $stmt->bind_result($id_alimentos);
    $stmt->fetch();
    $stmt->close();
    return $id_alimentos;
}

// Função para verificar se já existem informações nutricionais cadastradas
function informacoesNutricionaisExistem($conexao, $id_alimentos)
{
    global $count;
    $stmt = $conexao->prepare("SELECT COUNT(*) FROM informacoes_nutricionais WHERE id_alimentos = ?");
    $stmt->bind_param("i", $id_alimentos);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Função para buscar informações nutricionais da API USDA
function buscarInformacoesNutricionaisUSDA($alimento)
{
    $dicionario = [
        'feijão' => 'beans',
        'arroz' => 'rice',
        'frango' => 'chicken',
        'maçã' => 'apple',
        'banana' => 'banana',
        'batata' => 'potato',
        'carne' => 'beef',
        'ovo' => 'egg',
        'leite' => 'milk',
        'tomate' => 'tomato',
        'cenoura' => 'carrot',
        'alface' => 'lettuce',
        'espinafre' => 'spinach',
        'pepino' => 'cucumber',
        'abóbora' => 'pumpkin',
        'brócolis' => 'broccoli',
        'abacate' => 'avocado',
        'melancia' => 'watermelon',
        'manga' => 'mango',
        'laranja' => 'orange',
        'pera' => 'pear',
        'kiwi' => 'kiwi',
        'cabelinha' => 'cabbage',
        'alho' => 'garlic',
        'cebola' => 'onion',
        'pão' => 'bread',
        'macarrão' => 'pasta',
        'queijo' => 'cheese',
        'iogurte' => 'yogurt',
        'chocolate' => 'chocolate',
        'morango' => 'strawberry',
        'pêssego' => 'peach',
        'coco' => 'coconut',
        'mel' => 'honey',
        'azeite' => 'olive oil',
        'sal' => 'salt',
        'pimenta' => 'pepper',
        'abobrinha' => 'zucchini',
        'berinjela' => 'eggplant',
        'couve' => 'kale',
        'mandioca' => 'cassava',
        'feijão preto' => 'black beans',
        'feijão carioca' => 'pinto beans',
        'lentilha' => 'lentils',
        'grão de bico' => 'chickpeas',
        'quinoa' => 'quinoa',
        'aveia' => 'oats',
        'amendoim' => 'peanut',
        'castanha' => 'nut',
        'amêndoa' => 'almond',
        'noz' => 'walnut',
        'caju' => 'cashew',
        'abacaxi' => 'pineapple',
        'cabeluda' => 'parsley',
        'oregano' => 'oregano',
        'manjericão' => 'basil',
        'tomilho' => 'thyme',
        'alecrim' => 'rosemary',
        'salsinha' => 'parsley',
        'orégano' => 'oregano',
        'salsão' => 'celery',
        'peixe' => 'fish',
        'salmon' => 'salmon',
        'bacalhau' => 'cod',
        'camarão' => 'shrimp',
        'lagosta' => 'lobster',
        'polvo' => 'octopus',
        'frutos do mar' => 'seafood',
        'tilápia' => 'tilapia',
        'salmão' => 'salmon',
        'pescada' => 'perch',
        'truta' => 'trout',
        'cavala' => 'mackerel',
        'sardinha' => 'sardine',
        'açucar' => 'sugar'
    ];


    $alimento_em_ingles = strtolower(trim($alimento));
    if (array_key_exists($alimento_em_ingles, $dicionario)) {
        $alimento_em_ingles = $dicionario[$alimento_em_ingles];
    }

    $api_url = "https://api.nal.usda.gov/fdc/v1/foods/search";
    $api_key = "NYnb2hbm07OPP5BSVoDnZHNvKnXxt749V1cighZy";

    $params = [
        'query' => $alimento_em_ingles,
        'api_key' => $api_key
    ];

    $url = $api_url . '?' . http_build_query($params);
    $response = file_get_contents($url);
    $response_data = json_decode($response, true);

    if (isset($response_data['foods']) && count($response_data['foods']) > 0) {
        $food_data = $response_data['foods'][0];
        $valor_energetico = 0;
        $carboidratos = 0;
        $proteinas = 0;
        $gorduras_totais = 0;

        foreach ($food_data['foodNutrients'] as $nutrient) {
            if ($nutrient['nutrientName'] == 'Energy') {
                $valor_energetico = $nutrient['value'];
            } elseif ($nutrient['nutrientName'] == 'Carbohydrate, by difference') {
                $carboidratos = $nutrient['value'];
            } elseif ($nutrient['nutrientName'] == 'Protein') {
                $proteinas = $nutrient['value'];
            } elseif ($nutrient['nutrientName'] == 'Total lipid (fat)') {
                $gorduras_totais = $nutrient['value'];
            }
        }

        return [
            'valor_energetico' => $valor_energetico,
            'carboidratos' => $carboidratos,
            'proteinas' => $proteinas,
            'gorduras_totais' => $gorduras_totais
        ];
    }

    return [
        'valor_energetico' => 0,
        'carboidratos' => 0,
        'proteinas' => 0,
        'gorduras_totais' => 0
    ];
}

$informacoes_nutricionais = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $alimentos = explode(",", $_POST["alimentos"]);

    foreach ($alimentos as $alimento) {
        $alimento = trim($alimento);
        if (!empty($alimento)) {
            // Verifica se o alimento já existe no banco
            $id_alimento = obterIdAlimento($conexao, $alimento);

            if (!$id_alimento) {
                // Insere o alimento no banco se não existir
                $stmt = $conexao->prepare("INSERT INTO alimentos (nome) VALUES (?)");
                $stmt->bind_param("s", $alimento);
                $stmt->execute();
                $id_alimento = $stmt->insert_id;
                $stmt->close();
            }

            // Verifica se já existem informações nutricionais no banco
            if (!informacoesNutricionaisExistem($conexao, $id_alimento)) {
                $info = buscarInformacoesNutricionaisUSDA($alimento);

                $stmt = $conexao->prepare("INSERT INTO informacoes_nutricionais (id_alimentos, valor_energetico, carboidratos, proteinas, gorduras_totais) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("idddd", $id_alimento, $info['valor_energetico'], $info['carboidratos'], $info['proteinas'], $info['gorduras_totais']);
                $stmt->execute();
                $stmt->close();
            } else {
                // Busca as informações nutricionais do banco para exibição
                $stmt = $conexao->prepare("SELECT valor_energetico, carboidratos, proteinas, gorduras_totais FROM informacoes_nutricionais WHERE id_alimentos = ?");
                $stmt->bind_param("i", $id_alimento);
                $stmt->execute();
                $stmt->bind_result($valor_energetico, $carboidratos, $proteinas, $gorduras_totais);
                $stmt->fetch();
                $stmt->close();

                $info = [
                    'valor_energetico' => $valor_energetico,
                    'carboidratos' => $carboidratos,
                    'proteinas' => $proteinas,
                    'gorduras_totais' => $gorduras_totais
                ];
            }

            // Adiciona ao array para exibição na tabela
            $informacoes_nutricionais[] = [
                'nome' => $alimento,
                'valor_energetico' => $info['valor_energetico'],
                'carboidratos' => $info['carboidratos'],
                'proteinas' => $info['proteinas'],
                'gorduras_totais' => $info['gorduras_totais'],
            ];
        }
    }

    $conexao->close();
}

?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Montagem Dieta</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h1>Fale os alimentos que você gostaria de ter na sua dieta</h1>
    <form method="post">
        <label for="alimentos">Alimentos (separe por vírgula):</label>
        <input type="text" id="alimentos" name="alimentos" required> <br><br>
        <button type="submit">Adicionar</button>
    </form>

    <?php if (!empty($informacoes_nutricionais)): ?>
        <h2>Informações Nutricionais (por 100g)</h2>
        <table>
            <tr>
                <th>Alimento</th>
                <th>Calorias (kcal)</th>
                <th>Carboidratos (g)</th>
                <th>Proteínas (g)</th>
                <th>Gorduras (g)</th>
            </tr>
            <?php foreach ($informacoes_nutricionais as $info): ?>
                <tr>
                    <td><?= htmlspecialchars($info['nome']) ?></td>
                    <td><?= htmlspecialchars($info['valor_energetico']) ?></td>
                    <td><?= htmlspecialchars($info['carboidratos']) ?></td>
                    <td><?= htmlspecialchars($info['proteinas']) ?></td>
                    <td><?= htmlspecialchars($info['gorduras_totais']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>

</html>