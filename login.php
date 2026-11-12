<?php
require 'helpers.php';

if (usuario()) {
    header('Location: index.php');
    exit;
}

$error = '';
$loginIngresado = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginIngresado = trim((string)($_POST['login'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    try {
        validarCsrf('csrf_login');
        $st = db()->prepare('SELECT u.id,u.cliente_id,u.nombre,u.password_hash
            FROM costobene_usuarios u
            JOIN costobene_clientes c ON c.id=u.cliente_id
            WHERE (u.nombre_usuario=? OR u.correo=?) AND u.activo=1 AND c.activo=1 LIMIT 1');
        $st->execute([$loginIngresado,$loginIngresado]);
        $u = $st->fetch();

        if ($u && password_verify($pass, $u['password_hash'])) {
            $datosUsuario = cargarUsuarioParaSesion((int)$u['id']);
            if (!$datosUsuario) {
                $error = 'La cuenta no tiene roles o permisos configurados.';
            } else {
                session_regenerate_id(true);
                $_SESSION['u'] = $datosUsuario;
                if (isset($_POST['recordar']) && $_POST['recordar'] === '1') {
                    try { crearTokenRecordado((int)$u['id']); }
                    catch (Throwable $tokenError) { error_log('No se pudo activar Recordar mi cuenta: ' . $tokenError->getMessage()); }
                } else {
                    eliminarTokenRecordadoActual();
                }
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    } catch (Throwable $e) {
        error_log('Error en login CostoPyme: ' . $e->getMessage());
        $error = 'No se pudo iniciar sesión. Verificá la conexión e intentá nuevamente.';
    }
}
$csrfLogin = csrfToken('csrf_login');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ingresar - CostoPyme</title><link rel="stylesheet" href="style.css">
</head>
<body>
<main class="login">
  <div class="login-brand"><img src="assets/logo_finanzas.png" alt="Logo de CostoPyme"><h1>CostoPyme</h1><p>Gestión y rentabilidad</p></div>
  <form method="post" class="panel login-form">
    <h2>Iniciar sesión</h2>
    <?php if ($error): ?><p class="error"><?=h($error)?></p><?php endif; ?>
    <input type="hidden" name="csrf" value="<?=h($csrfLogin)?>">
    <label>Usuario o correo electrónico<input name="login" required autofocus autocomplete="username" value="<?=h($loginIngresado)?>" placeholder="Ingresá tu usuario o correo"></label>
    <label>Contraseña<input type="password" name="password" required autocomplete="current-password" placeholder="Ingresá tu contraseña"></label>
    <label class="remember-label"><input type="checkbox" name="recordar" value="1"><span>Recordar mi cuenta en este dispositivo</span></label>
    <button type="submit">Iniciar sesión</button>
  </form>
  <p class="registro-link">¿Todavía no tenés cuenta? <a href="registro.php">Crear una cuenta</a></p>
</main>
</body>
</html>
