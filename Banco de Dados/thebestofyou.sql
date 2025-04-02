-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01-Abr-2025 às 15:57
-- Versão do servidor: 10.4.27-MariaDB
-- versão do PHP: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `thebestofyou`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `alimentos`
--

CREATE TABLE `alimentos` (
  `id_alimentos` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `quantidade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `dieta`
--

CREATE TABLE `dieta` (
  `id_dieta` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `objetivo` varchar(30) NOT NULL,
  `situacao` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `dieta`
--

INSERT INTO `dieta` (`id_dieta`, `data_inicio`, `id_usuario`, `objetivo`, `situacao`) VALUES
(1, '2025-04-01', 1, 'cutting', 'A');

-- --------------------------------------------------------

--
-- Estrutura da tabela `dieta_alimentos`
--

CREATE TABLE `dieta_alimentos` (
  `id_dieta_alimentos` int(11) NOT NULL,
  `id_dieta` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `evolucao`
--

CREATE TABLE `evolucao` (
  `id_evolucao` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `peso_incio` float NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_fim` date NOT NULL,
  `peso_fim` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `exercicio`
--

CREATE TABLE `exercicio` (
  `id_exercicio` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `link_video_execucao` varchar(255) NOT NULL,
  `grupo_muscular` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `informacoes_nutricionais`
--

CREATE TABLE `informacoes_nutricionais` (
  `id_informacoes_nutricionais` int(11) NOT NULL,
  `id_alimentos` int(11) NOT NULL,
  `valor_energetico` float NOT NULL,
  `carboidratos` float NOT NULL,
  `proteinas` float NOT NULL,
  `gorduras_totais` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `treino`
--

CREATE TABLE `treino` (
  `id_treino` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `divisao_treino` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `treino_exercicio`
--

CREATE TABLE `treino_exercicio` (
  `id_treino_exercicio` int(11) NOT NULL,
  `id_treino` int(11) NOT NULL,
  `id_exercicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `idade` int(3) NOT NULL,
  `peso` float NOT NULL,
  `altura` int(3) NOT NULL,
  `metabolismo_basal` float NOT NULL,
  `nivel_atv_fisica` float NOT NULL,
  `gasto_calorico_total` float NOT NULL,
  `sexo` varchar(30) NOT NULL,
  `protocolo` varchar(30) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `telefone` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `email`, `senha`, `idade`, `peso`, `altura`, `metabolismo_basal`, `nivel_atv_fisica`, `gasto_calorico_total`, `sexo`, `protocolo`, `nome`, `telefone`) VALUES
(1, 'henriqueoliveirapiresoo24@gmail.com', '$2y$10$WIH90SekyzWjinEiOswNwuLKSDhI4sCXV.5u4jES0S/qZwE7gGe0G', 16, 90.5, 177, 2059.46, 1.55, 3192.16, 'masculino', 'harris', 'henrique', '64988888888');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `alimentos`
--
ALTER TABLE `alimentos`
  ADD PRIMARY KEY (`id_alimentos`);

--
-- Índices para tabela `dieta`
--
ALTER TABLE `dieta`
  ADD PRIMARY KEY (`id_dieta`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices para tabela `dieta_alimentos`
--
ALTER TABLE `dieta_alimentos`
  ADD PRIMARY KEY (`id_dieta_alimentos`),
  ADD KEY `id_dieta` (`id_dieta`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices para tabela `evolucao`
--
ALTER TABLE `evolucao`
  ADD PRIMARY KEY (`id_evolucao`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices para tabela `exercicio`
--
ALTER TABLE `exercicio`
  ADD PRIMARY KEY (`id_exercicio`);

--
-- Índices para tabela `informacoes_nutricionais`
--
ALTER TABLE `informacoes_nutricionais`
  ADD PRIMARY KEY (`id_informacoes_nutricionais`),
  ADD KEY `id_alimentos` (`id_alimentos`);

--
-- Índices para tabela `treino`
--
ALTER TABLE `treino`
  ADD PRIMARY KEY (`id_treino`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices para tabela `treino_exercicio`
--
ALTER TABLE `treino_exercicio`
  ADD PRIMARY KEY (`id_treino_exercicio`),
  ADD KEY `id_treino` (`id_treino`),
  ADD KEY `id_exercicio` (`id_exercicio`);

--
-- Índices para tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alimentos`
--
ALTER TABLE `alimentos`
  MODIFY `id_alimentos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `dieta`
--
ALTER TABLE `dieta`
  MODIFY `id_dieta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `dieta_alimentos`
--
ALTER TABLE `dieta_alimentos`
  MODIFY `id_dieta_alimentos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `evolucao`
--
ALTER TABLE `evolucao`
  MODIFY `id_evolucao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `exercicio`
--
ALTER TABLE `exercicio`
  MODIFY `id_exercicio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `informacoes_nutricionais`
--
ALTER TABLE `informacoes_nutricionais`
  MODIFY `id_informacoes_nutricionais` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `treino`
--
ALTER TABLE `treino`
  MODIFY `id_treino` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `treino_exercicio`
--
ALTER TABLE `treino_exercicio`
  MODIFY `id_treino_exercicio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
