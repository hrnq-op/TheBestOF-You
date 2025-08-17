-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/08/2025 às 19:29
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

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
-- Estrutura para tabela `alimentos`
--

CREATE TABLE `alimentos` (
  `id_alimentos` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `id_dieta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `dieta`
--

CREATE TABLE `dieta` (
  `id_dieta` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `objetivo` varchar(30) NOT NULL,
  `situacao` varchar(1) NOT NULL,
  `refeicoes` int(11) NOT NULL,
  `arquivo_dieta` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `evolucao`
--

CREATE TABLE `evolucao` (
  `id_evolucao` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `peso_inicio` float NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_fim` date NOT NULL,
  `peso_fim` float NOT NULL,
  `objetivo` varchar(30) NOT NULL,
  `tempo_dieta` varchar(30) NOT NULL,
  `imagem` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `exercicio`
--

CREATE TABLE `exercicio` (
  `id_exercicio` int(11) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `link_video_execucao` varchar(255) NOT NULL,
  `grupo_muscular` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `exercicio`
--

INSERT INTO `exercicio` (`id_exercicio`, `nome`, `link_video_execucao`, `grupo_muscular`) VALUES
(1, 'Supino Reto com Barra', 'https://www.youtube.com/shorts/50RSzhMG5Hc', 'Peito, ombros, tríceps'),
(2, 'Supino Reto com Halteres', 'https://www.youtube.com/shorts/-Q4QcFAn51M', ' Peito, ombros, tríceps'),
(3, 'Supino inclinado com barra', 'https://www.youtube.com/shorts/wlLcApOyK2w', 'Peito (parte superior), ombros, tríceps.'),
(4, 'Supino inclinado com halteres', 'https://www.youtube.com/shorts/akOsBDJifQQ', 'Peito (parte superior), ombros, tríceps.'),
(5, 'Supino declinado com barra', 'https://youtu.be/J2g6qPBJfqo?si=Hm36aMxErP-YBkXL', 'Peito (parte inferior), ombros, tríceps'),
(6, 'Fly (máquina)', 'https://www.youtube.com/shorts/3K__fj8IMWQ', 'Peito, ombros.'),
(7, 'Crossover (cabo)', 'https://youtu.be/pdMWt71MPlw?si=2rCFA80oZ5d8nKC9', ' Peito, ombros.'),
(8, 'Flexões (Push-ups)', 'https://www.youtube.com/shorts/WY8nwVTcyww', 'Peito, ombros, tríceps'),
(9, 'Remada curvada com barra', 'https://www.youtube.com/shorts/SbuXAFpDUkI', ' Dorsais, trapézio, romboides, bíceps.'),
(10, 'Remada curvada com halteres', 'https://www.youtube.com/watch?v=Vk6c7CjtM14', ' Dorsais, trapézio, romboides, bíceps'),
(11, 'Remada na máquina', 'https://www.youtube.com/shorts/n2xEPLv2uhg', ' Dorsais, trapézio, romboides, bíceps.'),
(12, 'Puxada na frente (Lat Pulldown', 'https://www.youtube.com/shorts/Y4Mq3y2vzLw', 'Dorsais, trapézio, bíceps.'),
(13, 'Pull-up (Barra fixa)', 'https://www.youtube.com/shorts/BYYxtz9MtDc', ' Dorsais, bíceps, trapézio'),
(14, 'Remada unilateral com halteres', 'https://www.youtube.com/shorts/RmowAgP6vsk', 'Dorsais, trapézio, romboides, bíceps.'),
(15, 'Puxada alta com pegada aberta', 'https://www.youtube.com/shorts/I-my22VjbdY', 'Dorsais, trapézio, bíceps.'),
(16, 'Remada baixa', 'https://www.youtube.com/shorts/ZWEsI23La34', 'Dorsais, romboides, trapézio, bíceps.'),
(17, 'Desenvolvimento de ombro com b', 'https://www.youtube.com/shorts/6TpBrrIzRE4', ' Deltoides, tríceps.'),
(18, 'Desenvolvimento de ombro com h', 'https://www.youtube.com/shorts/oWXDHlrHiIc', ' Deltoides, tríceps.'),
(19, 'Desenvolvimento de ombro na má', 'https://www.youtube.com/shorts/VFbijwqdsTU', ' Deltoides, tríceps.'),
(20, 'Elevação lateral com halteres', 'https://www.youtube.com/shorts/4RdH8Z9lRhQ', 'Deltoides (lateral).'),
(21, 'Elevação lateral com cabo', 'https://www.youtube.com/shorts/7QmcQy1Z6Vo', 'Deltoides (lateral)'),
(22, 'Elevação frontal com halteres ', 'https://www.youtube.com/shorts/F6toacmeUlA', 'Deltoides (anterior).'),
(23, 'Elevação frontal com barra', 'https://www.youtube.com/shorts/mSiGD-Bj6Xk', ' Deltoides (anterior)'),
(24, 'Elevação frontal com cabo', 'https://www.youtube.com/shorts/eG_a1Rmv7p0', 'Deltoides (anterior).'),
(25, 'Remada alta com barra', 'https://www.youtube.com/shorts/KABahQZ_9FQ', 'Deltoides posterior, trapézio, romboides.'),
(26, 'levação lateral invertida', 'https://www.youtube.com/shorts/jHY4zZ0Fcno', ' Deltoides posterior, trapézio.'),
(27, 'Rosca direta com barra', 'https://www.youtube.com/watch?v=FHyZEuRpSg4&t=38s', 'Bíceps'),
(28, 'Rosca direta com halteres', 'https://www.youtube.com/shorts/hZrvPQz-0f8', 'Bíceps'),
(29, 'Rosca alternada com halteres', 'https://www.youtube.com/shorts/a28SbBN_14k', 'Bíceps'),
(30, 'Rosca concentrada com halteres', 'https://www.youtube.com/shorts/xDYL0tcj4Ek', 'Bíceps'),
(31, 'Rosca martelo com halteres', 'https://www.youtube.com/shorts/8PN6YfFC6Q4', 'Bíceps (braquial), antebraços.'),
(32, 'Rosca na máquina', 'https://www.youtube.com/shorts/jvcm0-445nA', 'Bíceps'),
(33, 'Rosca no cabo (Cable Curl)', 'https://www.youtube.com/shorts/OLDilBZxtmg', 'Bíceps'),
(34, 'Rosca Scott', 'https://www.youtube.com/shorts/faBk2akE0mQ', 'Bíceps'),
(35, 'ríceps testa (Skull Crushers)', 'https://www.youtube.com/shorts/nZCGGprwRx0', ' Tríceps.'),
(36, 'Tríceps na polia alta (Pushdow', 'https://www.youtube.com/shorts/1YCIzxYMZDg', 'Tríceps'),
(37, 'Mergulho (Dips)', 'https://www.youtube.com/shorts/qyr4VaEhG3g', 'Tríceps, peito.'),
(38, 'Extensão de tríceps com halter', 'https://www.youtube.com/shorts/8FNGBJUHfsA', ' Tríceps'),
(39, 'Extensão de tríceps na máquina', 'https://www.youtube.com/shorts/2OymsPc-9Tw', ' Tríceps.'),
(40, 'Tríceps com cabo', 'https://www.youtube.com/shorts/eJHyVyKVMZ4', 'Tríceps'),
(41, 'Agachamento com barra', 'https://www.youtube.com/shorts/wzsUfTMPrEg', 'Quadríceps, glúteos, isquiotibiais, core.'),
(42, 'Agachamento na máquina Smith', 'https://www.youtube.com/shorts/8pjN_4fkxgU', 'Quadríceps, glúteos, isquiotibiais, core.'),
(43, 'Leg Press', 'https://www.youtube.com/shorts/lHZUF_s3q9c', 'Quadríceps, glúteos, isquiotibiais.'),
(44, 'Cadeira extensora', 'https://youtu.be/pJZXbaF-MCM?si=k52SlnlEEdOtDvgw', ' Quadríceps.'),
(45, 'Cadeira flexora', 'https://youtu.be/Zss6E3VU6X0?si=6KgAcS0OPjphog44', ' Isquiotibiais.'),
(46, 'Afundo com halteres', 'https://www.youtube.com/shorts/w8Ar4bgxizw', 'Quadríceps, glúteos, isquiotibiais.'),
(47, 'Elevação de quadril (Hip Thrus', 'https://www.youtube.com/shorts/btWqWMBlwlc', ' Glúteos, isquiotibiais'),
(48, 'Stiff', 'https://www.youtube.com/shorts/w0bT2qDWJmw', ' Isquiotibiais, glúteos, lombar.'),
(49, 'Búlgaro', 'https://www.youtube.com/shorts/oRf8HQipiBI', 'Quadríceps, glúteos, isquiotibiais, core.'),
(50, 'Agachamento sumô com halteres', 'https://www.youtube.com/shorts/uXsHFdEiMH8', ' Glúteos, quadríceps, adutores.'),
(51, 'Cadeira abdutora', 'https://www.youtube.com/shorts/E5r5OmVfxpU', 'Adutores.'),
(52, 'Cadeira adutora', 'https://www.youtube.com/shorts/lsb18p3cOAk', 'Adutores, quadríceps'),
(53, 'Deadlift (Levantamento terra)', 'https://www.youtube.com/shorts/y4YD_D4g-Vk', 'Isquiotibiais, glúteos, lombar, trapézio.'),
(54, 'Elevação de panturrilha no Smi', 'https://www.youtube.com/shorts/12_-N79aAeA', 'Panturrilhas'),
(55, 'Elevação de panturrilha sentad', 'https://www.youtube.com/shorts/tAjoHFlgYW8', 'Panturrilhas (focando no músculo sóleo)'),
(56, 'Elevação de panturrilha na máq', 'https://www.youtube.com/shorts/1BUuWhDiMNg', 'Panturrilhas'),
(57, 'Elevação de panturrilha no leg', 'https://www.youtube.com/shorts/erUNxqpFxkc', 'Panturrilhas'),
(58, 'Abdominal reto (Crunch)', 'https://www.youtube.com/shorts/P5ySsdvCMyE', 'Abdômen.'),
(59, 'Abdominal oblíquo (Russian Twi', 'https://www.youtube.com/shorts/8hnozF9z1_U', 'Abdômen (oblíquos).'),
(60, 'Abdominal na máquina', 'https://www.youtube.com/shorts/YSKWgY_47L4', 'Abdômen.'),
(61, 'Prancha', 'https://www.youtube.com/shorts/jZY0XzzXleI', 'Core'),
(62, 'Abdominal com roda', 'https://www.youtube.com/shorts/KTOto_msRUM', 'Abdômen, ombros, lombar.'),
(63, 'Extensão lombar na máquina', 'https://www.youtube.com/shorts/pZZB43bJuz4', 'Lombar'),
(64, 'Extensão lombar Máquina', 'https://www.youtube.com/shorts/Hf3e7yvqWk4', 'Lombar'),
(65, 'Remada Cavalinho', 'https://www.youtube.com/shorts/8K183T-ms3w', 'Dorsais (principalmente)  Romboides  Trapézio  Bíceps');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessoes_ativas`
--

CREATE TABLE `sessoes_ativas` (
  `id_sessao` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `treino`
--

CREATE TABLE `treino` (
  `id_treino` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `divisao_treino` varchar(30) NOT NULL,
  `dias_de_treino` int(11) NOT NULL,
  `nivel_de_treino` varchar(30) NOT NULL,
  `arquivo_treino` varchar(255) NOT NULL,
  `enfase_muscular` varchar(255) DEFAULT NULL,
  `situacao` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `treino_exercicio`
--

CREATE TABLE `treino_exercicio` (
  `id_treino_exercicio` int(11) NOT NULL,
  `id_treino` int(11) NOT NULL,
  `id_exercicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `idade` int(3) NOT NULL,
  `peso` float NOT NULL,
  `altura` int(3) NOT NULL,
  `metabolismo_basal` float NOT NULL,
  `nivel_atv_fisica` float NOT NULL,
  `gasto_calorico_total` float NOT NULL,
  `sexo` varchar(30) NOT NULL,
  `protocolo` varchar(30) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `telefone` varchar(15) NOT NULL,
  `carbo_necessarias` float NOT NULL,
  `prot_necessarias` float NOT NULL,
  `gord_necessarias` float NOT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios_pendentes`
--

CREATE TABLE `usuarios_pendentes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `telefone` int(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alimentos`
--
ALTER TABLE `alimentos`
  ADD PRIMARY KEY (`id_alimentos`),
  ADD KEY `id_dieta` (`id_dieta`);

--
-- Índices de tabela `dieta`
--
ALTER TABLE `dieta`
  ADD PRIMARY KEY (`id_dieta`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `evolucao`
--
ALTER TABLE `evolucao`
  ADD PRIMARY KEY (`id_evolucao`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `exercicio`
--
ALTER TABLE `exercicio`
  ADD PRIMARY KEY (`id_exercicio`);

--
-- Índices de tabela `sessoes_ativas`
--
ALTER TABLE `sessoes_ativas`
  ADD PRIMARY KEY (`id_sessao`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `treino`
--
ALTER TABLE `treino`
  ADD PRIMARY KEY (`id_treino`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `treino_exercicio`
--
ALTER TABLE `treino_exercicio`
  ADD PRIMARY KEY (`id_treino_exercicio`),
  ADD KEY `id_treino` (`id_treino`),
  ADD KEY `id_exercicio` (`id_exercicio`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`);

--
-- Índices de tabela `usuarios_pendentes`
--
ALTER TABLE `usuarios_pendentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `verification_token` (`verification_token`);

--
-- AUTO_INCREMENT para tabelas despejadas
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
  MODIFY `id_dieta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `evolucao`
--
ALTER TABLE `evolucao`
  MODIFY `id_evolucao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `exercicio`
--
ALTER TABLE `exercicio`
  MODIFY `id_exercicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de tabela `sessoes_ativas`
--
ALTER TABLE `sessoes_ativas`
  MODIFY `id_sessao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `usuarios_pendentes`
--
ALTER TABLE `usuarios_pendentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `sessoes_ativas`
--
ALTER TABLE `sessoes_ativas`
  ADD CONSTRAINT `sessoes_ativas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
