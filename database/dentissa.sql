-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-07-2026 a las 07:20:21
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dentissa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `paciente_id` char(36) NOT NULL,
  `dentista_id` char(36) NOT NULL,
  `servicio_id` char(36) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `duracion_min` int(11) NOT NULL DEFAULT 30,
  `estado` enum('agendada','confirmada','en_curso','completada','cancelada','no_asistio') NOT NULL DEFAULT 'agendada',
  `notas_paciente` text DEFAULT NULL,
  `notas_internas` text DEFAULT NULL,
  `recordatorio_enviado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_por` char(36) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `paciente_id`, `dentista_id`, `servicio_id`, `fecha_hora`, `duracion_min`, `estado`, `notas_paciente`, `notas_internas`, `recordatorio_enviado`, `creado_por`, `creado_en`, `actualizado_en`) VALUES
('10f4291d-df6f-412d-897f-9fd48ccb887c', '33feb3ab-f2a4-4df6-90c2-bba0b97449ab', 'f59c6da2-936f-4133-a70c-9c4e816712eb', '37fe6d1b-24e3-11f1-8967-0045e2e7d554', '2026-04-17 09:00:00', 45, 'completada', NULL, NULL, 0, '3800c172-24e3-11f1-8967-0045e2e7d554', '2026-04-15 16:57:39', '2026-05-15 03:17:54'),
('ed72428a-c0c7-4def-8703-9c68b504ee3a', '49388466-9e43-48ee-9948-64f94f681d28', 'f59c6da2-936f-4133-a70c-9c4e816712eb', '37fe6d89-24e3-11f1-8967-0045e2e7d554', '2026-03-24 09:00:00', 60, 'agendada', NULL, NULL, 0, '3800c172-24e3-11f1-8967-0045e2e7d554', '2026-03-23 14:59:53', '2026-03-23 14:59:53');

--
-- Disparadores `citas`
--
DELIMITER $$
CREATE TRIGGER `trg_historial_cita` AFTER UPDATE ON `citas` FOR EACH ROW BEGIN
  IF OLD.estado <> NEW.estado THEN
    INSERT INTO historial_citas (id, cita_id, estado_anterior, estado_nuevo)
    VALUES (UUID(), NEW.id, OLD.estado, NEW.estado);
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expedientes`
--

CREATE TABLE `expedientes` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `paciente_id` char(36) NOT NULL,
  `cita_id` char(36) DEFAULT NULL,
  `dentista_id` char(36) NOT NULL,
  `diagnostico` text NOT NULL,
  `tratamiento` text DEFAULT NULL,
  `receta` text DEFAULT NULL,
  `archivos_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`archivos_url`)),
  `observaciones` text DEFAULT NULL,
  `proxima_visita` date DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_citas`
--

CREATE TABLE `historial_citas` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `cita_id` char(36) NOT NULL,
  `cambiado_por` char(36) DEFAULT NULL,
  `estado_anterior` enum('agendada','confirmada','en_curso','completada','cancelada','no_asistio') DEFAULT NULL,
  `estado_nuevo` enum('agendada','confirmada','en_curso','completada','cancelada','no_asistio') DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_citas`
--

INSERT INTO `historial_citas` (`id`, `cita_id`, `cambiado_por`, `estado_anterior`, `estado_nuevo`, `motivo`, `fecha`) VALUES
('a9c14e99-500c-11f1-b98d-0045e2e7d554', '10f4291d-df6f-412d-897f-9fd48ccb887c', NULL, 'agendada', 'completada', NULL, '2026-05-14 21:17:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_dentista`
--

CREATE TABLE `horarios_dentista` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `personal_id` char(36) NOT NULL,
  `dia_semana` tinyint(4) NOT NULL COMMENT '1=Lun 7=Dom',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `usuario_id` char(36) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `contacto_emergencia` varchar(200) DEFAULT NULL,
  `telefono_emergencia` varchar(20) DEFAULT NULL,
  `tipo_sangre` varchar(5) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `enfermedades_cronicas` text DEFAULT NULL,
  `medicamentos_actuales` text DEFAULT NULL,
  `notas_medicas` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `usuario_id`, `fecha_nacimiento`, `genero`, `direccion`, `ciudad`, `contacto_emergencia`, `telefono_emergencia`, `tipo_sangre`, `alergias`, `enfermedades_cronicas`, `medicamentos_actuales`, `notas_medicas`, `creado_en`) VALUES
('33feb3ab-f2a4-4df6-90c2-bba0b97449ab', 'cf3cac86-2ad6-44b1-aa8b-5fd042c6f3e6', '2005-07-07', 'femenino', 'higuera', 'colima', 'gerardo', '7551027064', 'A+', 'penicilina', NULL, NULL, NULL, '2026-03-23 00:59:35'),
('49388466-9e43-48ee-9948-64f94f681d28', '85f42207-a41c-4389-9498-79884c50453d', '2005-11-15', 'masculino', 'sn', 'san jose ixtapa', 'gerardo', '7551027064', 'A+', NULL, NULL, NULL, NULL, '2026-03-22 03:20:45'),
('7877a761-6769-43bb-939a-b75823374ec8', '50e338a4-b07b-4cac-bd14-9379baaa865b', '2004-01-04', 'otro', 'cuahutemoc', 'zihuayork', 'mi mamá', '1234567890', 'O+', 'al camarón', 'rodilla chueca', NULL, NULL, '2026-04-13 14:11:54'),
('8e0ee4ba-38da-4036-85b8-9dabf46eaa21', '62234c5e-0ea8-4d48-a402-e38b97fb91aa', '2000-01-02', 'femenino', 'sn', 'san jose ixtapa', 'mi mamá', '1234567890', 'A+', NULL, NULL, NULL, NULL, '2026-04-15 17:02:28'),
('d798c70b-3d4f-4c38-8f87-4718a8861479', 'c14b4a9e-38eb-43be-893f-379aad7bafe9', '2004-01-14', 'masculino', 'primer paso', 'zihuayork', 'mi mami', '1234567890', 'A-', NULL, NULL, NULL, NULL, '2026-04-13 14:14:16'),
('eb27c783-7f76-4dd6-8d1a-402d32f347ee', '23cd914b-27af-46e0-9cdf-77d4a4664c16', '2005-12-27', 'femenino', 'sn', 'irapuato', 'mi mamá', '123213123123', 'A+', NULL, NULL, NULL, NULL, '2026-05-23 02:51:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal`
--

CREATE TABLE `personal` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `usuario_id` char(36) NOT NULL,
  `tipo` enum('dentista','asistente','administrador') NOT NULL,
  `especialidad` varchar(150) DEFAULT NULL,
  `numero_cedula` varchar(50) DEFAULT NULL,
  `universidad` varchar(150) DEFAULT NULL,
  `anios_experiencia` int(11) NOT NULL DEFAULT 0,
  `biografia` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personal`
--

INSERT INTO `personal` (`id`, `usuario_id`, `tipo`, `especialidad`, `numero_cedula`, `universidad`, `anios_experiencia`, `biografia`, `creado_en`) VALUES
('f59c6da2-936f-4133-a70c-9c4e816712eb', '4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'dentista', NULL, NULL, NULL, 0, NULL, '2026-03-23 01:30:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `duracion_min` int(11) NOT NULL DEFAULT 30,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `color_agenda` varchar(7) NOT NULL DEFAULT '#F4C0D1',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `nombre`, `descripcion`, `categoria`, `duracion_min`, `precio`, `color_agenda`, `activo`, `creado_en`) VALUES
('37fe6a0e-24e3-11f1-8967-0045e2e7d554', 'Consulta general', 'Revisión y diagnóstico completo', 'Preventivo', 30, 400.00, '#F4C0D1', 1, '2026-03-20 23:02:54'),
('37fe6c10-24e3-11f1-8967-0045e2e7d554', 'Limpieza dental', 'Profilaxis y pulido coronal', 'Preventivo', 45, 600.00, '#FBEAF0', 1, '2026-03-20 23:02:54'),
('37fe6d1b-24e3-11f1-8967-0045e2e7d554', 'Extracción simple', 'Extracción de pieza sin complicaciones', 'Cirugía', 45, 800.00, '#ED93B1', 1, '2026-03-20 23:02:54'),
('37fe6d89-24e3-11f1-8967-0045e2e7d554', 'Extracción de muela', 'Extracción de tercer molar', 'Cirugía', 60, 1500.00, '#D4537E', 1, '2026-03-20 23:02:54'),
('37fe6ddf-24e3-11f1-8967-0045e2e7d554', 'Resina dental', 'Restauración con resina fotopolimerizable', 'Restauración', 60, 900.00, '#F4C0D1', 1, '2026-03-20 23:02:54'),
('37fe6e3f-24e3-11f1-8967-0045e2e7d554', 'Endodoncia unirradicular', 'Tratamiento de conducto 1 raíz', 'Endodoncia', 90, 2500.00, '#993556', 1, '2026-03-20 23:02:54'),
('37fe6e90-24e3-11f1-8967-0045e2e7d554', 'Endodoncia multirradicular', 'Tratamiento de conducto múltiples raíces', 'Endodoncia', 120, 3800.00, '#993556', 1, '2026-03-20 23:02:54'),
('37fe6edf-24e3-11f1-8967-0045e2e7d554', 'Blanqueamiento dental', 'Blanqueamiento con luz LED en consultorio', 'Estética', 90, 2800.00, '#FBEAF0', 1, '2026-03-20 23:02:54'),
('37fe6f35-24e3-11f1-8967-0045e2e7d554', 'Consulta de ortodoncia', 'Evaluación para tratamiento de brackets', 'Ortodoncia', 45, 500.00, '#F4C0D1', 1, '2026-03-20 23:02:54'),
('37fe6f80-24e3-11f1-8967-0045e2e7d554', 'Colocación de brackets', 'Aparatología fija metálica completa', 'Ortodoncia', 90, 18000.00, '#ED93B1', 1, '2026-03-20 23:02:54'),
('37fe6fce-24e3-11f1-8967-0045e2e7d554', 'Revisión de ortodoncia', 'Ajuste mensual de aparatología fija', 'Ortodoncia', 30, 600.00, '#F4C0D1', 1, '2026-03-20 23:02:54'),
('37fe7019-24e3-11f1-8967-0045e2e7d554', 'Radiografía periapical', 'Radiografía digital de zona específica', 'Diagnóstico', 15, 200.00, '#FBEAF0', 1, '2026-03-20 23:02:54'),
('37fe7067-24e3-11f1-8967-0045e2e7d554', 'Radiografía panorámica', 'Ortopantomografía completa', 'Diagnóstico', 20, 600.00, '#FBEAF0', 1, '2026-03-20 23:02:54'),
('37fe70b2-24e3-11f1-8967-0045e2e7d554', 'Sellador de fosetas', 'Sellado preventivo en molares', 'Preventivo', 30, 400.00, '#FBEAF0', 1, '2026-03-20 23:02:54'),
('37fe7100-24e3-11f1-8967-0045e2e7d554', 'Corona dental', 'Prótesis fija cerámica o metal-cerámica', 'Prostodoncia', 90, 5500.00, '#D4537E', 1, '2026-03-20 23:02:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('jOHE1Zn8R0k3ABQSuQTTW2PC7Ha6r99e1PD8UCi5', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJsOGNSUmNuRDRhVXJ0cXFpWnZreUdKeG5ocWZGTWQ2cmtPUEd2bk40IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDBcL2Rhc2hib2FyZCJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDBcL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1774130729);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` text NOT NULL,
  `rol` enum('cliente','asistente','dentista','admin') NOT NULL DEFAULT 'cliente',
  `telefono` varchar(20) DEFAULT NULL,
  `foto_url` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password_hash`, `rol`, `telefono`, `foto_url`, `activo`, `creado_en`, `actualizado_en`) VALUES
('23cd914b-27af-46e0-9cdf-77d4a4664c16', 'getsemani', 'blancas contreras', 'gits.qchit@gmail.com', '$2y$12$3XS44U4/6WPCdQ2xPyodH.QIU3N1jLz4pWWPvIp/Fc6StMv/avuha', 'cliente', '462 308 4120', NULL, 1, '2026-05-23 02:51:38', '2026-05-23 02:51:38'),
('3800c172-24e3-11f1-8967-0045e2e7d554', 'Admin', 'Sistema', 'admin@clinica.com', '$2y$12$i61TZ7v9bFBuq.9/JLDZyOPVWbc./hhJWcyMZE4cEHP6WyOs1YsZy', 'admin', '555-0001', NULL, 1, '2026-03-20 23:02:54', '2026-04-08 16:33:02'),
('4dbe9e97-99c8-4aff-bf12-cdf4186237fa', 'melissa', 'lopez', 'melissa@gmail.com', '$2y$12$.4BbI3zMazup3JjiW8mjY.Y1XuuaiitONbn11MoAosJvKSOTZD8sm', 'dentista', '755 7607 8976', NULL, 1, '2026-03-23 01:30:52', '2026-03-23 01:30:52'),
('50e338a4-b07b-4cac-bd14-9379baaa865b', 'fernanda', 'perez', '12ferpe@gmail.com', '$2y$12$dOJE2XEwyVDSnCXfVwVEXelyA5vcR3pBsKp48HzwC2XEYAn7/3r8m', 'cliente', '1234567890', NULL, 1, '2026-04-13 14:11:54', '2026-04-13 14:11:54'),
('62234c5e-0ea8-4d48-a402-e38b97fb91aa', 'kenia', 'santos', 'kenia@gmail.com', '$2y$12$ghvFshCGd5JLzg0HOQeh4epJWe/eyAZo8Nczdup6b9wlKR1KMCkgC', 'cliente', '1234567890', NULL, 1, '2026-04-15 17:02:28', '2026-04-15 17:02:28'),
('85f42207-a41c-4389-9498-79884c50453d', 'gerardo', 'sabalsa', 'gerardoleyvasabalsa960@gmail.com', '$2y$12$qQ1QftL.01xKxmhpKPsF4.L.OoYrMQ8rD1pxUf1/zPv3w0Q2oUx1W', 'cliente', '7551027064', NULL, 1, '2026-03-22 03:20:45', '2026-03-22 03:20:45'),
('c14b4a9e-38eb-43be-893f-379aad7bafe9', 'pancracia', 'valdovinos', 'penval1@gmail.com', '$2y$12$iPVkj9SrpFAWRvAwKf8dGOBAF4hziW5qHJFCQiTSMUUaUgUGbjI4S', 'cliente', '1234567890', NULL, 0, '2026-04-13 14:14:16', '2026-04-15 16:17:37'),
('cf3cac86-2ad6-44b1-aa8b-5fd042c6f3e6', 'sheccid ariadne', 'perez', 'sheccid@gmail.com', '$2y$12$c3bkvivoJ6qwd2eu0DMfo.yAZcW9anAdV3jU8Qhu6id8LbnT062HK', 'cliente', '755 521 9870', NULL, 1, '2026-03-23 00:59:35', '2026-03-23 00:59:35');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_citas_hoy`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_citas_hoy` (
`id` char(36)
,`fecha_hora` datetime
,`estado` enum('agendada','confirmada','en_curso','completada','cancelada','no_asistio')
,`duracion_min` int(11)
,`paciente` varchar(201)
,`tel_paciente` varchar(20)
,`dentista` varchar(201)
,`servicio` varchar(150)
,`precio` decimal(10,2)
,`notas_paciente` text
,`notas_internas` text
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_historial_paciente`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_historial_paciente` (
`cita_id` char(36)
,`paciente_id` char(36)
,`fecha_hora` datetime
,`estado` enum('agendada','confirmada','en_curso','completada','cancelada','no_asistio')
,`servicio` varchar(150)
,`precio` decimal(10,2)
,`dentista` varchar(201)
,`diagnostico` text
,`tratamiento` text
,`proxima_visita` date
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_citas_hoy`
--
DROP TABLE IF EXISTS `v_citas_hoy`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_citas_hoy`  AS SELECT `c`.`id` AS `id`, `c`.`fecha_hora` AS `fecha_hora`, `c`.`estado` AS `estado`, `c`.`duracion_min` AS `duracion_min`, concat(`up`.`nombre`,' ',`up`.`apellido`) AS `paciente`, `up`.`telefono` AS `tel_paciente`, concat(`ud`.`nombre`,' ',`ud`.`apellido`) AS `dentista`, `s`.`nombre` AS `servicio`, `s`.`precio` AS `precio`, `c`.`notas_paciente` AS `notas_paciente`, `c`.`notas_internas` AS `notas_internas` FROM (((((`citas` `c` join `pacientes` `pa` on(`c`.`paciente_id` = `pa`.`id`)) join `usuarios` `up` on(`pa`.`usuario_id` = `up`.`id`)) join `personal` `pe` on(`c`.`dentista_id` = `pe`.`id`)) join `usuarios` `ud` on(`pe`.`usuario_id` = `ud`.`id`)) left join `servicios` `s` on(`c`.`servicio_id` = `s`.`id`)) WHERE cast(`c`.`fecha_hora` as date) = curdate() ORDER BY `c`.`fecha_hora` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_historial_paciente`
--
DROP TABLE IF EXISTS `v_historial_paciente`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_historial_paciente`  AS SELECT `c`.`id` AS `cita_id`, `c`.`paciente_id` AS `paciente_id`, `c`.`fecha_hora` AS `fecha_hora`, `c`.`estado` AS `estado`, `s`.`nombre` AS `servicio`, `s`.`precio` AS `precio`, concat(`ud`.`nombre`,' ',`ud`.`apellido`) AS `dentista`, `e`.`diagnostico` AS `diagnostico`, `e`.`tratamiento` AS `tratamiento`, `e`.`proxima_visita` AS `proxima_visita` FROM (((((`citas` `c` join `pacientes` `pa` on(`c`.`paciente_id` = `pa`.`id`)) left join `servicios` `s` on(`c`.`servicio_id` = `s`.`id`)) join `personal` `pe` on(`c`.`dentista_id` = `pe`.`id`)) join `usuarios` `ud` on(`pe`.`usuario_id` = `ud`.`id`)) left join `expedientes` `e` on(`e`.`cita_id` = `c`.`id`)) ORDER BY `c`.`fecha_hora` DESC ;

-- --------------------------------------------------------

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cita_servicio` (`servicio_id`),
  ADD KEY `fk_cita_creador` (`creado_por`),
  ADD KEY `idx_citas_paciente` (`paciente_id`),
  ADD KEY `idx_citas_dentista` (`dentista_id`),
  ADD KEY `idx_citas_fecha` (`fecha_hora`),
  ADD KEY `idx_citas_estado` (`estado`),
  ADD KEY `idx_citas_fecha_estado` (`fecha_hora`,`estado`);

--
-- Indices de la tabla `expedientes`
--
ALTER TABLE `expedientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exp_cita` (`cita_id`),
  ADD KEY `fk_exp_dentista` (`dentista_id`),
  ADD KEY `idx_exp_paciente` (`paciente_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `historial_citas`
--
ALTER TABLE `historial_citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_historial_usuario` (`cambiado_por`),
  ADD KEY `idx_historial_cita` (`cita_id`);

--
-- Indices de la tabla `horarios_dentista`
--
ALTER TABLE `horarios_dentista`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_horario_dentista_dia` (`personal_id`,`dia_semana`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indices de la tabla `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pacientes_usuario` (`usuario_id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_personal_usuario` (`usuario_id`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_rol` (`rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `fk_cita_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cita_dentista` FOREIGN KEY (`dentista_id`) REFERENCES `personal` (`id`),
  ADD CONSTRAINT `fk_cita_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `fk_cita_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `expedientes`
--
ALTER TABLE `expedientes`
  ADD CONSTRAINT `fk_exp_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_exp_dentista` FOREIGN KEY (`dentista_id`) REFERENCES `personal` (`id`),
  ADD CONSTRAINT `fk_exp_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`);

--
-- Filtros para la tabla `historial_citas`
--
ALTER TABLE `historial_citas`
  ADD CONSTRAINT `fk_historial_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`cambiado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `horarios_dentista`
--
ALTER TABLE `horarios_dentista`
  ADD CONSTRAINT `fk_horario_personal` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `fk_pacientes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `fk_personal_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
