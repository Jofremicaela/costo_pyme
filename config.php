
<?php

// ========================================
// CONEXIÓN A LA BASE DE DATOS -local por ahora luego lo conectamos a  INFINITYFREE
// ========================================


function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {

        // Detectar si estamos trabajando en XAMPP o en InfinityFree
        $esLocal = in_array($_SERVER['HTTP_HOST'] ?? '', [
            'localhost',
            '127.0.0.1'
        ], true);

        if ($esLocal) {
            // CONEXIÓN LOCAL XAMPP
            $host = '127.0.0.1';
            $dbname = 'calcostos';
            $user = 'root';
            $password = '';
        } else {
            // CONEXIÓN PRODUCCIÓN INFINITYFREE
            $host = 'sql200.infinityfree.com';
            $dbname = 'if0_43416623_Costo_Pyme_BD';
            $user = 'if0_43416623';
            $password = '718122Mica';
        }

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

// ========================================
// CONFIGURACIÓN DE SESIONES
// ========================================

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {

    // Evita aceptar identificadores de sesión no inicializados.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    ]);

    session_start();
}

// ========================================
// FUNCIONES GENERALES
// ========================================

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function pesos($n): string {
    return '$ ' . number_format($n, 0, ',', '.');
}

function usuario(): ?array {
    return $_SESSION['u'] ?? null;
}

// ========================================
// CONTROL DE ACCESO
// ========================================

function requerirLogin(): void {
    if (!usuario()) {
        header('Location: login.php');
        exit;
    }
}

function puede(string $permiso): bool {
    $u = usuario();
    return $u && in_array($permiso, $u['permisos'], true);
}

function esSuper(): bool {
    $u = usuario();
    return $u && in_array('super_administrador', $u['roles'], true);
}

// ========================================
// CLIENTE ACTUAL
// ========================================

function clienteActual(): int {
    if (esSuper() && isset($_GET['cliente'])) {
        return (int)$_GET['cliente'];
    }

    return (int)usuario()['cliente_id'];
}
