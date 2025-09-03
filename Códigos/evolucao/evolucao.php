<?php
session_start();
include('../conexao.php');

$usuario_id = $_SESSION['id_usuario'];

// --- BUSCA DADOS PARA A TABELA ---
$evolucoes = [];
$res_evolucao = $conexao->query("SELECT id_evolucao, data_inicio, peso_inicio, tempo_dieta, objetivo FROM evolucao WHERE id_usuario = $usuario_id ORDER BY id_evolucao ASC");
$peso_anterior = null;
$objetivo_anterior = null;
while ($row = $res_evolucao->fetch_assoc()) {
    $peso_atual = $row['peso_inicio'];
    $objetivo_atual = $row['objetivo'];
    if ($peso_anterior !== null) { $diferenca = $peso_atual - $peso_anterior; } else { $diferenca = 0.0; }
    $evolucoes[] = [ 'id_evolucao' => $row['id_evolucao'], 'tempo_dieta' => $row['tempo_dieta'] ?? '', 'objetivo' => $objetivo_atual ?? '', 'peso' => $peso_atual, 'diferenca' => number_format($diferenca, 1), 'separador' => ($objetivo_anterior !== null && $objetivo_anterior !== $objetivo_atual) ];
    $peso_anterior = $peso_atual;
    $objetivo_anterior = $objetivo_atual;
}

// --- PREPARAR DADOS PARA O GRÁFICO ---
$sql_grafico = "SELECT data_inicio, peso_inicio, objetivo FROM evolucao WHERE id_usuario = $usuario_id ORDER BY data_inicio ASC";
$res_grafico = $conexao->query($sql_grafico);
$datas = [];
$pesos = [];
$objetivos = [];
while ($row_grafico = $res_grafico->fetch_assoc()) {
    $datas[] = date('d/m/Y', strtotime($row_grafico['data_inicio']));
    $pesos[] = $row_grafico['peso_inicio'];
    $objetivos[] = $row_grafico['objetivo'];
}
$datasGrafico = json_encode($datas);
$pesosGrafico = json_encode($pesos);
$objetivosGrafico = json_encode($objetivos);

// --- LÓGICA DE EXCLUSÃO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_evolucao'])) {
    $id_excluir = intval($_POST['excluir_evolucao']);
    $stmt = $conexao->prepare("SELECT imagem FROM evolucao WHERE id_evolucao = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_excluir, $usuario_id);
    $stmt->execute();
    $stmt->bind_result($imagens_str);
    $stmt->fetch();
    $stmt->close();
    if (!empty($imagens_str)) { $imagens = explode(',', $imagens_str); foreach ($imagens as $img) { $img = trim($img); if (file_exists($img)) { unlink($img); } } }
    $stmt_del = $conexao->prepare("DELETE FROM evolucao WHERE id_evolucao = ? AND id_usuario = ?");
    $stmt_del->bind_param("ii", $id_excluir, $usuario_id);
    $stmt_del->execute();
    $stmt_del->close();
    header("Location: evolucao.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Seção de Evolução</title>
    <link rel="stylesheet" href="evolucao.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            background-color: #eeeef1 !important;
        }
        .card {
            background-color: #ffffff;
            /* Adicionado sombra para destacar o card */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            border-radius: 8px;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out; /* Suaviza a transição */
        }
        /* Efeito de ampliação ao passar o mouse */
        .card:hover {
            transform: scale(1.03); /* Amplia em 3% */
            box-shadow: 0 8px 16px rgba(0,0,0,0.2); /* Aumenta a sombra */
        }
        .evolution-image-card {
            width: 320px; /* Largura fixa para os cards de imagem */
            margin-bottom: 20px; /* Espaçamento entre os cards */
        }
        .evolution-image-card img {
            max-width: 100%;
            height: 250px; /* Altura fixa para as imagens */
            object-fit: cover; /* Garante que a imagem preencha o espaço sem distorcer */
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .evolution-image-card .card-body {
            padding: 15px;
            text-align: center;
        }
        .evolution-image-card .card-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c2c54;
        }
        .evolution-image-card .card-text {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        .separador-objetivo { 
            height: 5px; 
            background-color: #343a40; 
        }
    </style>
</head>
<body>

    <header>
        <div class="logo"> <a href="../pagina_principal/index.php"> <img src="imagens/Logo.png" alt="Logo"> </a> </div>
        <div class="site-name">Evolução</div>
        <div class="logo"> <a href="../pagina_principal/index.php"> <img src="imagens/Logo.png" alt="Logo"> </a> </div>
    </header>

    <div class="container py-5">
        <h1 class="mb-4">Seção de Evolução</h1><br>

        <div class="card mb-5">
            <div class="card-header fw-bold">
                Registrar Nova Evolução
            </div>
            <div class="card-body">
                <form method="POST" action="processar_evolucao.php" enctype="multipart/form-data" id="formEvolucao">
                    <input type="hidden" name="id_evolucao" id="id_evolucao">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="peso" class="form-label">Peso Atual (kg)</label>
                            <input type="number" name="peso" id="peso" class="form-control" step="0.1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tempo_dieta" class="form-label">Tempo de Dieta</label>
                            <input type="text" name="tempo_dieta" id="tempo_dieta" class="form-control" placeholder="Ex: 2 semanas, 1 mês">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="objetivo" class="form-label">Objetivo</label>
                        <input type="text" name="objetivo" id="objetivo" class="form-control" placeholder="Ex: bulking, cutting">
                    </div>
                    <div class="mb-3">
                        <label for="imagem" class="form-label">Anexar Imagem (opcional)</label>
                        <input type="file" name="imagem[]" id="imagem" class="form-control" multiple accept="image/*">
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="atualizar" class="btn btn-success">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"> Acompanhamento de Peso </div>
            <div class="card-body">
                <div style="position: relative; height: 350px; width: 90%; margin: auto;">
                    <canvas id="graficoPeso"></canvas>
                </div>
            </div>
        </div>

        <div class="container py-5">
            <h3>Histórico de Evolução</h3><br>
            <table class="table table-bordered">
                 <thead> 
                    <tr> 
                        <th>Tempo de Dieta</th> 
                        <th>Objetivo</th> 
                        <th>Peso (kg)</th> 
                        <th>Perda/Ganho (kg)</th> 
                        <th>Ações</th> 
                    </tr> 
                </thead>
                <tbody>
                    <?php $objetivo_anterior = ''; foreach ($evolucoes as $registro): if ($objetivo_anterior !== '' && $registro['objetivo'] !== $objetivo_anterior): ?> <tr style="background-color: #ccc;"> <td colspan="5"></td> </tr> <?php endif; ?>
                    <tr> 
                        <td><?= htmlspecialchars($registro['tempo_dieta']) ?></td> 
                        <td><?= htmlspecialchars($registro['objetivo']) ?></td> 
                        <td><?= htmlspecialchars($registro['peso']) ?></td> 
                        <td><?= htmlspecialchars($registro['diferenca']) ?> kg</td> 
                        <td> 
                            <button class="btn btn-success btn-sm me-2" title="Alterar" onclick="preencherFormularioEdicao(<?= $registro['id_evolucao'] ?>, '<?= htmlspecialchars($registro['peso'], ENT_QUOTES) ?>', '<?= htmlspecialchars($registro['tempo_dieta'], ENT_QUOTES) ?>', '<?= htmlspecialchars($registro['objetivo'], ENT_QUOTES) ?>')">
                                <i class="fas fa-pen-to-square"></i>
                            </button> 
                            <form method="POST" onsubmit="return confirm('Deseja realmente excluir esta evolução?')" style="display: inline;"> 
                                <input type="hidden" name="excluir_evolucao" value="<?= $registro['id_evolucao'] ?>"> 
                                <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button> 
                            </form> 
                        </td> 
                    </tr>
                    <?php $objetivo_anterior = $registro['objetivo']; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="container py-4">
            <h3 class="text-center mb-3" style="font-size: 2rem;">Imagens da Evolução</h3>
            <p class="text-center text-muted mb-5"> Para adicionar novas imagens em um período já existente, clique em <strong>"Alterar"</strong> e selecione as novas imagens. </p>
            
            <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php
            // Lógica de imagens (inalterada na busca, mas a exibição muda)
            $sql_imagens = "SELECT id_evolucao, tempo_dieta, objetivo, peso_inicio, imagem FROM evolucao WHERE id_usuario = ? ORDER BY id_evolucao ASC";
            $stmt_imagens = $conexao->prepare($sql_imagens);
            $stmt_imagens->bind_param("i", $usuario_id);
            $stmt_imagens->execute();
            $resultado = $stmt_imagens->get_result();
            
            // Reestruturando os grupos para ter cada imagem e seus dados de evolução específicos
            $imagens_detalhadas = [];
            while ($row = $resultado->fetch_assoc()) {
                if (!empty($row['imagem'])) {
                    $imagens = explode(',', $row['imagem']);
                    foreach ($imagens as $img_path) {
                        $img_path = trim($img_path);
                        if (!empty($img_path)) {
                            $imagens_detalhadas[] = [
                                'id_evolucao' => $row['id_evolucao'],
                                'tempo_dieta' => $row['tempo_dieta'],
                                'objetivo' => $row['objetivo'],
                                'peso' => $row['peso_inicio'],
                                'caminho_imagem' => $img_path
                            ];
                        }
                    }
                }
            }

            // Exibindo cada imagem em seu próprio card
            if (!empty($imagens_detalhadas)):
                foreach ($imagens_detalhadas as $img_data):
            ?>
                <div class="card evolution-image-card">
                    <img src="<?= htmlspecialchars($img_data['caminho_imagem']) ?>" class="card-img-top" alt="Imagem evolução">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($img_data['objetivo']) ?></h5>
                        <p class="card-text">Tempo: <?= htmlspecialchars($img_data['tempo_dieta']) ?></p>
                        <p class="card-text">Peso: <?= htmlspecialchars($img_data['peso']) ?> kg</p>
                        <form method="POST" action="remover_imagem.php" onsubmit="return confirm('Deseja realmente remover esta imagem?')" style="display: inline-block;">
                            <input type="hidden" name="id_usuario" value="<?= $usuario_id ?>">
                            <input type="hidden" name="imagem" value="<?= htmlspecialchars($img_data['caminho_imagem']) ?>">
                            <button type="submit" class="btn btn-danger btn-sm mt-2" title="Remover Imagem">
                                <i class="fas fa-trash-alt"></i> Remover Imagem
                            </button>
                        </form>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <p class="text-center text-muted">Nenhuma imagem de evolução cadastrada ainda.</p>
            <?php
            endif;
            ?>
            </div> </div>
    </div>

<script> function preencherFormularioEdicao(id, peso, tempo, objetivo) { document.getElementById("id_evolucao").value = id; document.getElementById("peso").value = peso; document.getElementById("tempo_dieta").value = tempo; document.getElementById("objetivo").value = objetivo; window.scrollTo({ top: 0, behavior: 'smooth' }); } </script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const datas = <?= $datasGrafico ?>;
    const pesos = <?= $pesosGrafico ?>;
    const objetivos = <?= $objetivosGrafico ?>;
    if (datas.length >= 2) {
        const ctx = document.getElementById('graficoPeso').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: datas,
                datasets: [{
                    label: 'Peso (kg)',
                    data: pesos,
                    borderColor: '#2e8b57',
                    backgroundColor: 'rgba(46, 139, 87, 0.1)',
                    fill: true,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const pesoLabel = `Peso: ${context.parsed.y} kg`;
                                const index = context.dataIndex;
                                const objetivoLabel = `Objetivo: ${objetivos[index]}`;
                                return [pesoLabel, objetivoLabel];
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: false, title: { display: true, text: 'Peso (kg)' } },
                    x: { title: { display: true, text: 'Data do Registro' } }
                }
            }
        });
    }
});
</script>

</body>
</html>