<?php
require_once __DIR__ . '/config.php';

function csrfToken(string $clave = 'csrf_general'): string {
    if (empty($_SESSION[$clave])) $_SESSION[$clave] = bin2hex(random_bytes(32));
    return $_SESSION[$clave];
}

function validarCsrf(string $clave = 'csrf_general'): void {
    $recibido = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION[$clave]) || !hash_equals($_SESSION[$clave], $recibido)) {
        throw new RuntimeException('La sesión del formulario venció. Actualizá la página e intentá nuevamente.');
    }
}

function registrarAuditoriaProducto(int $productoId, string $accion, ?array $anteriores, ?array $nuevos): void {
    $st = db()->prepare('INSERT INTO costobene_auditoria
        (tabla_afectada, registro_id, usuario_id, accion, datos_anteriores, datos_nuevos)
        VALUES (?, ?, ?, ?, ?, ?)');
    $st->execute([
        'costobene_productos', $productoId, (int)(usuario()['id'] ?? 0) ?: null, $accion,
        $anteriores ? json_encode($anteriores, JSON_UNESCAPED_UNICODE) : null,
        $nuevos ? json_encode($nuevos, JSON_UNESCAPED_UNICODE) : null
    ]);
}

function subirArchivoSeguro(array $archivo, string $subcarpeta, array $tipos, int $maxBytes): ?string {
    if (!isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($archivo['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('No se pudo cargar el archivo. Revisá el tamaño e intentá nuevamente.');
    if (($archivo['size'] ?? 0) > $maxBytes) throw new RuntimeException('El archivo supera el tamaño máximo permitido.');
    if (!is_uploaded_file($archivo['tmp_name'])) throw new RuntimeException('El archivo recibido no es válido.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    if (!isset($tipos[$mime])) throw new RuntimeException('El formato del archivo no está permitido.');
    if (str_starts_with($mime, 'image/') && @getimagesize($archivo['tmp_name']) === false) {
        throw new RuntimeException('El archivo de imagen no es válido.');
    }
    $dir = __DIR__ . '/uploads/' . $subcarpeta;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('No se pudo crear la carpeta de archivos.');
    $nombre = bin2hex(random_bytes(16)) . '.' . $tipos[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], $dir . '/' . $nombre)) throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
    return 'uploads/' . $subcarpeta . '/' . $nombre;
}


function rolActual(): string {
    $roles = usuario()['roles'] ?? [];
    foreach (['super_administrador', 'administrador', 'encargado', 'operador', 'consulta'] as $rol) {
        if (in_array($rol, $roles, true)) return $rol;
    }
    return '';
}

function esOperador(): bool { return rolActual() === 'operador'; }
function puedeGestionarComisiones(): bool { return puede('gestionar_comisiones'); }
function puedeGestionarPromociones(): bool { return puede('gestionar_promociones'); }

function validarCuitArgentina(string $cuit): bool {
    $n = preg_replace('/\D+/', '', $cuit);
    if (strlen($n) !== 11) return false;
    $coef = [5,4,3,2,7,6,5,4,3,2]; $sum = 0;
    for ($i=0; $i<10; $i++) $sum += (int)$n[$i] * $coef[$i];
    $resto = $sum % 11; $verificador = 11 - $resto;
    if ($verificador === 11) $verificador = 0; elseif ($verificador === 10) $verificador = 9;
    return $verificador === (int)$n[10];
}


function monedaSimbolo(string $moneda): string {
    return ['ARS' => '$', 'USD' => 'US$', 'EUR' => '€'][$moneda] ?? $moneda;
}

function esAdministrador(): bool {
    $roles = usuario()['roles'] ?? [];
    return in_array('administrador', $roles, true) || in_array('super_administrador', $roles, true);
}

/* Autenticación persistente: se guarda un hash del token, nunca la contraseña. */
function cookieRecordar(string $valor, int $vence): void {
    setcookie('costopyme_recordar', $valor, [
        'expires' => $vence,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function limpiarCookieRecordar(): void {
    cookieRecordar('', time() - 3600);
    unset($_COOKIE['costopyme_recordar']);
}

function eliminarTokenRecordadoActual(): void {
    $valor = (string)($_COOKIE['costopyme_recordar'] ?? '');
    if (preg_match('/^([a-f0-9]{24}):[a-f0-9]{64}$/', $valor, $m)) {
        try {
            $st = db()->prepare('DELETE FROM costobene_recordar_sesiones WHERE selector = ?');
            $st->execute([$m[1]]);
        } catch (Throwable $e) {
            error_log('No se pudo revocar el token recordado: ' . $e->getMessage());
        }
    }
    limpiarCookieRecordar();
}

function crearTokenRecordado(int $usuarioId): void {
    eliminarTokenRecordadoActual();
    $selector = bin2hex(random_bytes(12));
    $validador = bin2hex(random_bytes(32));
    $vence = time() + (30 * 24 * 60 * 60);
    $st = db()->prepare('INSERT INTO costobene_recordar_sesiones (usuario_id, selector, token_hash, expires_at, created_at, last_used_at) VALUES (?, ?, ?, FROM_UNIXTIME(?), NOW(), NOW())');
    $st->execute([$usuarioId, $selector, hash('sha256', $validador), $vence]);
    cookieRecordar($selector . ':' . $validador, $vence);
}

function cargarUsuarioParaSesion(int $usuarioId): ?array {
    $st = db()->prepare('SELECT u.id, u.cliente_id, u.nombre
        FROM costobene_usuarios u
        JOIN costobene_clientes c ON c.id = u.cliente_id
        WHERE u.id = ? AND u.activo = 1 AND c.activo = 1 LIMIT 1');
    $st->execute([$usuarioId]);
    $u = $st->fetch();
    if (!$u) return null;

    $r = db()->prepare('SELECT r.nombre FROM costobene_usuarios_roles ur
        JOIN costobene_roles r ON r.id = ur.rol_id WHERE ur.usuario_id = ?');
    $r->execute([$usuarioId]);
    $roles = $r->fetchAll(PDO::FETCH_COLUMN);

    $p = db()->prepare('SELECT DISTINCT p.nombre FROM costobene_usuarios_roles ur
        JOIN costobene_roles_permisos rp ON rp.rol_id = ur.rol_id
        JOIN costobene_permisos p ON p.id = rp.permiso_id WHERE ur.usuario_id = ?');
    $p->execute([$usuarioId]);
    $permisos = $p->fetchAll(PDO::FETCH_COLUMN);
    if (!$roles || !$permisos) return null;

    return [
        'id' => (int)$u['id'],
        'cliente_id' => (int)$u['cliente_id'],
        'nombre' => $u['nombre'],
        'roles' => $roles,
        'permisos' => $permisos
    ];
}

function restaurarSesionRecordada(): void {
    if (usuario()) return;
    $valor = (string)($_COOKIE['costopyme_recordar'] ?? '');
    if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $valor, $m)) return;

    try {
        $st = db()->prepare('SELECT id, usuario_id, token_hash, UNIX_TIMESTAMP(expires_at) AS vence
            FROM costobene_recordar_sesiones WHERE selector = ? LIMIT 1');
        $st->execute([$m[1]]);
        $token = $st->fetch();
        if (!$token || (int)$token['vence'] < time() || !hash_equals((string)$token['token_hash'], hash('sha256', $m[2]))) {
            if ($token) {
                $del = db()->prepare('DELETE FROM costobene_recordar_sesiones WHERE id = ?');
                $del->execute([$token['id']]);
            }
            limpiarCookieRecordar();
            return;
        }

        $datosUsuario = cargarUsuarioParaSesion((int)$token['usuario_id']);
        if (!$datosUsuario) {
            $del = db()->prepare('DELETE FROM costobene_recordar_sesiones WHERE id = ?');
            $del->execute([$token['id']]);
            limpiarCookieRecordar();
            return;
        }

        session_regenerate_id(true);
        $_SESSION['u'] = $datosUsuario;

        // Rotación del token para reducir el riesgo si una cookie fuese copiada.
        $nuevoValidador = bin2hex(random_bytes(32));
        $nuevoVence = time() + (30 * 24 * 60 * 60);
        $up = db()->prepare('UPDATE costobene_recordar_sesiones SET token_hash = ?, expires_at = FROM_UNIXTIME(?), last_used_at = NOW() WHERE id = ?');
        $up->execute([hash('sha256', $nuevoValidador), $nuevoVence, $token['id']]);
        cookieRecordar($m[1] . ':' . $nuevoValidador, $nuevoVence);
    } catch (Throwable $e) {
        // Si falta aplicar la migración, el acceso normal con usuario y contraseña sigue disponible.
        error_log('No se pudo restaurar la sesión recordada: ' . $e->getMessage());
    }
}

restaurarSesionRecordada();
