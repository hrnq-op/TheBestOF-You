<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');
require __DIR__ . '/../../vendor/autoload.php';

// --- NOVA FUNÇÃO PARA LER TABELAS MARKDOWN DE TREINO ---
function parseTreinoMarkdownTable($markdown) {
    $linhasFinais = [];
    $linhas = explode("\n", trim($markdown));

    // Itera sobre todas as linhas do texto recebido
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        
        // Se a linha for um cabeçalho de dia, adiciona-a diretamente
        if (strpos($linha, '###') === 0) {
            // Remove o '###' e os '**' para limpar o título
            $tituloLimpo = str_replace(['###', '**'], '', trim($linha));
            $linhasFinais[] = 'DIA_HEADER:' . $tituloLimpo; // Marca como um cabeçalho
        } 
        // Se for uma linha de dados da tabela (começa e termina com '|')
        elseif (strpos($linha, '|') === 0 && strpos($linha, ':---') === false) {
            $colunas = array_map('trim', explode('|', trim($linha, '|')));
            
            // Espera 4 colunas: Vazio, Exercício, Séries, Repetições, Execução, Vazio
            if (count($colunas) >= 4) {
                $exercicio = $colunas[0];
                $series = $colunas[1];
                $repeticoes = $colunas[2];
                
                // Monta a string no formato desejado
                $linhasFinais[] = '• ' . $exercicio . ' - ' . $series . 'x' . $repeticoes;
            }
        }
    }
    return $linhasFinais;
}


class PDF_Treino extends tFPDF
{
    protected $enfasePDF = '';
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
    
    function setEnfase($enfase) { $this->enfasePDF = $enfase; }
    function setHoraGeracao($hora) { $this->horaGeracaoPDF = $hora; }

    function Footer() {
        $this->SetY(-15);
        $this->SetDrawColor(46, 139, 87);
        $this->SetLineWidth(1);
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
        $this->Ln(2);
        $infoParts = [];
        if (!empty($this->enfasePDF)) { $infoParts[] = 'Ênfase: ' . $this->enfasePDF; }
        if (!empty($this->horaGeracaoPDF)) { $infoParts[] = 'Gerado em: ' . $this->horaGeracaoPDF; }
        $infoString = implode(' | ', $infoParts);
        $this->SetFont('DejaVu', '', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, $infoString, 0, 0, 'L');
        $this->SetFont('DejaVu', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo(), 0, 0, 'R');
    }

    function CriarTabelaDivisao($titulo, $conteudo) {
        $this->SetFont('DejaVu', 'B', 14);
        $this->SetFillColor(80, 80, 80);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(190, 10, $titulo, 1, 1, 'C', true);

        $this->SetFont('DejaVu', '', 11);
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(245, 245, 245);
        $this->MultiCell(190, 7, $conteudo, 'LRB', 'L', true);

        $this->Ln(10);
    }
}


// --- LÓGICA PRINCIPAL ATUALIZADA ---
if (isset($_GET['treino']) && !empty($_GET['treino'])) {
    
    $texto_treino_markdown = urldecode($_GET['treino']);
    $enfase = isset($_GET['enfase']) ? urldecode($_GET['enfase']) : '';
    $horaAtual = date('d/m/Y H:i:s');
    
    $pdf = new PDF_Treino();
    $pdf->setEnfase($enfase);
    $pdf->setHoraGeracao($horaAtual);
    $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
    $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
    $pdf->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
    $pdf->AddPage();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('DejaVu', 'B', 18);
    $pdf->Cell(0, 15, 'Seu Plano de Treino Personalizado', 0, 1, 'C');
    $pdf->Ln(5);

    // 1. Usa a nova função para processar o Markdown do treino
    $linhas_processadas = parseTreinoMarkdownTable($texto_treino_markdown);

    $titulo_atual = '';
    $conteudo_atual = '';

    // 2. Agrupa os exercícios sob seus respectivos dias
    foreach ($linhas_processadas as $linha) {
        if (strpos($linha, 'DIA_HEADER:') === 0) {
            // Se já tínhamos um dia anterior, imprime a tabela dele
            if (!empty($titulo_atual)) {
                $pdf->CriarTabelaDivisao($titulo_atual, $conteudo_atual);
            }
            // Inicia um novo dia
            $titulo_atual = str_replace('DIA_HEADER:', '', $linha);
            $conteudo_atual = '';
        } else {
            // Adiciona a linha de exercício ao conteúdo do dia atual
            $conteudo_atual .= $linha . "\n";
        }
    }

    // 3. Imprime a última tabela de dia que ficou aberta
    if (!empty($titulo_atual)) {
        $pdf->CriarTabelaDivisao($titulo_atual, trim($conteudo_atual));
    }
    
    $pdf->Output('plano_de_treino.pdf', 'D');
} else {
    echo 'Nenhum dado de treino foi fornecido na URL.';
}
?>