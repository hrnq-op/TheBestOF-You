<?php
session_start();
include('../conexao.php');

$usuario_id = $_SESSION['id_usuario'];

$evolucoes = [];

// Buscar evoluções ordenadas por id (mais antigas primeiro)
$res_evolucao = $conexao->query("SELECT id_evolucao, data_inicio, peso_inicio, tempo_dieta, objetivo FROM evolucao WHERE id_usuario = $usuario_id ORDER BY id_evolucao ASC");

$peso_anterior = null;
$objetivo_anterior = null;

while ($row = $res_evolucao->fetch_assoc()) {
    $peso_atual = $row['peso_inicio'];
    $objetivo_atual = $row['objetivo'];

    if ($peso_anterior !== null) {
        $diferenca = $peso_atual - $peso_anterior;
    } else {
        $diferenca = 0.0;
    }

    $evolucoes[] = [
        'tempo_dieta' => $row['tempo_dieta'] ?? '',
        'objetivo' => $objetivo_atual ?? '',
        'peso' => $peso_atual,
        'diferenca' => number_format($diferenca, 1),
        'separador' => ($objetivo_anterior !== null && $objetivo_anterior !== $objetivo_atual)
    ];

    $peso_anterior = $peso_atual;
    $objetivo_anterior = $objetivo_atual;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Seção de Evolução</title>
    <link rel="stylesheet" href="evolucao.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .separador-objetivo {
            height: 5px;
            background-color: #343a40;
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
        <div class="site-name">Evolução</div>
        <div class="logo">
            <a href="../pagina_principal/index.php">
                <img src="imagens/Logo.png" alt="Logo">
            </a>
        </div>
    </header>

    <div class="container py-5">
        <h1 class="mb-4">📊 Seção de Evolução</h1><br>

        <form method="POST" action="processar_evolucao.php" enctype="multipart/form-data" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Peso Atual (kg)</label>
                <input type="number" name="peso" class="form-control" step="0.1" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tempo de Dieta</label>
                <input type="text" name="tempo_dieta" class="form-control" placeholder="Ex: 2 semanas, 1 mês">
            </div>

            <div class="col-md-4">
                <label class="form-label">Anexar Imagem (opcional)</label>
                <input type="file" name="imagem[]" class="form-control" multiple accept="image/*">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="atualizar" class="btn btn-success">Atualizar Dados</button>
            </div>
        </form>

        <div class="container py-5">
            <h3>Histórico de Evolução</h3><br>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tempo de Dieta</th>
                        <th>Objetivo</th>
                        <th>Peso (kg)</th>
                        <th>Perda/Ganho (kg)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $objetivo_anterior = '';
                    foreach ($evolucoes as $registro):
                        if ($objetivo_anterior !== '' && $registro['objetivo'] !== $objetivo_anterior): ?>
                            <tr style="background-color: #ccc;">
                                <td colspan="5"></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td><?= htmlspecialchars($registro['tempo_dieta']) ?></td>
                            <td><?= htmlspecialchars($registro['objetivo']) ?></td>
                            <td><?= htmlspecialchars($registro['peso']) ?></td>
                            <td><?= htmlspecialchars($registro['diferenca']) ?> kg</td>
                        </tr>
                        <?php $objetivo_anterior = $registro['objetivo']; ?>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>

        <?php
        // Mostrar imagens agrupadas por objetivo e tempo_dieta
        $sql_imagens = "SELECT tempo_dieta, objetivo, peso_inicio, imagem FROM evolucao WHERE id_usuario = ? ORDER BY id_evolucao ASC";
        $stmt_imagens = $conexao->prepare($sql_imagens);
        $stmt_imagens->bind_param("i", $usuario_id);
        $stmt_imagens->execute();
        $resultado = $stmt_imagens->get_result();

        $grupos = [];

        while ($row = $resultado->fetch_assoc()) {
            $key = $row['objetivo'] . '|' . $row['tempo_dieta'];
            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'objetivo' => $row['objetivo'],
                    'tempo_dieta' => $row['tempo_dieta'],
                    'peso' => $row['peso_inicio'],
                    'imagens' => []
                ];
            }

            if (!empty($row['imagem'])) {
                $imagens = explode(',', $row['imagem']);
                $grupos[$key]['imagens'] = array_merge($grupos[$key]['imagens'], $imagens);
            }
        }
        ?>

        <div class="container py-4">
            <h3 class="text-center mb-5" style="font-size: 2rem;">Imagens da Evolução</h3> <br>

            <?php foreach ($grupos as $grupo): ?>
                <?php if (!empty($grupo['imagens'])): ?>
                    <div class="mb-5">
                        <h5 class="mb-3"><?= htmlspecialchars($grupo['objetivo']) ?> - <?= htmlspecialchars($grupo['tempo_dieta']) ?> (<?= htmlspecialchars($grupo['peso']) ?> kg)</h5>
                        <div style="display: flex; flex-wrap: wrap; gap: 16px;">
                            <?php foreach ($grupo['imagens'] as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" alt="Imagem evolução" style="height: 300px; border: 1px solid #ccc; padding: 4px; border-radius: 8px;">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>



</body>

</html>