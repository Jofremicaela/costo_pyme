
<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void {
    http_response_code($codigo);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

$archivoGuardado = null;

try {

    // ========================================
    // VALIDAR MÉTODO Y SESIÓN
    // ========================================

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(405, ['error' => 'Método no permitido']);
    }

    if (!usuario()) {
        responder(401, ['error' => 'Sesión vencida']);
    }

    if (!puede('crear_productos')) {
        responder(403, ['error' => 'Tu perfil no puede crear o modificar productos.']);
    }

    // ========================================
    // RECIBIR DATOS
    // ========================================

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $unidad = trim($_POST['unidad'] ?? '');
    $moneda = strtoupper(trim($_POST['moneda'] ?? 'ARS'));

    $volumen = filter_var(
        $_POST['volumen_esperado'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $margen = filter_var(
        $_POST['margen_porcentaje'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $precioTexto = trim($_POST['precio_manual'] ?? '');

    $precio = $precioTexto === ''
        ? null
        : filter_var($precioTexto, FILTER_VALIDATE_FLOAT);

    $horasTexto = trim($_POST['horas_trabajo'] ?? '');
    $valorHoraTexto = trim($_POST['valor_hora'] ?? '');

    $horas = $horasTexto === ''
        ? 0
        : filter_var($horasTexto, FILTER_VALIDATE_FLOAT);

    $valorHora = $valorHoraTexto === ''
        ? 0
        : filter_var($valorHoraTexto, FILTER_VALIDATE_FLOAT);

    if (
        $nombre === '' ||
        $unidad === '' ||
        $moneda === '' ||
        $volumen === false ||
        $volumen === null ||
        $margen === false ||
        $margen === null
    ) {
        responder(400, [
            'error' => 'Completá nombre, unidad, moneda, volumen y margen.'
        ]);
    }

    if (
        !is_numeric($volumen) ||
        $volumen < 0 ||
        $margen < 0 ||
        $margen >= 100 ||
        ($precioTexto !== '' && $precio === false) ||
        ($precio !== null && $precio < 0) ||
        $horas === false ||
        $horas < 0 ||
        $valorHora === false ||
        $valorHora < 0
    ) {
        responder(400, [
            'error' => 'Hay valores numéricos inválidos.'
        ]);
    }

    if (!preg_match('/^[A-Z]{3}$/', $moneda)) {
        responder(400, [
            'error' => 'La moneda debe ser un código de tres letras, por ejemplo ARS.'
        ]);
    }

    // ========================================
    // VALIDAR IMAGEN
    // ========================================

    $fotoPath = null;

    if (isset($_FILES['imagen']) &&
        $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {

        $imagen = $_FILES['imagen'];

        if ($imagen['error'] !== UPLOAD_ERR_OK) {
            responder(400, [
                'error' => 'No se pudo recibir la imagen. Código: ' . $imagen['error']
            ]);
        }

        if ($imagen['size'] > 3 * 1024 * 1024) {
            responder(400, [
                'error' => 'La imagen no puede superar los 3 MB.'
            ]);
        }

        if (!is_uploaded_file($imagen['tmp_name'])) {
            responder(400, [
                'error' => 'Archivo de imagen inválido.'
            ]);
        }

        $info = @getimagesize($imagen['tmp_name']);

        if ($info === false) {
            responder(400, [
                'error' => 'El archivo seleccionado no es una imagen válida.'
            ]);
        }

        $mime = $info['mime'] ?? '';

        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($extensiones[$mime])) {
            responder(400, [
                'error' => 'Solo se permiten imágenes JPG, PNG o WEBP.'
            ]);
        }

        // ========================================
        // CREAR CARPETA
        // ========================================

        $directorio = __DIR__ . '/uploads/productos/';

        if (
            !is_dir($directorio) &&
            !mkdir($directorio, 0755, true)
        ) {
            responder(500, [
                'error' => 'No se pudo crear la carpeta de imágenes.'
            ]);
        }

        if (!is_writable($directorio)) {
            responder(500, [
                'error' => 'La carpeta de imágenes no tiene permisos de escritura.'
            ]);
        }

        // Nombre aleatorio para evitar sobrescrituras.
        $nombreArchivo = bin2hex(random_bytes(16))
            . '.' . $extensiones[$mime];

        $destino = $directorio . $nombreArchivo;

        if (!move_uploaded_file($imagen['tmp_name'], $destino)) {
            responder(500, [
                'error' => 'No se pudo guardar la imagen.'
            ]);
        }

        $archivoGuardado = $destino;

        $fotoPath = 'uploads/productos/' . $nombreArchivo;
    }

    // ========================================
    // GUARDAR PRODUCTO EN MYSQL
    // ========================================

    $sql = '
        INSERT INTO costobene_productos (
            cliente_id,
            nombre,
            descripcion,
            foto_path,
            unidad,
            moneda,
            volumen_esperado,
            horas_trabajo,
            valor_hora,
            margen_porcentaje,
            precio_manual,
            fecha_creacion,
            fecha_actualizacion
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
        )
    ';

    $st = db()->prepare($sql);

    $st->execute([
        clienteActual(),
        $nombre,
        $descripcion !== '' ? $descripcion : null,
        $fotoPath,
        $unidad,
        $moneda,
        $volumen,
        $horas,
        $valorHora,
        $margen,
        $precio
    ]);

    $id = (int) db()->lastInsertId();

    // ========================================
    // RESPUESTA
    // ========================================

    responder(201, [
        'ok' => true,
        'mensaje' => 'Producto o servicio guardado correctamente.',
        'id' => $id,
        'foto_path' => $fotoPath
    ]);

} catch (Throwable $e) {

    // Si falla MySQL, no dejar una imagen huérfana.
    if ($archivoGuardado && is_file($archivoGuardado)) {
        @unlink($archivoGuardado);
    }

    error_log('guardar_producto.php: ' . $e->getMessage());

    responder(500, [
        'error' => 'No se pudo guardar el producto o servicio.'
    ]);
}
