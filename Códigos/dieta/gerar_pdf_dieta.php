<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');
require __DIR__ . '/../../vendor/autoload.php';

// Função de parser (sem alterações)
function parseMarkdownTable($markdown) {
    $refeicoes = [];
    $linhas = explode("\n", trim($markdown));

    for ($i = 2; $i < count($linhas); $i++) {
        $linha = trim($linhas[$i]);
        if (empty($linha)) continue;

        $linhaSemBarras = trim($linha, '|');
        $colunas = explode('|', $linhaSemBarras);

        if (count($colunas) === 6) {
            $refeicoes[] = [
                'refeicao'     => trim($colunas[0]),
                'descricao'    => str_replace('<br>', "\n", trim($colunas[1])),
                'calorias'     => trim($colunas[2]),
                'proteinas'    => trim($colunas[3]),
                'carboidratos' => trim($colunas[4]),
                'gorduras'     => trim($colunas[5])
            ];
        }
    }
    return $refeicoes;
}


class PDF_Diet extends tFPDF
{
    protected $objetivoPDF = '';
    protected $nomePDF = '';
    protected $horaGeracaoPDF = '';

    function Header() {
        $this->SetFillColor(46, 139, 87);
        $this->Rect(0, 0, $this->GetPageWidth(), 25, 'F');
        $caminho_logo = __DIR__ . '/imagens/logob.png';
        $this->Image($caminho_logo, 10, -1, 30);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('DejaVu', 'B', 20);
        $this->SetY(9);
        $this->SetX(45);
        $this->Cell(0, 10, 'TheBestOF-You', 0, 0, 'L');
        $this->Ln(25);
    }
    
    function setObjetivo($objetivo) { $this->objetivoPDF = $objetivo; }
    function setNome($nome) { $this->nomePDF = $nome; }
    function setHoraGeracao($hora) { $this->horaGeracaoPDF = $hora; }

    function Footer() {
        $this->SetY(-15);
        $this->SetDrawColor(46, 139, 87);
        $this->SetLineWidth(1);
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
        $this->Ln(2);
        $infoParts = [];
        if (!empty($this->nomePDF)) { $infoParts[] = 'Nome: ' . $this->nomePDF; }
        if (!empty($this->objetivoPDF)) { $infoParts[] = 'Objetivo: ' . $this->objetivoPDF; }
        if (!empty($this->horaGeracaoPDF)) { $infoParts[] = 'Gerado em: ' . $this->horaGeracaoPDF; }
        $infoString = implode(' | ', $infoParts);
        $this->SetFont('DejaVu', '', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, $infoString, 0, 0, 'L');
        $this->SetFont('DejaVu', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo(), 0, 0, 'R');
    }

    // --- MÉTODO COM O TÍTULO CORRIGIDO ---
    function CriarTabelaRefeicao($refeicaoData) {
        // --- MUDANÇA APLICADA AQUI ---
        // Limpa os asteriscos do título antes de exibi-lo
        $tituloLimpo = str_replace('**', '', $refeicaoData['refeicao']);

        // Parte 1: Título
        $this->SetFont('DejaVu', 'B', 14);
        $this->SetFillColor(47, 79, 79);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, $tituloLimpo, 1, 1, 'C', true);

        // Parte 2: Descrição dos alimentos
        $this->SetFont('DejaVu', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(245, 245, 245);
        $this->MultiCell(0, 8, $refeicaoData['descricao'], 'LR', 'L', true);

        // Parte 3: Linha de resumo nutricional
        $this->SetFont('DejaVu', 'B', 9);
        $this->SetFillColor(220, 220, 220);
        $this->SetTextColor(50, 50, 50);
        
        $resumo = sprintf(
            "Calorias: %s | Proteínas: %s | Carboidratos: %s | Gorduras: %s",
            $refeicaoData['calorias'],
            $refeicaoData['proteinas'],
            $refeicaoData['carboidratos'],
            $refeicaoData['gorduras']
        );
        
        $this->Cell(0, 7, $resumo, 'LRB', 1, 'C', true);
        
        $this->Ln(10);
    }
}


// O resto do código continua o mesmo
if (isset($_GET['dieta']) && !empty($_GET['dieta'])) {
    
    $texto_dieta_markdown = urldecode($_GET['dieta']);
    
    session_start();
    $objetivo = $_SESSION['objetivo'] ?? '';
    $horaAtual = date('d/m/Y H:i:s');
    
    $pdf = new PDF_Diet();
    $pdf->setObjetivo($objetivo);
    $pdf->setHoraGeracao($horaAtual);
    $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
    $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
    $pdf->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
    $pdf->AddPage();
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('DejaVu', 'B', 18);
    $pdf->Cell(0, 15, 'Seu Plano Alimentar Personalizado', 0, 1, 'C');
    $pdf->Ln(5);

    $refeicoes = parseMarkdownTable($texto_dieta_markdown);

    foreach ($refeicoes as $refeicao) {
        $pdf->CriarTabelaRefeicao($refeicao);
    }
    
    $pdf->Output('plano_alimentar.pdf', 'D');

} else {
    echo 'Nenhum dado de dieta foi fornecido na URL.';
}
?>