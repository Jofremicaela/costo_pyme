-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-11-2026 a las 07:53:43
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `calcostos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_auditoria`
--

CREATE TABLE `costobene_auditoria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tabla_afectada` varchar(100) NOT NULL,
  `registro_id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `accion` enum('insert','update','delete') NOT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_auditoria`
--

INSERT INTO `costobene_auditoria` (`id`, `tabla_afectada`, `registro_id`, `usuario_id`, `accion`, `datos_anteriores`, `datos_nuevos`, `fecha_accion`) VALUES
(1, 'costobene_productos', 6, 6, 'insert', NULL, '{\"nombre\":\"gorras\",\"descripcion\":\"gorras para personalizar\",\"foto_path\":\"uploads\\/productos\\/8f3ff3b878d99e7d1e6d9b5679a9237a.jpg\",\"unidad\":\"unidad\",\"moneda\":\"ARS\",\"volumen_esperado\":100,\"margen_porcentaje\":30,\"precio_manual\":1000}', '2026-11-12 02:10:26'),
(2, 'costobene_productos', 7, 6, 'insert', NULL, '{\"nombre\":\"buzos\",\"descripcion\":\"buzos para egresados\",\"foto_path\":\"uploads\\/productos\\/530bd02687285d3e43a395cdecc49c19.jpg\",\"unidad\":\"unidad\",\"moneda\":\"ARS\",\"volumen_esperado\":200,\"margen_porcentaje\":30,\"precio_manual\":3000}', '2026-11-12 02:21:35'),
(3, 'costobene_productos', 6, 6, 'update', '{\"id\":6,\"cliente_id\":4,\"nombre\":\"gorras\",\"descripcion\":\"gorras para personalizar\",\"foto_path\":\"uploads\\/productos\\/8f3ff3b878d99e7d1e6d9b5679a9237a.jpg\",\"unidad\":\"unidad\",\"moneda\":\"ARS\",\"volumen_esperado\":\"100.00\",\"horas_trabajo\":\"0.00\",\"valor_hora\":\"0.00\",\"margen_porcentaje\":\"30.00\",\"precio_manual\":\"1000.00\",\"fecha_creacion\":\"2026-11-11 23:10:26\",\"fecha_actualizacion\":\"2026-11-11 23:10:26\"}', '{\"nombre\":\"gorras\",\"descripcion\":\"gorras para personalizar\",\"foto_path\":\"uploads\\/productos\\/8f3ff3b878d99e7d1e6d9b5679a9237a.jpg\",\"unidad\":\"unidad\",\"moneda\":\"ARS\",\"volumen_esperado\":100,\"margen_porcentaje\":30,\"precio_manual\":2000}', '2026-11-12 06:00:58'),
(4, 'costobene_productos', 8, 6, 'insert', NULL, '{\"nombre\":\"peluqueria\",\"descripcion\":\"peluqueria infantil\",\"foto_path\":null,\"unidad\":\"hora\",\"moneda\":\"ARS\",\"volumen_esperado\":70,\"horas_trabajo\":5,\"valor_hora\":10000,\"margen_porcentaje\":30,\"precio_manual\":null}', '2026-11-12 06:10:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_clientes`
--

CREATE TABLE `costobene_clientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre_empresa` varchar(150) NOT NULL,
  `razon_social` varchar(150) DEFAULT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `rubro` enum('gastronomia','textiles','servicios') DEFAULT NULL,
  `margen_minimo` decimal(5,2) NOT NULL DEFAULT 10.00,
  `email_contacto` varchar(255) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_clientes`
--

INSERT INTO `costobene_clientes` (`id`, `nombre_empresa`, `razon_social`, `cuit`, `rubro`, `margen_minimo`, `email_contacto`, `telefono`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Textiles del Norte', 'Textiles del Norte SA', '30-12345678-9', NULL, 10.00, 'contacto@textilesnorte.com', '+5491123456789', 1, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(2, 'Moda Premium', 'Moda Premium SRL', '30-98765432-1', NULL, 10.00, 'ventas@modapremium.com', '+5491165432187', 1, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(3, 'Kiosco Estilo', 'Kiosco Estilo SA', '30-45678912-3', NULL, 10.00, 'hola@kioscoestilo.com', '+5491156789123', 1, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(4, 'master', NULL, '2132434', NULL, 10.00, 'micaelajofre@gmail.com', NULL, 1, '2026-11-12 02:03:00', '2026-11-12 02:03:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_comisiones`
--

CREATE TABLE `costobene_comisiones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `categoria` enum('marketplace','medio_pago','canal_venta') NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `porcentaje` decimal(7,4) NOT NULL DEFAULT 0.0000,
  `cargo_fijo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `moneda` char(3) NOT NULL DEFAULT 'ARS',
  `iva_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `base_calculo` enum('precio_total','precio_sin_descuento') NOT NULL DEFAULT 'precio_total',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` bigint(20) UNSIGNED NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_configuraciones_app`
--

CREATE TABLE `costobene_configuraciones_app` (
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `periodo_activo` enum('day','week','month','year') NOT NULL DEFAULT 'month',
  `producto_seleccionado_id` bigint(20) UNSIGNED DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'ARS',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `costobene_configuraciones_app`
--

INSERT INTO `costobene_configuraciones_app` (`cliente_id`, `periodo_activo`, `producto_seleccionado_id`, `moneda`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'month', NULL, 'ARS', '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(2, 'month', NULL, 'ARS', '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(3, 'month', NULL, 'ARS', '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(4, 'month', NULL, 'ARS', '2026-11-12 02:03:00', '2026-11-12 02:03:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_costos_producto`
--

CREATE TABLE `costobene_costos_producto` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo_costo` enum('fixed','variable','indirect','labor') NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado_aprobacion` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'aprobado',
  `aprobado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `aprobado_en` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `factura_path` varchar(255) DEFAULT NULL,
  `horas_trabajo` int(10) UNSIGNED DEFAULT NULL,
  `valor_hora` decimal(12,2) DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `costobene_costos_producto`
--

INSERT INTO `costobene_costos_producto` (`id`, `producto_id`, `creado_por`, `tipo_costo`, `nombre`, `monto`, `estado_aprobacion`, `aprobado_por`, `aprobado_en`, `fecha_creacion`, `fecha_actualizacion`, `factura_path`, `horas_trabajo`, `valor_hora`) VALUES
(1, 1, NULL, 'fixed', 'Alquiler taller', 260000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(2, 1, NULL, 'fixed', 'Electricidad y gas', 68000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(3, 1, NULL, 'fixed', 'Maquinarias y mantenimiento', 90000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(4, 1, NULL, 'variable', 'Tela algodón premium', 3200.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(5, 1, NULL, 'variable', 'Tinta y plastisol', 980.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(6, 1, NULL, 'variable', 'Etiquetas y embalaje', 420.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(7, 1, NULL, 'indirect', 'Marketing y redes', 120000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(8, 1, NULL, 'indirect', 'Transporte y delivery', 75000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(9, 2, NULL, 'fixed', 'Alquiler taller', 260000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(10, 2, NULL, 'fixed', 'Servicios', 70000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(11, 2, NULL, 'variable', 'Tela polar', 6100.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(12, 2, NULL, 'variable', 'Hilos y accesorios', 1250.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(13, 2, NULL, 'variable', 'Etiquetas', 500.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(14, 2, NULL, 'indirect', 'Publicidad', 110000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(15, 3, NULL, 'fixed', 'Alquiler taller', 260000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(16, 3, NULL, 'fixed', 'Mantenimiento de máquinas', 95000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(17, 3, NULL, 'variable', 'Tela y forro', 9800.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(18, 3, NULL, 'variable', 'Zíper y cierres', 1700.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(19, 3, NULL, 'variable', 'Etiquetas y embalaje', 700.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(20, 3, NULL, 'indirect', 'Marketing y ventas', 150000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(21, 4, NULL, 'fixed', 'Alquiler local', 210000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(22, 4, NULL, 'fixed', 'Servicios', 55000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(23, 4, NULL, 'variable', 'Tela premium', 2600.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(24, 4, NULL, 'variable', 'Estampado digital', 900.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(25, 4, NULL, 'indirect', 'Publicidad digital', 90000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(26, 5, NULL, 'fixed', 'Alquiler taller', 200000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(27, 5, NULL, 'fixed', 'Mantenimiento', 60000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(28, 5, NULL, 'variable', 'Tela casual', 3000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(29, 5, NULL, 'variable', 'Botones y etiquetas', 600.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(30, 5, NULL, 'indirect', 'Marketing', 85000.00, 'aprobado', NULL, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19', NULL, NULL, NULL),
(31, 6, NULL, 'fixed', 'luz', 1000.00, 'aprobado', NULL, NULL, '2026-11-12 02:17:13', '2026-11-12 02:17:13', NULL, NULL, NULL),
(32, 6, NULL, 'fixed', 'wifi', 20000.00, 'aprobado', NULL, NULL, '2026-11-12 02:17:39', '2026-11-12 02:17:39', NULL, NULL, NULL),
(33, 7, NULL, 'variable', 'limpieza', 2000.00, 'aprobado', NULL, NULL, '2026-11-12 03:37:44', '2026-11-12 03:37:44', NULL, NULL, NULL),
(34, 8, 6, 'fixed', 'dj', 50000.00, 'aprobado', NULL, NULL, '2026-11-12 06:12:53', '2026-11-12 06:12:53', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_costos_productos_compartidos`
--

CREATE TABLE `costobene_costos_productos_compartidos` (
  `costo_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `monto_asignado` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_costos_productos_compartidos`
--

INSERT INTO `costobene_costos_productos_compartidos` (`costo_id`, `producto_id`, `monto_asignado`) VALUES
(33, 6, 1000.00),
(33, 7, 1000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_facturas`
--

CREATE TABLE `costobene_facturas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `suscripcion_id` bigint(20) UNSIGNED NOT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `moneda` char(3) NOT NULL DEFAULT 'ARS',
  `fecha_emision` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_vencimiento` timestamp NULL DEFAULT NULL,
  `estado` enum('pagada','pendiente','vencida','cancelada') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_facturas`
--

INSERT INTO `costobene_facturas` (`id`, `cliente_id`, `suscripcion_id`, `numero_factura`, `monto`, `moneda`, `fecha_emision`, `fecha_vencimiento`, `estado`) VALUES
(1, 1, 1, 'FAC-2026-001', 7500.00, 'ARS', '2026-11-12 01:41:19', '2026-11-19 01:41:19', 'pendiente'),
(2, 2, 2, 'FAC-2026-002', 2500.00, 'ARS', '2026-11-12 01:41:19', '2026-11-19 01:41:19', 'pagada'),
(3, 3, 3, 'FAC-2026-003', 0.00, 'ARS', '2026-11-12 01:41:19', '2026-11-19 01:41:19', 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_invitaciones`
--

CREATE TABLE `costobene_invitaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `rol_id` bigint(20) UNSIGNED NOT NULL,
  `rol` enum('encargado','operador') NOT NULL,
  `codigo_hash` char(64) NOT NULL,
  `creado_por` bigint(20) UNSIGNED NOT NULL,
  `vence_en` datetime NOT NULL,
  `usada_en` datetime DEFAULT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `revocada` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_invitaciones`
--

INSERT INTO `costobene_invitaciones` (`id`, `cliente_id`, `rol_id`, `rol`, `codigo_hash`, `creado_por`, `vence_en`, `usada_en`, `usuario_id`, `revocada`, `fecha_creacion`) VALUES
(1, 4, 5, 'operador', '44bd0f169dbcef6a9ecf82c74abd549aac470d47e8261eb9b5720160cd84d79a', 6, '2026-11-19 02:57:33', NULL, NULL, 1, '2026-11-12 05:57:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_objetivos_periodo_producto`
--

CREATE TABLE `costobene_objetivos_periodo_producto` (
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `periodo` enum('day','week','month','year') NOT NULL,
  `volumen_objetivo` decimal(12,2) NOT NULL DEFAULT 0.00
) ;

--
-- Volcado de datos para la tabla `costobene_objetivos_periodo_producto`
--

INSERT INTO `costobene_objetivos_periodo_producto` (`producto_id`, `periodo`, `volumen_objetivo`) VALUES
(1, 'day', 15.00),
(1, 'week', 80.00),
(1, 'month', 360.00),
(1, 'year', 4320.00),
(2, 'day', 8.00),
(2, 'week', 52.00),
(2, 'month', 220.00),
(2, 'year', 2640.00),
(3, 'day', 5.00),
(3, 'week', 30.00),
(3, 'month', 130.00),
(3, 'year', 1560.00),
(4, 'day', 18.00),
(4, 'week', 90.00),
(4, 'month', 500.00),
(4, 'year', 6000.00),
(5, 'day', 10.00),
(5, 'week', 60.00),
(5, 'month', 280.00),
(5, 'year', 3360.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_permisos`
--

CREATE TABLE `costobene_permisos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_permisos`
--

INSERT INTO `costobene_permisos` (`id`, `nombre`, `descripcion`) VALUES
(1, 'ver_dashboard', 'Puede ver dashboard'),
(2, 'crear_productos', 'Puede crear productos'),
(3, 'editar_costos', 'Puede editar costos'),
(4, 'ver_reportes', 'Puede ver reportes'),
(5, 'gestionar_usuarios', 'Puede gestionar usuarios'),
(6, 'gestionar_planes', 'Puede gestionar planes y suscripciones'),
(7, 'gestionar_clientes', 'Puede gestionar clientes'),
(8, 'ver_facturas', 'Puede ver facturas'),
(9, 'gestionar_comisiones', 'Crear y modificar comisiones'),
(10, 'gestionar_promociones', 'Crear y administrar promociones'),
(11, 'aplicar_promociones', 'Aplicar promociones autorizadas'),
(12, 'aprobar_cargas', 'Aprobar o rechazar cargas de operadores'),
(13, 'cargar_produccion', 'Cargar datos operativos'),
(14, 'editar_cargas_propias', 'Editar cargas propias pendientes'),
(15, 'ver_costos', 'Consultar costos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_planes`
--

CREATE TABLE `costobene_planes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `moneda` char(3) NOT NULL DEFAULT 'ARS',
  `duracion_dias` int(11) NOT NULL DEFAULT 30,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_planes`
--

INSERT INTO `costobene_planes` (`id`, `nombre`, `descripcion`, `precio`, `moneda`, `duracion_dias`, `activo`) VALUES
(1, 'Gratis', 'Acceso limitado', 0.00, 'ARS', 30, 1),
(2, 'Básico', 'Para pequeñas empresas', 2500.00, 'ARS', 30, 1),
(3, 'Profesional', 'Para negocios en crecimiento', 7500.00, 'ARS', 30, 1),
(4, 'Empresarial', 'Para equipos y varios usuarios', 18000.00, 'ARS', 30, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_produccion_mensual_producto`
--

CREATE TABLE `costobene_produccion_mensual_producto` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `anio` smallint(5) UNSIGNED NOT NULL,
  `mes` tinyint(3) UNSIGNED NOT NULL,
  `unidades_vendidas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_promedio_real` decimal(12,2) NOT NULL DEFAULT 0.00,
  `costos_fijos_reales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `costos_variables_reales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `costos_indirectos_reales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `mano_obra_real` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado_aprobacion` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'aprobado',
  `aprobado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `aprobado_en` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_productos`
--

CREATE TABLE `costobene_productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `unidad` varchar(50) NOT NULL DEFAULT 'unidad',
  `moneda` char(3) NOT NULL DEFAULT 'ARS',
  `volumen_esperado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `horas_trabajo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_hora` decimal(12,2) NOT NULL DEFAULT 0.00,
  `margen_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `precio_manual` decimal(12,2) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `costobene_productos`
--

INSERT INTO `costobene_productos` (`id`, `cliente_id`, `nombre`, `descripcion`, `foto_path`, `unidad`, `moneda`, `volumen_esperado`, `horas_trabajo`, `valor_hora`, `margen_porcentaje`, `precio_manual`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 'Remera estampada', NULL, NULL, 'unidad', 'ARS', 360.00, 24.00, 2200.00, 38.00, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(2, 1, 'Buzo de algodón', NULL, NULL, 'unidad', 'ARS', 220.00, 30.00, 2300.00, 35.00, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(3, 1, 'Campera de trabajo', NULL, NULL, 'unidad', 'ARS', 130.00, 36.00, 2500.00, 40.00, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(4, 2, 'Camiseta premium', NULL, NULL, 'unidad', 'ARS', 500.00, 20.00, 2100.00, 40.00, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(5, 3, 'Pantalón casual', NULL, NULL, 'unidad', 'ARS', 280.00, 26.00, 2200.00, 36.00, NULL, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(6, 4, 'gorras', 'gorras para personalizar', 'uploads/productos/8f3ff3b878d99e7d1e6d9b5679a9237a.jpg', 'unidad', 'ARS', 100.00, 0.00, 0.00, 30.00, 2000.00, '2026-11-12 02:10:26', '2026-11-12 06:00:58'),
(7, 4, 'buzos', 'buzos para egresados', 'uploads/productos/530bd02687285d3e43a395cdecc49c19.jpg', 'unidad', 'ARS', 200.00, 0.00, 0.00, 30.00, 3000.00, '2026-11-12 02:21:35', '2026-11-12 02:21:35'),
(8, 4, 'peluqueria', 'peluqueria infantil', NULL, 'hora', 'ARS', 70.00, 5.00, 10000.00, 30.00, NULL, '2026-11-12 06:10:55', '2026-11-12 06:10:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_promociones`
--

CREATE TABLE `costobene_promociones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` enum('porcentaje','fijo','mayorista','combo') NOT NULL,
  `alcance` enum('individual','todos') NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `cantidad_minima` decimal(12,2) DEFAULT NULL,
  `cantidad_bonificada` decimal(12,2) DEFAULT NULL,
  `incluir_futuros` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('programada','activa','vencida','pausada') NOT NULL DEFAULT 'programada',
  `creado_por` bigint(20) UNSIGNED NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_promociones_productos`
--

CREATE TABLE `costobene_promociones_productos` (
  `promocion_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_recordar_sesiones`
--

CREATE TABLE `costobene_recordar_sesiones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `selector` char(24) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_roles`
--

CREATE TABLE `costobene_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_roles`
--

INSERT INTO `costobene_roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'super_administrador', 'Control total de la plataforma'),
(2, 'administrador', 'Gestiona clientes, usuarios y reportes'),
(3, 'encargado', 'Administra productos y costos'),
(4, 'consulta', 'Solo visualiza información'),
(5, 'operador', 'Carga datos operativos con permisos limitados');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_roles_permisos`
--

CREATE TABLE `costobene_roles_permisos` (
  `rol_id` bigint(20) UNSIGNED NOT NULL,
  `permiso_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_roles_permisos`
--

INSERT INTO `costobene_roles_permisos` (`rol_id`, `permiso_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 9),
(3, 10),
(3, 11),
(3, 12),
(3, 13),
(3, 15),
(4, 1),
(4, 4),
(4, 8),
(5, 1),
(5, 3),
(5, 11),
(5, 13),
(5, 14),
(5, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_suscripciones`
--

CREATE TABLE `costobene_suscripciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `estado` enum('activa','vencida','cancelada','pendiente') NOT NULL DEFAULT 'pendiente',
  `fecha_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_fin` timestamp NULL DEFAULT NULL,
  `renovacion_automatica` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_suscripciones`
--

INSERT INTO `costobene_suscripciones` (`id`, `cliente_id`, `plan_id`, `estado`, `fecha_inicio`, `fecha_fin`, `renovacion_automatica`, `fecha_creacion`) VALUES
(1, 1, 3, 'activa', '2026-11-12 01:41:19', '2026-12-12 01:41:19', 1, '2026-11-12 01:41:19'),
(2, 2, 2, 'activa', '2026-11-12 01:41:19', '2026-12-12 01:41:19', 1, '2026-11-12 01:41:19'),
(3, 3, 1, 'activa', '2026-11-12 01:41:19', '2026-12-12 01:41:19', 1, '2026-11-12 01:41:19'),
(4, 4, 1, 'activa', '2026-11-12 02:03:00', '2026-12-12 02:03:00', 0, '2026-11-12 02:03:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_usuarios`
--

CREATE TABLE `costobene_usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_usuarios`
--

INSERT INTO `costobene_usuarios` (`id`, `cliente_id`, `nombre`, `apellido`, `nombre_usuario`, `correo`, `password_hash`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 'Ana', 'García', 'ana_admin', 'ana@textilesnorte.com', '$2y$10$examplehashplaceholder1234567890', 1, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(2, 1, 'Luis', 'Pérez', 'luis_encargado', 'luis@textilesnorte.com', '$2y$10$examplehashplaceholder1234567890', 1, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(3, 2, 'María', 'Lopez', 'maria_consulta', 'maria@modapremium.com', '$2y$10$examplehashplaceholder1234567890', 1, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(4, 3, 'Pedro', 'Smith', 'pedro_admin', 'pedro@kioscoestilo.com', '$2y$10$examplehashplaceholder1234567890', 1, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(5, 1, 'Sofía', 'Ríos', 'sofia_super', 'sofia@costobeneficio.com', '$2y$10$examplehashplaceholder1234567890', 1, '2026-11-12 01:41:19', '2026-11-12 01:41:19'),
(6, 4, 'micaela', 'jofre', 'micaela123_.', 'micaelajofre@gmail.com', '$2y$10$WEPbWRjzYAnbrRp0ZRXmpeJi14eGUUZ/4ivdkEjiht4VzeAepvG3W', 1, '2026-11-12 02:03:00', '2026-11-12 02:03:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `costobene_usuarios_roles`
--

CREATE TABLE `costobene_usuarios_roles` (
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `rol_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `costobene_usuarios_roles`
--

INSERT INTO `costobene_usuarios_roles` (`usuario_id`, `rol_id`) VALUES
(1, 2),
(2, 3),
(3, 4),
(4, 2),
(5, 1),
(6, 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `costobene_auditoria`
--
ALTER TABLE `costobene_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `costobene_idx_auditoria_tabla` (`tabla_afectada`),
  ADD KEY `costobene_idx_auditoria_usuario` (`usuario_id`);

--
-- Indices de la tabla `costobene_clientes`
--
ALTER TABLE `costobene_clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `costobene_comisiones`
--
ALTER TABLE `costobene_comisiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_com_cliente` (`cliente_id`),
  ADD KEY `idx_com_producto` (`producto_id`),
  ADD KEY `fk_com_creador` (`creado_por`);

--
-- Indices de la tabla `costobene_configuraciones_app`
--
ALTER TABLE `costobene_configuraciones_app`
  ADD PRIMARY KEY (`cliente_id`),
  ADD KEY `costobene_fk_configuraciones_app_producto_seleccionado` (`producto_seleccionado_id`);

--
-- Indices de la tabla `costobene_costos_producto`
--
ALTER TABLE `costobene_costos_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `costobene_idx_costos_producto_id` (`producto_id`),
  ADD KEY `costobene_idx_costos_producto_tipo` (`tipo_costo`),
  ADD KEY `costobene_fk_costo_creador` (`creado_por`),
  ADD KEY `costobene_fk_costo_aprobador` (`aprobado_por`);

--
-- Indices de la tabla `costobene_costos_productos_compartidos`
--
ALTER TABLE `costobene_costos_productos_compartidos`
  ADD PRIMARY KEY (`costo_id`,`producto_id`),
  ADD KEY `idx_costos_compartidos_producto` (`producto_id`);

--
-- Indices de la tabla `costobene_facturas`
--
ALTER TABLE `costobene_facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD KEY `costobene_idx_facturas_cliente` (`cliente_id`),
  ADD KEY `costobene_idx_facturas_suscripcion` (`suscripcion_id`);

--
-- Indices de la tabla `costobene_invitaciones`
--
ALTER TABLE `costobene_invitaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_hash` (`codigo_hash`),
  ADD KEY `idx_inv_cliente` (`cliente_id`),
  ADD KEY `fk_inv_rol` (`rol_id`),
  ADD KEY `fk_inv_creador` (`creado_por`),
  ADD KEY `fk_inv_usuario` (`usuario_id`);

--
-- Indices de la tabla `costobene_objetivos_periodo_producto`
--
ALTER TABLE `costobene_objetivos_periodo_producto`
  ADD PRIMARY KEY (`producto_id`,`periodo`);

--
-- Indices de la tabla `costobene_permisos`
--
ALTER TABLE `costobene_permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `costobene_planes`
--
ALTER TABLE `costobene_planes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `costobene_produccion_mensual_producto`
--
ALTER TABLE `costobene_produccion_mensual_producto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `costobene_uq_produccion_producto_mes` (`producto_id`,`anio`,`mes`),
  ADD KEY `fk_produccion_creador` (`creado_por`),
  ADD KEY `fk_produccion_aprobador` (`aprobado_por`);

--
-- Indices de la tabla `costobene_productos`
--
ALTER TABLE `costobene_productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `costobene_uq_cliente_producto_nombre` (`cliente_id`,`nombre`),
  ADD KEY `costobene_idx_productos_cliente_id` (`cliente_id`);

--
-- Indices de la tabla `costobene_promociones`
--
ALTER TABLE `costobene_promociones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_promo_cliente` (`cliente_id`),
  ADD KEY `fk_promo_creador` (`creado_por`);

--
-- Indices de la tabla `costobene_promociones_productos`
--
ALTER TABLE `costobene_promociones_productos`
  ADD PRIMARY KEY (`promocion_id`,`producto_id`),
  ADD KEY `fk_pp_producto` (`producto_id`);

--
-- Indices de la tabla `costobene_recordar_sesiones`
--
ALTER TABLE `costobene_recordar_sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_recordar_selector` (`selector`),
  ADD KEY `idx_recordar_usuario` (`usuario_id`),
  ADD KEY `idx_recordar_expira` (`expires_at`);

--
-- Indices de la tabla `costobene_roles`
--
ALTER TABLE `costobene_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `costobene_roles_permisos`
--
ALTER TABLE `costobene_roles_permisos`
  ADD PRIMARY KEY (`rol_id`,`permiso_id`),
  ADD KEY `costobene_fk_roles_permisos_permiso` (`permiso_id`);

--
-- Indices de la tabla `costobene_suscripciones`
--
ALTER TABLE `costobene_suscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `costobene_idx_suscripciones_cliente` (`cliente_id`),
  ADD KEY `costobene_idx_suscripciones_plan` (`plan_id`);

--
-- Indices de la tabla `costobene_usuarios`
--
ALTER TABLE `costobene_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `costobene_idx_usuarios_cliente_id` (`cliente_id`);

--
-- Indices de la tabla `costobene_usuarios_roles`
--
ALTER TABLE `costobene_usuarios_roles`
  ADD PRIMARY KEY (`usuario_id`,`rol_id`),
  ADD KEY `costobene_fk_usuarios_roles_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `costobene_auditoria`
--
ALTER TABLE `costobene_auditoria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `costobene_clientes`
--
ALTER TABLE `costobene_clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `costobene_comisiones`
--
ALTER TABLE `costobene_comisiones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costobene_costos_producto`
--
ALTER TABLE `costobene_costos_producto`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costobene_facturas`
--
ALTER TABLE `costobene_facturas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `costobene_invitaciones`
--
ALTER TABLE `costobene_invitaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `costobene_permisos`
--
ALTER TABLE `costobene_permisos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `costobene_planes`
--
ALTER TABLE `costobene_planes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `costobene_produccion_mensual_producto`
--
ALTER TABLE `costobene_produccion_mensual_producto`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costobene_productos`
--
ALTER TABLE `costobene_productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costobene_promociones`
--
ALTER TABLE `costobene_promociones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `costobene_recordar_sesiones`
--
ALTER TABLE `costobene_recordar_sesiones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `costobene_roles`
--
ALTER TABLE `costobene_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `costobene_suscripciones`
--
ALTER TABLE `costobene_suscripciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `costobene_usuarios`
--
ALTER TABLE `costobene_usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `costobene_auditoria`
--
ALTER TABLE `costobene_auditoria`
  ADD CONSTRAINT `costobene_fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `costobene_usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_comisiones`
--
ALTER TABLE `costobene_comisiones`
  ADD CONSTRAINT `fk_com_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `costobene_clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_com_creador` FOREIGN KEY (`creado_por`) REFERENCES `costobene_usuarios` (`id`),
  ADD CONSTRAINT `fk_com_producto` FOREIGN KEY (`producto_id`) REFERENCES `costobene_productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `costobene_configuraciones_app`
--
ALTER TABLE `costobene_configuraciones_app`
  ADD CONSTRAINT `costobene_fk_configuraciones_app_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `costobene_clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `costobene_fk_configuraciones_app_producto_seleccionado` FOREIGN KEY (`producto_seleccionado_id`) REFERENCES `costobene_productos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_costos_producto`
--
ALTER TABLE `costobene_costos_producto`
  ADD CONSTRAINT `costobene_fk_costo_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `costobene_usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `costobene_fk_costo_creador` FOREIGN KEY (`creado_por`) REFERENCES `costobene_usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `costobene_fk_costos_producto_producto` FOREIGN KEY (`producto_id`) REFERENCES `costobene_productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_costos_productos_compartidos`
--
ALTER TABLE `costobene_costos_productos_compartidos`
  ADD CONSTRAINT `fk_costos_compartidos_costo` FOREIGN KEY (`costo_id`) REFERENCES `costobene_costos_producto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_costos_compartidos_producto` FOREIGN KEY (`producto_id`) REFERENCES `costobene_productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_facturas`
--
ALTER TABLE `costobene_facturas`
  ADD CONSTRAINT `costobene_fk_facturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `costobene_clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `costobene_fk_facturas_suscripcion` FOREIGN KEY (`suscripcion_id`) REFERENCES `costobene_suscripciones` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_invitaciones`
--
ALTER TABLE `costobene_invitaciones`
  ADD CONSTRAINT `fk_inv_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `costobene_clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_creador` FOREIGN KEY (`creado_por`) REFERENCES `costobene_usuarios` (`id`),
  ADD CONSTRAINT `fk_inv_rol` FOREIGN KEY (`rol_id`) REFERENCES `costobene_roles` (`id`),
  ADD CONSTRAINT `fk_inv_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `costobene_usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `costobene_objetivos_periodo_producto`
--
ALTER TABLE `costobene_objetivos_periodo_producto`
  ADD CONSTRAINT `costobene_fk_objetivos_periodo_producto` FOREIGN KEY (`producto_id`) REFERENCES `costobene_productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_produccion_mensual_producto`
--
ALTER TABLE `costobene_produccion_mensual_producto`
  ADD CONSTRAINT `costobene_fk_produccion_mensual_producto` FOREIGN KEY (`producto_id`) REFERENCES `costobene_productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produccion_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `costobene_usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_produccion_creador` FOREIGN KEY (`creado_por`) REFERENCES `costobene_usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `costobene_productos`
--
ALTER TABLE `costobene_productos`
  ADD CONSTRAINT `costobene_fk_productos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `costobene_clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_promociones`
--
ALTER TABLE `costobene_promociones`
  ADD CONSTRAINT `fk_promo_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `costobene_clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_promo_creador` FOREIGN KEY (`creado_por`) REFERENCES `costobene_usuarios` (`id`);

--
-- Filtros para la tabla `costobene_promociones_productos`
--
ALTER TABLE `costobene_promociones_productos`
  ADD CONSTRAINT `fk_pp_producto` FOREIGN KEY (`producto_id`) REFERENCES `costobene_productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pp_promo` FOREIGN KEY (`promocion_id`) REFERENCES `costobene_promociones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `costobene_recordar_sesiones`
--
ALTER TABLE `costobene_recordar_sesiones`
  ADD CONSTRAINT `fk_recordar_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `costobene_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_roles_permisos`
--
ALTER TABLE `costobene_roles_permisos`
  ADD CONSTRAINT `costobene_fk_roles_permisos_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `costobene_permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `costobene_fk_roles_permisos_rol` FOREIGN KEY (`rol_id`) REFERENCES `costobene_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_suscripciones`
--
ALTER TABLE `costobene_suscripciones`
  ADD CONSTRAINT `costobene_fk_suscripciones_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `costobene_clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `costobene_fk_suscripciones_plan` FOREIGN KEY (`plan_id`) REFERENCES `costobene_planes` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_usuarios`
--
ALTER TABLE `costobene_usuarios`
  ADD CONSTRAINT `costobene_fk_usuarios_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `costobene_clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `costobene_usuarios_roles`
--
ALTER TABLE `costobene_usuarios_roles`
  ADD CONSTRAINT `costobene_fk_usuarios_roles_rol` FOREIGN KEY (`rol_id`) REFERENCES `costobene_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `costobene_fk_usuarios_roles_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `costobene_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
