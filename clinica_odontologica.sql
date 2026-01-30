-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-01-2026 a las 13:25:50
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `clinica_odontologica`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `spebuscar_Paciente` (IN `p_ci` INT(11), IN `p_nombres` VARCHAR(80), IN `p_telefono` VARCHAR(30))   SELECT *
FROM pacientes
WHERE ci = p_ci
AND nombres = p_nombres
AND telefono = p_telefono$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `speditar_Paciente` (IN `p_id_paciente` INT(11), IN `p_ci` VARCHAR(30), IN `p_nombres` VARCHAR(80), IN `p_apellido_paterno` VARCHAR(80), IN `p_apellido_materno` VARCHAR(80), IN `p_fecha_nacimiento` DATE, IN `p_sexo` VARCHAR(15), IN `p_direccion` VARCHAR(150), IN `p_telefono` VARCHAR(30), IN `p_creado_en` DATETIME)   UPDATE pacientes
    SET ci = p_ci,
        nombres = p_nombres,
        apellido_paterno = p_apellido_paterno,
        apellido_materno = p_apellido_materno,
        fecha_nacimiento = p_fecha_nacimiento,
        sexo = p_sexo,
        direccion = p_direccion,
        telefono = p_telefono
    WHERE id_paciente = p_id_paciente$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `speEliminar_Paciente` (IN `p_id_paciente` INT(11))   delete from pacientes where id_paciente=p_id_paciente$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `spinsertar_Paciente` (IN `p_id_paciente` INT(11), IN `p_ci` VARCHAR(30), IN `p_nombres` VARCHAR(80), IN `p_apellido_paterno` VARCHAR(80), IN `p_apellido_materno` VARCHAR(80), IN `p_fecha_nacimiento` DATE, IN `p_sexo` VARCHAR(15), IN `p_direccion` VARCHAR(150), IN `p_telefono` VARCHAR(30))   INSERT INTO pacientes(id_paciente,ci,nombres,apellido_paterno,apellido_materno,fecha_nacimiento,sexo,direccion,telefono)
VALUES(p_id_paciente,p_ci,p_nombres,p_apellido_paterno,p_apellido_materno,p_fecha_nacimiento,p_sexo,p_direccion,p_telefono)$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atenciones`
--

CREATE TABLE `atenciones` (
  `id_atencion` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `fecha_atencion` datetime NOT NULL DEFAULT current_timestamp(),
  `diagnostico` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `atenciones`
--

INSERT INTO `atenciones` (`id_atencion`, `id_cita`, `fecha_atencion`, `diagnostico`, `observaciones`) VALUES
(38, 10, '2026-01-25 14:08:20', 'dolor estomacal', 'necesita cirugia de apendice'),
(39, 13, '2026-01-28 20:50:58', 'recuperado', 'recuperar'),
(40, 11, '2026-01-28 21:12:42', 'dolor de ojos', 'urgencia'),
(41, 14, '2026-01-30 02:07:16', 'bien bueno', 'nice');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id_cita` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `id_odontologo` int(11) NOT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime DEFAULT NULL,
  `estado` enum('PROGRAMADA','CANCELADA','ATENDIDA','NO_ASISTIO') NOT NULL DEFAULT 'PROGRAMADA',
  `motivo` varchar(200) DEFAULT NULL,
  `creada_por` int(11) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id_cita`, `id_paciente`, `id_odontologo`, `fecha_hora_inicio`, `fecha_hora_fin`, `estado`, `motivo`, `creada_por`, `creado_en`) VALUES
(10, 1, 123, '2026-01-23 19:37:17', '2026-01-22 19:37:17', 'ATENDIDA', 'dolor de muelas\r\n', 123, '2026-01-23 19:37:17'),
(11, 1, 1224, '2026-01-25 00:26:24', '2026-01-29 20:26:24', 'ATENDIDA', 'estamos probando nuevamente', 1, '2026-01-24 20:27:02'),
(13, 1, 1224, '2026-01-14 06:00:00', NULL, 'ATENDIDA', 'prueba del codigo de compañeros', 1, '2026-01-27 06:47:40'),
(14, 4, 123, '2026-01-29 17:22:00', '2026-01-29 18:22:00', 'ATENDIDA', 'Extracción Molares', NULL, '2026-01-29 14:21:46'),
(15, 2, 123, '2026-01-30 08:30:00', '2026-01-30 09:00:00', 'PROGRAMADA', 'Extracción Incisivos', NULL, '2026-01-30 01:15:43'),
(16, 3, 1229, '2026-01-30 08:30:00', '2026-01-30 09:00:00', 'PROGRAMADA', 'Extracción Incisivos', NULL, '2026-01-30 02:42:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_atencion`
--

CREATE TABLE `detalle_atencion` (
  `id_detalle` int(11) NOT NULL,
  `id_atencion` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `odontologos`
--

CREATE TABLE `odontologos` (
  `id_odontologo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `matricula` varchar(50) NOT NULL,
  `especialidad` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `odontologos`
--

INSERT INTO `odontologos` (`id_odontologo`, `id_usuario`, `matricula`, `especialidad`) VALUES
(123, 123, '12345', 'odontologo pedriatra'),
(1224, 1, 'nuevo ingreso', 'sirujano'),
(1229, 136, 'MT0111-12551', 'todologo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id_paciente` int(11) NOT NULL,
  `ci` varchar(30) NOT NULL,
  `nombres` varchar(80) NOT NULL,
  `apellido_paterno` varchar(80) NOT NULL,
  `apellido_materno` varchar(80) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `sexo` varchar(15) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `departamento` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id_paciente`, `ci`, `nombres`, `apellido_paterno`, `apellido_materno`, `fecha_nacimiento`, `sexo`, `direccion`, `telefono`, `creado_en`, `departamento`) VALUES
(1, '9691917', 'Ricardo', 'Flores', 'Soruco', '1998-11-22', 'Femenino', 'dorado norte', '75604608', '2026-01-22 12:13:41', 'EX'),
(2, '9691999', 'Adsfjlksj', 'Dfajsdñl', 'Kjdsalfkaj', '1997-01-25', 'Masculino', 'djalñkdsjañlk', '75064888', '2026-01-22 11:56:29', 'SC'),
(3, '987654321', 'Victor', 'Aurelio', 'Serrano', '1997-03-25', 'Femenino', 'otro paciente', '67587098', '2026-01-24 20:25:27', 'CH'),
(4, '123456789', 'Juan Jose', 'Sotelo', 'Garcia', '1998-05-22', 'Masculino', 'lujan', '78121561', '2026-01-27 06:55:41', 'SC');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_atencion` int(11) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `monto` decimal(10,2) NOT NULL,
  `metodo` enum('EFECTIVO','TARJETA','TRANSFERENCIA','CREDITO') NOT NULL,
  `estado` enum('PAGADO','PENDIENTE') NOT NULL DEFAULT 'PAGADO',
  `registrado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_atencion`, `fecha_pago`, `monto`, `metodo`, `estado`, `registrado_por`) VALUES
(1, 40, '2026-01-30 01:31:42', 4800.00, 'TRANSFERENCIA', 'PAGADO', NULL),
(2, 38, '2026-01-30 01:33:42', 500.00, 'CREDITO', 'PAGADO', NULL),
(3, 39, '2026-01-30 01:34:32', 4500.00, 'TARJETA', 'PAGADO', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recibos`
--

CREATE TABLE `recibos` (
  `id_recibo` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `numero_recibo` varchar(50) NOT NULL,
  `fecha_recibo` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `recibos`
--

INSERT INTO `recibos` (`id_recibo`, `id_pago`, `numero_recibo`, `fecha_recibo`, `total`) VALUES
(1, 1, 'R-00001', '2026-01-30 01:31:42', 4800.00),
(2, 2, 'R-00002', '2026-01-30 01:33:42', 500.00),
(3, 3, 'R-00003', '2026-01-30 01:34:32', 4500.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(1, 'ADMIN'),
(3, 'DOCTOR'),
(2, 'RECEPCIONISTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id_servicio` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombres` varchar(80) NOT NULL,
  `apellidos` varchar(80) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `usuario`, `password_hash`, `nombres`, `apellidos`, `email`, `telefono`, `activo`, `creado_en`) VALUES
(1, 3, 'DR roger', '1010', 'roger', 'idalgo', 'rogeridalgo.@gmail.com', '12345678', 2, '2026-01-24 20:23:54'),
(123, 1, 'ADM', '12345', 'Ricardo', 'flores', 'ricardo.gmail.com', '75600084', 1, '2026-01-23 16:58:46'),
(124, 2, 'Recep', '0001', 'julia', 'mano', 'julia@gmail.com', '55555555', 2, '2026-01-27 12:35:12'),
(125, 1, 'damian', '1111', 'daminan', 'zotelo', 'garcia.gamil.com', '15654684', 1, '2026-01-28 09:51:24'),
(134, 3, 'juan', '123', 'Juan gabriel', 'mano', 'juangabriel@gmail.com', '1234589', 1, '2026-01-28 22:02:55'),
(135, 2, 'laura', '7789', 'laura', 'santos', 'gomez@gmail.com', '12151848', 1, '2026-01-28 22:03:46'),
(136, 3, 'hugito', '123', 'hugo fernando', 'contreras armando', 'hugito@gmail.com', '75604408', 1, '2026-01-30 02:35:55');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_login`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_login` (
`usuario` varchar(50)
,`password_hash` varchar(255)
,`NombreUsuario` varchar(80)
,`rol` varchar(30)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_agenda`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_agenda` (
`id_cita` int(11)
,`fecha_hora_inicio` datetime
,`fecha_hora_fin` datetime
,`estado` enum('PROGRAMADA','CANCELADA','ATENDIDA','NO_ASISTIO')
,`paciente` varchar(242)
,`ci_paciente` varchar(30)
,`odontologo` varchar(161)
,`matricula` varchar(50)
,`motivo` varchar(200)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_atencion_clinica`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_atencion_clinica` (
`id_atencion` int(11)
,`fecha_atencion` datetime
,`diagnostico` text
,`observaciones` text
,`id_cita` int(11)
,`fecha_hora_inicio` datetime
,`fecha_hora_fin` datetime
,`estado` enum('PROGRAMADA','CANCELADA','ATENDIDA','NO_ASISTIO')
,`motivo` varchar(200)
,`id_paciente` int(11)
,`ci` varchar(30)
,`nombres` varchar(80)
,`apellido_paterno` varchar(80)
,`apellido_materno` varchar(80)
,`telefono` varchar(30)
,`sexo` varchar(15)
,`fecha_nacimiento` date
,`id_odontologo` int(11)
,`matricula` varchar(50)
,`especialidad` varchar(80)
,`nombre_odontologo` varchar(161)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_atencion_total`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_atencion_total` (
`id_atencion` int(11)
,`fecha_atencion` datetime
,`id_cita` int(11)
,`total_servicios` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_login`
--
DROP TABLE IF EXISTS `vista_login`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_login`  AS SELECT `u`.`usuario` AS `usuario`, `u`.`password_hash` AS `password_hash`, `u`.`nombres` AS `NombreUsuario`, `r`.`nombre` AS `rol` FROM (`usuarios` `u` join `roles` `r` on(`u`.`id_rol` = `r`.`id_rol`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_agenda`
--
DROP TABLE IF EXISTS `vw_agenda`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_agenda`  AS SELECT `c`.`id_cita` AS `id_cita`, `c`.`fecha_hora_inicio` AS `fecha_hora_inicio`, `c`.`fecha_hora_fin` AS `fecha_hora_fin`, `c`.`estado` AS `estado`, concat(`p`.`nombres`,' ',`p`.`apellido_paterno`,' ',ifnull(`p`.`apellido_materno`,'')) AS `paciente`, `p`.`ci` AS `ci_paciente`, concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `odontologo`, `o`.`matricula` AS `matricula`, `c`.`motivo` AS `motivo` FROM (((`citas` `c` join `pacientes` `p` on(`p`.`id_paciente` = `c`.`id_paciente`)) join `odontologos` `o` on(`o`.`id_odontologo` = `c`.`id_odontologo`)) join `usuarios` `u` on(`u`.`id_usuario` = `o`.`id_usuario`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_atencion_clinica`
--
DROP TABLE IF EXISTS `vw_atencion_clinica`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_atencion_clinica`  AS SELECT `a`.`id_atencion` AS `id_atencion`, `a`.`fecha_atencion` AS `fecha_atencion`, `a`.`diagnostico` AS `diagnostico`, `a`.`observaciones` AS `observaciones`, `c`.`id_cita` AS `id_cita`, `c`.`fecha_hora_inicio` AS `fecha_hora_inicio`, `c`.`fecha_hora_fin` AS `fecha_hora_fin`, `c`.`estado` AS `estado`, `c`.`motivo` AS `motivo`, `p`.`id_paciente` AS `id_paciente`, `p`.`ci` AS `ci`, `p`.`nombres` AS `nombres`, `p`.`apellido_paterno` AS `apellido_paterno`, `p`.`apellido_materno` AS `apellido_materno`, `p`.`telefono` AS `telefono`, `p`.`sexo` AS `sexo`, `p`.`fecha_nacimiento` AS `fecha_nacimiento`, `o`.`id_odontologo` AS `id_odontologo`, `o`.`matricula` AS `matricula`, `o`.`especialidad` AS `especialidad`, concat(`u`.`nombres`,' ',`u`.`apellidos`) AS `nombre_odontologo` FROM ((((`atenciones` `a` join `citas` `c` on(`a`.`id_cita` = `c`.`id_cita`)) join `pacientes` `p` on(`c`.`id_paciente` = `p`.`id_paciente`)) join `odontologos` `o` on(`c`.`id_odontologo` = `o`.`id_odontologo`)) join `usuarios` `u` on(`o`.`id_usuario` = `u`.`id_usuario`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_atencion_total`
--
DROP TABLE IF EXISTS `vw_atencion_total`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_atencion_total`  AS SELECT `a`.`id_atencion` AS `id_atencion`, `a`.`fecha_atencion` AS `fecha_atencion`, `a`.`id_cita` AS `id_cita`, sum(`d`.`subtotal`) AS `total_servicios` FROM (`atenciones` `a` left join `detalle_atencion` `d` on(`d`.`id_atencion` = `a`.`id_atencion`)) GROUP BY `a`.`id_atencion`, `a`.`fecha_atencion`, `a`.`id_cita` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `atenciones`
--
ALTER TABLE `atenciones`
  ADD PRIMARY KEY (`id_atencion`),
  ADD UNIQUE KEY `id_cita` (`id_cita`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD UNIQUE KEY `ux_citas_doctor_inicio` (`id_odontologo`,`fecha_hora_inicio`),
  ADD KEY `fk_citas_pacientes` (`id_paciente`),
  ADD KEY `fk_citas_creada_por` (`creada_por`);

--
-- Indices de la tabla `detalle_atencion`
--
ALTER TABLE `detalle_atencion`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_detalle_atencion_servicios` (`id_servicio`),
  ADD KEY `ix_detalle_atencion_id_atencion` (`id_atencion`);

--
-- Indices de la tabla `odontologos`
--
ALTER TABLE `odontologos`
  ADD PRIMARY KEY (`id_odontologo`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD UNIQUE KEY `matricula` (`matricula`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id_paciente`),
  ADD UNIQUE KEY `ci` (`ci`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `fk_pagos_atenciones` (`id_atencion`),
  ADD KEY `fk_pagos_registrado_por` (`registrado_por`);

--
-- Indices de la tabla `recibos`
--
ALTER TABLE `recibos`
  ADD PRIMARY KEY (`id_recibo`),
  ADD UNIQUE KEY `id_pago` (`id_pago`),
  ADD UNIQUE KEY `numero_recibo` (`numero_recibo`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id_servicio`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_usuarios_roles` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `atenciones`
--
ALTER TABLE `atenciones`
  MODIFY `id_atencion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `detalle_atencion`
--
ALTER TABLE `detalle_atencion`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `odontologos`
--
ALTER TABLE `odontologos`
  MODIFY `id_odontologo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1230;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id_paciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `recibos`
--
ALTER TABLE `recibos`
  MODIFY `id_recibo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `atenciones`
--
ALTER TABLE `atenciones`
  ADD CONSTRAINT `fk_atenciones_citas` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `fk_citas_creada_por` FOREIGN KEY (`creada_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_odontologos` FOREIGN KEY (`id_odontologo`) REFERENCES `odontologos` (`id_odontologo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_citas_pacientes` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_atencion`
--
ALTER TABLE `detalle_atencion`
  ADD CONSTRAINT `fk_detalle_atencion_atenciones` FOREIGN KEY (`id_atencion`) REFERENCES `atenciones` (`id_atencion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_atencion_servicios` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id_servicio`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `odontologos`
--
ALTER TABLE `odontologos`
  ADD CONSTRAINT `fk_odontologos_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_atenciones` FOREIGN KEY (`id_atencion`) REFERENCES `atenciones` (`id_atencion`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagos_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `recibos`
--
ALTER TABLE `recibos`
  ADD CONSTRAINT `fk_recibos_pagos` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
