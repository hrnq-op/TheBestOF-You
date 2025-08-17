<?php
// Inicia a sessão PHP para gerenciar o estado do usuário
session_start();
// Inclui o arquivo de conexão com o banco de dados
include "../conexao.php";

$usuario_logado = false;
$nome_usuario = "";

if (isset($_SESSION['id_usuario'])) {
    $id = $_SESSION['id_usuario'];
    $sql = "SELECT nome FROM usuario WHERE id_usuario = '$id'";
    $resultado = mysqli_query($conexao, $sql);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $linha = mysqli_fetch_assoc($resultado);
        $nome_usuario = $linha['nome'];
        $usuario_logado = true;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheBestOF-You</title>
    <!-- Inclui seu arquivo CSS principal (assumindo que ele contém os estilos personalizados e as animações) -->
    <link href="principal.css?v=2" rel="stylesheet">
    <!-- Inclui o CDN do Tailwind CSS para as classes utilitárias -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inclui a fonte Inter do Google Fonts com vários pesos para tipografia refinada -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Configuração do Tailwind CSS para estender o tema com cores e arredondamentos personalizados -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#00c853',
                        'primary-green-dark': '#00b44b',
                        'light-bg': '#f5f8fb',
                        'dark-gray': '#333',
                        'medium-gray': '#555',
                        'light-gray-text': '#777',
                        'white': '#ffffff',
                    },
                    borderRadius: {
                        'DEFAULT': '0.5rem',
                        'lg': '1rem',
                        'xl': '1.5rem',
                        'full': '9999px',
                    }
                }
            }
        }
    </script>
</head>
<body class="antialiased">

    <!-- Cabeçalho (Header) do site -->
    <!-- Adicionadas classes 'fixed', 'top-0', 'left-0', 'right-0', 'z-50' para fixar o cabeçalho no topo -->
    <!-- Estilo inline para 'backdrop-filter' e 'background-color' com transparência -->
    <header class="fixed top-0 left-0 right-0 z-50 py-3 px-4 md:px-10 lg:px-20 flex items-center justify-between shadow-md rounded-b-lg"
            style="background-color: rgba(255, 255, 255, 0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
        <!-- Contêiner do logo e nome do site -->
        <!-- Adicionada a classe '-ml-4' para mover o logo mais para a esquerda -->
        <div class="flex items-center gap-4 -ml-4">
            <a href="#" class="flex items-center">
                <!-- A altura do logo foi aumentada um pouco: de h-16 md:h-20 para h-18 md:h-24 -->
                <img src="imagens/Logo.png" alt="Logo TheBestOF-You" class="h-18 md:h-24 rounded-full shadow-sm">
                <!-- Nome do site, visível apenas em telas maiores para evitar quebra de layout em mobile -->
                <span class="text-dark-gray text-xl sm:text-2xl md:text-3xl font-bold ml-3 hidden sm:block">TheBestOF-You</span>
            </a>
        </div>

        <!-- Contêiner para links de navegação e autenticação -->
        <nav class="menu flex items-center">
            <!-- Verifica se o usuário está logado para exibir o nome ou o botão de login -->
            <?php if ($usuario_logado): ?>
                <div class="user-box flex items-center gap-2 mr-4">
                    <span class="text-dark-gray text-base font-medium">Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
                    <!-- Opcional: Adicionar um link para o painel do usuário ou logout aqui -->
                    <a href="../logout.php" class="text-primary-green hover:text-primary-green-dark text-sm font-medium ml-2">Sair</a>
                </div>
            <?php else: ?>
                <div class="auth-container">
                    <!-- Botão de Login/Cadastro com estilo profissional e sombra customizada -->
                    <a href="../login/login.php" class="auth-btn bg-primary-green text-white py-2 px-6 rounded-lg text-base font-medium transition-all duration-300 hover:bg-primary-green-dark hover:scale-105 btn-shadow">Entrar / Cadastrar</a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Botão de Menu para Mobile (Hamburguer) - Exibido apenas em telas pequenas -->
        <button class="md:hidden text-dark-gray focus:outline-none">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </header>

    <!-- Adiciona um padding-top ao main para compensar a altura do cabeçalho fixo -->
    <!-- Isso evita que o conteúdo inicial seja escondido pelo cabeçalho -->
    <main class="container mx-auto px-4 pt-[100px] pb-8 md:pt-[120px] md:pb-16 lg:pt-[140px] lg:pb-20">
        <!-- Seção de Introdução (Hero Section) -->
        <section class="intro flex flex-col md:flex-row items-center justify-between gap-8 md:gap-16">
            <div class="intro-text flex-1 max-w-full md:max-w-xl text-center md:text-left">
                <h1 class="text-dark-gray text-2xl md:text-3xl lg:text-4xl font-extrabold leading-tight mb-6 md:mb-8 animate-fade-in-up">
                    Otimize Seus Resultados com o <span class="text-primary-green">Melhor Software de Nutrição e Treino</span>
                </h1>

                <!-- Parágrafo atualizado com o novo texto e sem marcadores de negrito -->
                <p class="text-medium-gray text-lg md:text-xl leading-relaxed mb-4 animate-fade-in-up delay-100">
                    Confie no TheBestOF-You para guiar sua jornada. Nossos algoritmos inteligentes geram dietas e treinos personalizados, adaptados às suas necessidades e objetivos únicos, garantindo resultados reais e sustentáveis.
                </p>

                <div class="btn-container-bottom flex justify-center md:justify-start">
                    <a href="../login/login.php" class="btn-comecar bg-primary-green text-white py-3 px-8 md:py-4 md:px-10 rounded-lg text-lg font-semibold transition-all duration-300 hover:bg-primary-green-dark hover:scale-105 btn-shadow animate-fade-in-up delay-200">
                        Comece Agora Grátis
                    </a>
                </div>
            </div>

            <div class="intro-image flex-1 flex justify-center md:justify-end mt-8 md:mt-0">
                <img src="imagens/Imagem1.png" alt="Pessoa se exercitando ao ar livre" class="w-full max-w-md md:max-w-lg lg:max-w-xl h-auto transform hover:scale-105 transition-transform duration-500 animate-fade-in-right">
            </div>
        </section>

        <!-- Seção de Cards de Destaque (Confiança, Desempenho, Benefícios) -->
        <section class="py-16 md:py-24 bg-light-bg">
            <div class="container mx-auto px-4">
                <h2 class="text-dark-gray text-3xl md:text-4xl font-extrabold text-center mb-12 md:mb-16 animate-fade-in-up">
                    Por que escolher o <span class="text-primary-green">TheBestOF-You</span>?
                </h2>

                <!-- Grid responsivo para os cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-12 animate-fade-in-up delay-300">

                    <!-- Card 1: Confiança -->
                    <div class="bg-white p-8 rounded-xl shadow-lg flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300">
                        <div class="text-primary-green mb-4">
                            <!-- Ícone de Confiança - Exemplo usando SVG inline -->
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-dark-gray text-2xl font-semibold mb-4">Confiança</h3>
                        <p class="text-medium-gray text-base leading-relaxed">
                            Confie no TheBestOF-You para guiar sua jornada. Nossos algoritmos inteligentes geram dietas e treinos personalizados, adaptados às suas necessidades e objetivos únicos, garantindo resultados reais e sustentáveis.
                        </p>
                    </div>

                    <!-- Card 2: Desempenho -->
                    <div class="bg-white p-8 rounded-xl shadow-lg flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300">
                        <div class="text-primary-green mb-4">
                            <!-- Ícone de Desempenho - Exemplo usando SVG inline -->
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <h3 class="text-dark-gray text-2xl font-semibold mb-4">Desempenho</h3>
                        <p class="text-medium-gray text-base leading-relaxed">
                            Alcance seu máximo desempenho com nossa IA integrada. Ela monitora seu progresso e oferece atualizações simultâneas sobre o que não está te deixando confortável ou te impedindo de evoluir, otimizando seus resultados.
                        </p>
                    </div>

                    <!-- Card 3: Benefícios -->
                    <div class="bg-white p-8 rounded-xl shadow-lg flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300">
                        <div class="text-primary-green mb-4">
                            <!-- Ícone de Benefícios - Exemplo usando SVG inline -->
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-dark-gray text-2xl font-semibold mb-4">Benefícios</h3>
                        <p class="text-medium-gray text-base leading-relaxed">
                            Experimente os amplos benefícios de uma nutrição balanceada e treino eficaz. Melhore sua energia, humor, sono, imunidade e conquiste um corpo mais forte e saudável, transformando sua vida de dentro para fora.
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- Nova Seção de Depoimentos (Quem Recomenda) com Carrossel -->
        <section class="py-16 md:py-24 bg-white relative">
            <div class="container mx-auto px-4">
                <h2 class="text-dark-gray text-3xl md:text-4xl font-extrabold text-center mb-12 md:mb-16 animate-fade-in-up">
                    Quem <span class="text-primary-green">Recomenda</span> o TheBestOF-You?
                </h2>

                <!-- Contêiner do Carrossel de Depoimentos -->
                <div id="testimonial-carousel" class="relative overflow-hidden w-full max-w-3xl mx-auto">
                    <div id="testimonial-container" class="flex transition-transform duration-500 ease-in-out">
                        <!-- Depoimento 1: Ana Costa -->
                        <div class="testimonial-card flex-shrink-0 w-full bg-light-bg p-8 rounded-xl shadow-md flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300">
                            <div class="text-primary-green mb-4">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-dark-gray text-xl font-semibold mb-2">Ana Costa</h3>
                            <p class="text-medium-gray text-sm mb-4">Nutricionista - São Paulo/SP</p>
                            <p class="text-medium-gray text-base leading-relaxed">
                                "Este software revolucionou minha prática clínica! Meus pacientes estão mais engajados e os resultados são visíveis em menos tempo. A ferramenta é intuitiva e a personalização é um diferencial incrível."
                            </p>
                        </div>

                        <!-- Depoimento 2: Bruno Mendes -->
                        <div class="testimonial-card flex-shrink-0 w-full bg-light-bg p-8 rounded-xl shadow-md flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300 hidden">
                            <div class="text-primary-green mb-4">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-dark-gray text-xl font-semibold mb-2">Bruno Mendes</h3>
                            <p class="text-medium-gray text-sm mb-4">Personal Trainer - Rio de Janeiro/RJ</p>
                            <p class="text-medium-gray text-base leading-relaxed">
                                "A integração entre dieta e treino que o TheBestOF-You oferece é sensacional. Consigo monitorar o progresso dos meus alunos de forma muito mais eficiente e adaptar os planos em tempo real. Essencial para otimizar os resultados!"
                            </p>
                        </div>

                        <!-- Depoimento 3: Clara Faria -->
                        <div class="testimonial-card flex-shrink-0 w-full bg-light-bg p-8 rounded-xl shadow-md flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300 hidden">
                            <div class="text-primary-green mb-4">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 6v2a2 2 0 01-2 2H3a2 2 0 01-2-2v-2m18 0A2 2 0 0022 16h-3.375m0 0l-.5.5m-.5-.5l-.5.5M16 16v2a2 2 0 01-2 2H8a2 2 0 01-2-2v-2"></path>
                                </svg>
                            </div>
                            <h3 class="text-dark-gray text-xl font-semibold mb-2">Clara Faria</h3>
                            <p class="text-medium-gray text-sm mb-4">Usuária TheBestOF-You - Belo Horizonte/MG</p>
                            <p class="text-medium-gray text-base leading-relaxed">
                                "Estou impressionada com a inteligência do sistema! Minha dieta e treinos são perfeitamente ajustados, e o suporte para o que não está funcionando é incrível. Meus resultados nunca foram tão bons. Super recomendo!"
                            </p>
                        </div>

                        <!-- Depoimento 4: Rafael Silva -->
                        <div class="testimonial-card flex-shrink-0 w-full bg-light-bg p-8 rounded-xl shadow-md flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300 hidden">
                            <div class="text-primary-green mb-4">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657-3.582 3-8 3s-8-1.343-8-3c0-4.418 3.582-8 8-8s8 3.582 8 8zM4 14v4M12 14v4M20 14v4"></path>
                                </svg>
                            </div>
                            <h3 class="text-dark-gray text-xl font-semibold mb-2">Rafael Silva</h3>
                            <p class="text-medium-gray text-sm mb-4">Atleta Amador - Curitiba/PR</p>
                            <p class="text-medium-gray text-base leading-relaxed">
                                "Como corredor, cada refeição e treino contam. Com o TheBestOF-You, tenho a certeza de que estou nutrindo meu corpo e treinando de forma otimizada para minhas provas. Ganhei muita performance!"
                            </p>
                        </div>

                        <!-- Depoimento 5: Sofia Lima -->
                        <div class="testimonial-card flex-shrink-0 w-full bg-light-bg p-8 rounded-xl shadow-md flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300 hidden">
                            <div class="text-primary-green mb-4">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253M12 6.253c1.168-.776 2.754-1.253 4.5-1.253 1.746 0 3.332.477 4.5 1.253m-13 6V13m0 0a3 3 0 100 6m0-6V4"></path>
                            </svg>
                        </div>
                        <h3 class="text-dark-gray text-xl font-semibold mb-2">Sofia Lima</h3>
                        <p class="text-medium-gray text-sm mb-4">Estudante de Nutrição - Porto Alegre/RS</p>
                        <p class="text-medium-gray text-base leading-relaxed">
                            "Mesmo estudando a área, o TheBestOF-You me surpreende. É uma forma prática de aplicar o conhecimento e ver como a tecnologia pode impulsionar resultados reais na vida das pessoas. Futuro da nutrição!"
                        </p>
                    </div>

                    <!-- Depoimento 6: Gustavo Pereira -->
                    <div class="testimonial-card flex-shrink-0 w-full bg-light-bg p-8 rounded-xl shadow-md flex flex-col items-center text-center transform hover:scale-105 transition-transform duration-300 hidden">
                        <div class="text-primary-green mb-4">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 12v6a2 2 0 002 2h10a2 2 0 002-2v-6M12 12h.01"></path>
                            </svg>
                        </div>
                        <h3 class="text-dark-gray text-xl font-semibold mb-2">Gustavo Pereira</h3>
                        <p class="text-medium-gray text-sm mb-4">Empreendedor - Brasília/DF</p>
                        <p class="text-medium-gray text-base leading-relaxed">
                            "Com a correria do dia a dia, ter um plano de nutrição e treino que se adapta à minha rotina é um salvador. O TheBestOF-You me ajuda a manter o foco e a energia para meus desafios diários. Essencial!"
                        </p>
                    </div>

                </div>

                <!-- Botões de Navegação do Carrossel -->
                <div class="flex justify-center items-center mt-8 gap-4">
                    <button id="prev-testimonial" class="p-3 bg-primary-green text-white rounded-full shadow-lg hover:bg-primary-green-dark transition-colors duration-300 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button id="next-testimonial" class="p-3 bg-primary-green text-white rounded-full shadow-lg hover:bg-primary-green-dark transition-colors duration-300 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        <!-- Nova Seção de Contato -->
        <section class="py-16 md:py-24 bg-light-bg">
            <div class="container mx-auto px-4 flex flex-col md:flex-row items-center justify-center gap-8 md:gap-16">
                <!-- Imagem de Contato -->
                <div class="flex-shrink-0 w-full max-w-xs md:max-w-sm lg:max-w-md">
                     <img src="imagens/confuso.png" alt="Pessoa em dúvida com celular" class="w-full h-auto">
                </div>

                <!-- Texto de Contato e Botão -->
                <div class="text-center md:text-left">
                    <h2 class="text-dark-gray text-3xl md:text-4xl font-extrabold mb-6 animate-fade-in-up">
                        Ainda com <span class="text-primary-green">Dúvidas</span>?
                    </h2>
                    <p class="text-medium-gray text-lg md:text-xl leading-relaxed mb-8 animate-fade-in-up delay-100">
                        Entre em contato conosco e descubra como podemos orientá-lo na escolha ideal para fazer sua saúde e bem-estar crescerem!
                    </p>
                    <div class="flex justify-center md:justify-start animate-fade-in-up delay-200">
                        <a href="https://wa.me/5564992818272" target="_blank" class="flex items-center bg-primary-green text-white py-3 px-8 rounded-lg text-lg font-semibold transition-all duration-300 hover:bg-primary-green-dark hover:scale-105 btn-shadow">
                            <!-- Ícone do WhatsApp Padrão (SVG) -->
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12.04 2C7.03 2 3 6.03 3 11.04C3 12.87 3.51 14.6 4.47 16.09L3.06 20.14L7.26 18.73C8.75 19.49 10.39 19.96 12.04 19.96C17.05 19.96 21.08 15.93 21.08 10.92C21.08 6.03 17.05 2 12.04 2ZM17.27 15.09C16.94 15.7 15.35 15.65 14.86 15.43C14.37 15.21 13.08 14.54 12.83 14.45C12.58 14.36 12.39 14.28 12.14 14.61C11.89 14.94 11.16 15.65 10.91 15.89C10.66 16.13 10.43 16.16 9.92 15.93C9.41 15.7 8.08 15.22 6.84 14.01C5.81 12.98 5.17 11.66 4.93 11.23C4.7 10.79 4.89 10.63 5.06 10.46C5.23 10.29 5.44 10.02 5.59 9.87C5.74 9.72 5.86 9.61 6.01 9.53C6.16 9.46 6.32 9.38 6.48 9.3C6.64 9.21 6.77 9.17 6.91 9.14C7.05 9.11 7.21 9.07 7.37 9.04C7.53 9.01 7.7 8.97 7.86 8.97C8.02 8.97 8.16 9.02 8.28 9.16C8.4 9.3 8.56 9.46 8.70 9.61C8.84 9.76 9.04 10.05 9.19 10.27C9.34 10.49 9.49 10.79 9.61 10.92C9.73 11.05 9.85 11.2 9.97 11.34C10.09 11.48 10.21 11.62 10.33 11.77C10.45 11.92 10.59 12.06 10.73 12.21C10.88 12.36 11.02 12.48 11.16 12.6C11.3 12.72 11.44 12.83 11.58 12.94C11.72 13.05 11.86 13.16 12.01 13.25C12.16 13.34 12.3 13.43 12.44 13.52C12.59 13.61 12.72 13.7 12.85 13.79C12.98 13.88 13.12 13.97 13.26 14.05C13.4 14.13 13.54 14.22 13.68 14.3C13.82 14.38 13.97 14.46 14.11 14.53C14.26 14.6 14.4 14.67 14.54 14.74C14.69 14.81 14.83 14.88 14.98 14.94C15.12 15.01 15.26 15.07 15.41 15.13C15.55 15.19 15.69 15.25 15.83 15.3C15.98 15.36 16.12 15.41 16.26 15.46C16.41 15.51 16.55 15.56 16.70 15.61C16.84 15.66 16.98 15.71 17.12 15.76C17.27 15.8 17.41 15.84 17.55 15.88C17.69 15.92 17.83 15.96 17.98 16C18.12 16.03 18.26 16.07 18.41 16.09C18.55 16.11 18.69 16.14 18.83 16.16C18.98 16.18 19.12 16.21 19.26 16.23C19.40 16.25 19.54 16.27 19.69 16.29C19.83 16.31 19.97 16.33 20.12 16.35C20.26 16.37 20.40 16.38 20.55 16.39C20.69 16.40 20.83 16.42 20.98 16.42C21.12 16.42 21.26 16.42 21.40 16.42C21.55 16.42 21.69 16.42 21.83 16.42C21.98 16.42 22.12 16.42 22.26 16.42C22.40 16.42 22.54 16.42 22.69 16.42C22.83 16.42 22.97 16.42 23.12 16.42C23.26 16.42 23.40 16.42 23.55 16.42C23.69 16.42 23.83 16.42 23.98 16.42C23.99 16.42 23.99 16.42 24 16.42V11.04C24 6.03 19.97 2 14.96 2C9.95 2 5.92 6.03 5.92 11.04C5.92 12.87 6.43 14.6 7.39 16.09L5.98 20.14L10.18 18.73C11.67 19.49 13.31 19.96 14.96 19.96C20.05 19.96 24.08 15.93 24.08 10.92C24.08 6.03 20.05 2 14.96 2H12.04Z" fill-rule="evenodd" clip-rule="evenodd" />
                            </svg>
                            Entre em Contato
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Rodapé (Footer) do site -->
    <footer class="text-center py-8 px-4 mt-12 bg-white text-light-gray-text text-sm rounded-t-lg shadow-inner">
        <p>&copy; 2025 TheBestOF-You. Todos os direitos reservados.</p>
        <div class="flex justify-center space-x-4 mt-2">
            <a href="#" class="text-light-gray-text hover:text-primary-green transition-colors duration-300">Privacidade</a>
            <a href="#" class="text-light-gray-text hover:text-primary-green transition-colors duration-300">Termos de Uso</a>
        </div>
    </footer>

    <script>
        // JavaScript para o Carrossel de Depoimentos
        document.addEventListener('DOMContentLoaded', function() {
            const testimonialCards = document.querySelectorAll('.testimonial-card');
            const prevButton = document.getElementById('prev-testimonial');
            const nextButton = document.getElementById('next-testimonial');
            let currentIndex = 0; // Começa no primeiro depoimento

            // Função para mostrar um depoimento específico
            function showTestimonial(index) {
                // Esconde todos os depoimentos
                testimonialCards.forEach((card) => {
                    card.classList.add('hidden'); // Oculta o card
                    card.classList.remove('block'); // Garante que 'block' seja removido
                });
                // Garante que o índice esteja dentro dos limites (circular)
                currentIndex = (index + testimonialCards.length) % testimonialCards.length;
                // Mostra o depoimento atual
                testimonialCards[currentIndex].classList.remove('hidden'); // Torna o card visível
                testimonialCards[currentIndex].classList.add('block'); // Define display como block
            }

            // Mostra o primeiro depoimento ao carregar a página
            showTestimonial(currentIndex);

            // Event listener para o botão 'Próximo'
            nextButton.addEventListener('click', function() {
                showTestimonial(currentIndex + 1);
            });

            // Event listener para o botão 'Anterior'
            prevButton.addEventListener('click', function() {
                showTestimonial(currentIndex - 1);
            });
        });
    </script>
</body>
</html>
