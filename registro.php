<?php
require __DIR__ . '/helpers.php';
if (usuario()) { header('Location: index.php'); exit; }
$csrf = csrfToken('csrf_registro');
$error = '';
$old = ['rol'=>'administrador','empresa'=>'','cuit'=>'','rubro'=>'','codigo'=>'','nombre'=>'','apellido'=>'','correo'=>'','usuario'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) $old[$k] = trim((string)($_POST[$k] ?? $old[$k]));
    if ($old['rol'] !== 'administrador') { $old['empresa'] = trim((string)($_POST['empresa_invitada'] ?? '')); $old['cuit'] = trim((string)($_POST['cuit_invitada'] ?? '')); }
    $password = (string)($_POST['password'] ?? '');
    $confirmar = (string)($_POST['confirmar_password'] ?? '');
    try {
        validarCsrf('csrf_registro');
        if (!in_array($old['rol'], ['administrador','encargado','operador'], true)) throw new RuntimeException('Seleccioná un tipo de usuario válido.');
        if ($old['empresa']==='' || $old['nombre']==='' || $old['apellido']==='' || $old['correo']==='' || $old['usuario']==='' || $password==='') throw new RuntimeException('Completá todos los campos obligatorios.');
        if (mb_strlen($old['empresa'])>150 || mb_strlen($old['nombre'])>100 || mb_strlen($old['apellido'])>100 || mb_strlen($old['usuario'])>50) throw new RuntimeException('Uno o más campos superan la longitud permitida.');
        if (!filter_var($old['correo'], FILTER_VALIDATE_EMAIL) || strlen($old['correo'])>255) throw new RuntimeException('Ingresá un correo electrónico válido.');
        if (!preg_match('/^[A-Za-z0-9_.-]{4,50}$/', $old['usuario'])) throw new RuntimeException('El usuario debe tener entre 4 y 50 caracteres válidos.');
        if (strlen($password)<8 || $password!==$confirmar) throw new RuntimeException('La contraseña debe tener al menos 8 caracteres y ambas deben coincidir.');
        if ($old['rol']==='administrador') {
            $old['cuit'] = preg_replace('/[^0-9]/', '', $old['cuit']);
            if (strlen($old['cuit']) !== 11) throw new RuntimeException('El CUIT debe contener exactamente 11 números.');
            if (!validarCuitArgentina($old['cuit'])) throw new RuntimeException('El CUIT tiene 11 dígitos, pero el dígito verificador no coincide. Revisá el número ingresado.');
            if (!in_array($old['rubro'], ['gastronomia','textiles','servicios'], true)) throw new RuntimeException('Seleccioná el rubro de la empresa.');
        } else {
            $old['cuit'] = preg_replace('/[^0-9]/', '', $old['cuit']);
            if (strlen($old['cuit']) !== 11) throw new RuntimeException('El CUIT de la empresa debe contener exactamente 11 números.');
            if (!validarCuitArgentina($old['cuit'])) throw new RuntimeException('El CUIT ingresado tiene 11 dígitos, pero el dígito verificador no coincide.');
            if ($old['codigo']==='') throw new RuntimeException('Ingresá el código de invitación proporcionado por el administrador.');
        }
        $pdo=db(); $pdo->beginTransaction();
        $dupe=$pdo->prepare('SELECT id FROM costobene_usuarios WHERE nombre_usuario=? OR correo=? LIMIT 1');
        $dupe->execute([$old['usuario'],$old['correo']]);
        if ($dupe->fetch()) throw new RuntimeException('Ese nombre de usuario o correo ya está registrado.');
        if ($old['rol']==='administrador') {
            $dupCuit=$pdo->prepare('SELECT id FROM costobene_clientes WHERE cuit=? LIMIT 1');$dupCuit->execute([preg_replace('/\D+/', '',$old['cuit'])]);if($dupCuit->fetch())throw new RuntimeException('Ya existe una empresa registrada con ese CUIT. Solicitá una invitación al administrador de esa empresa si corresponde.');
            $st=$pdo->prepare('SELECT id FROM costobene_roles WHERE nombre=? LIMIT 1'); $st->execute(['administrador']); $rolId=(int)$st->fetchColumn();
            $planQ=$pdo->prepare("SELECT id,duracion_dias FROM costobene_planes WHERE nombre='Gratis' AND activo=1 ORDER BY id LIMIT 1"); $planQ->execute(); $plan=$planQ->fetch();
            if (!$rolId || !$plan) throw new RuntimeException('Falta configurar el rol administrador o el plan Gratis.');
            $st=$pdo->prepare('INSERT INTO costobene_clientes (nombre_empresa,cuit,email_contacto,rubro,activo) VALUES (?,?,?,?,1)');
            $st->execute([$old['empresa'],preg_replace('/\D+/', '',$old['cuit']),$old['correo'],$old['rubro']]); $clienteId=(int)$pdo->lastInsertId();
            $st=$pdo->prepare("INSERT INTO costobene_suscripciones (cliente_id,plan_id,estado,fecha_inicio,fecha_fin,renovacion_automatica) VALUES (?,?,'activa',CURRENT_TIMESTAMP,DATE_ADD(CURRENT_TIMESTAMP,INTERVAL ? DAY),0)");
            $st->execute([$clienteId,(int)$plan['id'],(int)$plan['duracion_dias']]);
            $pdo->prepare("INSERT INTO costobene_configuraciones_app (cliente_id,periodo_activo,moneda) VALUES (?,'month','ARS')")->execute([$clienteId]);
        } else {
            $codeHash=hash('sha256',strtoupper($old['codigo']));
            $st=$pdo->prepare("SELECT i.id,i.cliente_id,i.rol_id,c.nombre_empresa,c.cuit FROM costobene_invitaciones i JOIN costobene_clientes c ON c.id=i.cliente_id WHERE i.codigo_hash=? AND i.rol=? AND i.usada_en IS NULL AND i.revocada=0 AND i.vence_en>NOW() AND c.activo=1 FOR UPDATE");
            $st->execute([$codeHash,$old['rol']]); $inv=$st->fetch();
            if (!$inv) throw new RuntimeException('El código de invitación no es válido, venció o ya fue utilizado.');
            if (mb_strtolower($old['empresa'])!==mb_strtolower($inv['nombre_empresa']) || preg_replace('/\D+/', '',$old['cuit'])!==preg_replace('/\D+/', '',(string)$inv['cuit'])) throw new RuntimeException('El nombre de empresa o CUIT no coincide con la invitación.');
            $clienteId=(int)$inv['cliente_id']; $rolId=(int)$inv['rol_id'];
        }
        $st=$pdo->prepare('INSERT INTO costobene_usuarios (cliente_id,nombre,apellido,nombre_usuario,correo,password_hash,activo) VALUES (?,?,?,?,?,?,1)');
        $st->execute([$clienteId,$old['nombre'],$old['apellido'],$old['usuario'],$old['correo'],password_hash($password,PASSWORD_DEFAULT)]); $usuarioId=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO costobene_usuarios_roles (usuario_id,rol_id) VALUES (?,?)')->execute([$usuarioId,$rolId]);
        if ($old['rol']!=='administrador') $pdo->prepare('UPDATE costobene_invitaciones SET usada_en=NOW(),usuario_id=? WHERE id=?')->execute([$usuarioId,(int)$inv['id']]);
        $pdo->commit();
        $datos=cargarUsuarioParaSesion($usuarioId); if (!$datos) throw new RuntimeException('La cuenta se creó, pero no se pudieron cargar sus permisos.');
        session_regenerate_id(true); $_SESSION['u']=$datos; unset($_SESSION['csrf_registro']); header('Location: index.php'); exit;
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        error_log('Registro CostoPyme: '.$e->getMessage());
        $error=$e instanceof RuntimeException ? $e->getMessage() : 'No se pudo completar el registro. Verificá que la migración SQL esté aplicada.';
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Crear cuenta - CostoPyme</title><link rel="stylesheet" href="style.css"></head><body>
<main class="registro"><h1>CostoPyme</h1><form method="post" class="panel" autocomplete="on"><h2>Crear una cuenta</h2><p class="registro-intro">Elegí tu perfil y vinculá tu cuenta con una empresa.</p>
<?php if($error):?><p class="error" role="alert"><?=h($error)?></p><?php endif;?><input type="hidden" name="csrf" value="<?=h($csrf)?>">
<h3>1. ¿Qué tipo de usuario sos?</h3><label>Perfil *<select name="rol" id="rolRegistro" required><option value="administrador" <?=$old['rol']==='administrador'?'selected':''?>>Administrador · Creo una empresa</option><option value="encargado" <?=$old['rol']==='encargado'?'selected':''?>>Encargado · Gestiono productos, costos y producción</option><option value="operador" <?=$old['rol']==='operador'?'selected':''?>>Operador · Cargo datos operativos</option></select></label>
<section id="empresaAdmin"><h3>2. Datos de la empresa</h3><label>Nombre de la empresa *<input name="empresa" maxlength="150" required value="<?=h($old['empresa'])?>" autocomplete="organization"></label><label>CUIT de la empresa *<input name="cuit" maxlength="13" inputmode="numeric" autocomplete="off" required value="<?=h($old['cuit'])?>" placeholder="30-XXXXXXXX-X"></label><label>Actividad o rubro *<select name="rubro" required><option value="">Seleccionar rubro</option><option value="gastronomia" <?=$old['rubro']==='gastronomia'?'selected':''?>>Gastronomía</option><option value="textiles" <?=$old['rubro']==='textiles'?'selected':''?>>Textiles</option><option value="servicios" <?=$old['rubro']==='servicios'?'selected':''?>>Servicios</option></select></label></section>
<section id="empresaInvitada" hidden><h3>2. Vinculación con la empresa</h3><label>Nombre de la empresa *<input name="empresa_invitada" id="empresaInvitadaNombre" maxlength="150" value="<?=h($old['empresa'])?>"></label><label>CUIT de la empresa *<input name="cuit_invitada" id="cuitInvitado" maxlength="13" inputmode="numeric" autocomplete="off" value="<?=h($old['cuit'])?>" placeholder="30-XXXXXXXX-X"></label><label>Código de invitación *<input name="codigo" id="codigoInvitacion" maxlength="64" value="<?=h($old['codigo'])?>" autocomplete="off"></label><small>El administrador debe generar una invitación para tu perfil. El nombre y CUIT deben coincidir con los datos de la empresa.</small></section>
<h3>3. Tus datos personales</h3><div class="registro-grid"><label>Nombre *<input name="nombre" maxlength="100" required value="<?=h($old['nombre'])?>"></label><label>Apellido *<input name="apellido" maxlength="100" required value="<?=h($old['apellido'])?>"></label></div><label>Correo electrónico *<input type="email" name="correo" maxlength="255" required value="<?=h($old['correo'])?>"></label><label>Nombre de usuario *<input name="usuario" minlength="4" maxlength="50" pattern="[A-Za-z0-9_.-]{4,50}" required value="<?=h($old['usuario'])?>"></label><label>Contraseña *<input type="password" name="password" minlength="8" required autocomplete="new-password"></label><label>Confirmar contraseña *<input type="password" name="confirmar_password" minlength="8" required autocomplete="new-password"></label>
<div class="alert"><b>Permisos según perfil</b><p id="descripcionRol">Control de empresa, usuarios, productos, costos y reportes.</p></div><button type="submit">Crear mi cuenta</button></form><p class="registro-link">¿Ya tenés cuenta? <a href="login.php">Iniciar sesión</a></p></main>
<script>
(()=>{const r=document.getElementById('rolRegistro'),a=document.getElementById('empresaAdmin'),i=document.getElementById('empresaInvitada'),desc=document.getElementById('descripcionRol');const adminFields=[...a.querySelectorAll('input,select')],inviteFields=[...i.querySelectorAll('input')];function sync(){const isAdmin=r.value==='administrador';a.hidden=!isAdmin;i.hidden=isAdmin;adminFields.forEach(x=>x.disabled=!isAdmin);inviteFields.forEach(x=>x.disabled=isAdmin);const map={administrador:'Control de empresa, usuarios, productos, costos, producción y reportes.',encargado:'Gestión de productos, costos, comisiones, promociones y producción.',operador:'Carga de costos y datos operativos. Puede editar sus cargas hasta que el encargado las apruebe.'};desc.textContent=map[r.value];}r.addEventListener('change',sync);sync();document.querySelectorAll('[name=\"cuit\"],[name=\"cuit_invitada\"]').forEach(el=>{el.addEventListener('input',()=>{const n=el.value.replace(/\D/g,'').slice(0,11);el.value=n.length>10?n.slice(0,2)+'-'+n.slice(2,10)+'-'+n.slice(10):n.length>2?n.slice(0,2)+'-'+n.slice(2):n;});});})();
</script></body></html>
