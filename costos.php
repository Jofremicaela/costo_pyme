<?php
require 'helpers.php';
requerirLogin();
if (!puede('ver_dashboard')) { http_response_code(403); exit('No tenés permiso para acceder a Costos.'); }

$clienteId = esSuper()
    ? (int)($_GET['cliente'] ?? $_POST['cliente_id'] ?? usuario()['cliente_id'])
    : (int)usuario()['cliente_id'];
$error = '';
$ok = '';
$clientes = esSuper() ? db()->query('SELECT id,nombre_empresa FROM costobene_clientes WHERE activo=1 ORDER BY nombre_empresa')->fetchAll() : [];
$st = db()->prepare('SELECT id,nombre,moneda FROM costobene_productos WHERE cliente_id=? ORDER BY nombre');
$st->execute([$clienteId]);
$productos = $st->fetchAll();
$productoId = (int)($_GET['producto'] ?? $_POST['producto_id'] ?? ($productos[0]['id'] ?? 0));
$producto = null;
foreach ($productos as $p) {
    if ((int)$p['id'] === $productoId) { $producto = $p; break; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['accion'] ?? ''), ['aprobar_costo','rechazar_costo','editar_carga_propia'], true)) {
    try {
        validarCsrf('csrf_costos'); $accionFlujo=(string)$_POST['accion']; $costoId=(int)($_POST['costo_id']??0);
        $q=db()->prepare('SELECT c.id,c.creado_por,c.estado_aprobacion,p.cliente_id FROM costobene_costos_producto c JOIN costobene_productos p ON p.id=c.producto_id WHERE c.id=? AND p.cliente_id=?');
        $q->execute([$costoId,$clienteId]);$registro=$q->fetch();if(!$registro)throw new RuntimeException('No se encontró el costo solicitado.');
        if(in_array($accionFlujo,['aprobar_costo','rechazar_costo'],true)){
            if(!puede('aprobar_cargas'))throw new RuntimeException('No tenés permiso para aprobar cargas.');
            if($registro['estado_aprobacion']!=='pendiente')throw new RuntimeException('Esta carga ya fue revisada.');
            $estado=$accionFlujo==='aprobar_costo'?'aprobado':'rechazado';$up=db()->prepare('UPDATE costobene_costos_producto SET estado_aprobacion=?,aprobado_por=?,aprobado_en=NOW() WHERE id=?');$up->execute([$estado,(int)usuario()['id'],$costoId]);
            header('Location: costos.php?producto='.$productoId.'&revision=1');exit;
        }
        if(!esOperador()||!puede('editar_cargas_propias')||(int)$registro['creado_por']!==(int)usuario()['id']||$registro['estado_aprobacion']!=='pendiente')throw new RuntimeException('Solo podés editar tus propias cargas mientras estén pendientes de aprobación.');
        $nombre=trim((string)($_POST['nombre']??''));$monto=filter_var($_POST['monto']??null,FILTER_VALIDATE_FLOAT);$tipo=(string)($_POST['tipo_costo']??'');
        if($nombre===''||mb_strlen($nombre)>150||$monto===false||$monto<0||!in_array($tipo,['fixed','variable','indirect','labor'],true))throw new RuntimeException('Revisá el concepto, tipo y monto.');
        $up=db()->prepare('UPDATE costobene_costos_producto SET nombre=?,tipo_costo=?,monto=? WHERE id=? AND creado_por=? AND estado_aprobacion=\'pendiente\'');$up->execute([$nombre,$tipo,$monto,$costoId,(int)usuario()['id']]);
        header('Location: costos.php?producto='.$productoId.'&actualizado=1');exit;
    } catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'No se pudo procesar la carga.';}
}
if(isset($_GET['revision']))$ok='La revisión se registró correctamente.';
if(isset($_GET['actualizado']))$ok='Tu carga pendiente fue actualizada.';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_comision') {
    try {
        if (!puede('gestionar_comisiones')) throw new RuntimeException('Tu perfil no puede administrar comisiones.');
        validarCsrf('csrf_costos');
        $categoria=(string)($_POST['categoria']??''); $nombre=trim((string)($_POST['nombre_comision']??''));
        $porcentaje=filter_var($_POST['porcentaje']??0,FILTER_VALIDATE_FLOAT); $fijo=filter_var($_POST['cargo_fijo']??0,FILTER_VALIDATE_FLOAT); $iva=filter_var($_POST['iva_porcentaje']??0,FILTER_VALIDATE_FLOAT);
        $productoComision=(int)($_POST['producto_comision']??0);
        if(!in_array($categoria,['marketplace','medio_pago','canal_venta'],true)||$nombre===''||$porcentaje===false||$porcentaje<0||$porcentaje>100||$fijo===false||$fijo<0||$iva===false||$iva<0||$iva>100) throw new RuntimeException('Revisá los datos de la comisión.');
        if($productoComision>0){$v=db()->prepare('SELECT id FROM costobene_productos WHERE id=? AND cliente_id=?');$v->execute([$productoComision,$clienteId]);if(!$v->fetch())throw new RuntimeException('El producto seleccionado no pertenece a tu empresa.');}
        $monedaComision=strtoupper((string)($_POST['moneda_comision']??'ARS'));if(!in_array($monedaComision,['ARS','USD','EUR'],true))throw new RuntimeException('Seleccioná una moneda válida para el cargo fijo.');
        $ins=db()->prepare('INSERT INTO costobene_comisiones (cliente_id,producto_id,categoria,nombre,porcentaje,cargo_fijo,moneda,iva_porcentaje,base_calculo,creado_por) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $ins->execute([$clienteId,$productoComision?:null,$categoria,$nombre,$porcentaje,$fijo,$monedaComision,$iva,(string)($_POST['base_calculo']??'precio_total'),(int)usuario()['id']]);
        header('Location: costos.php?producto='.$productoId.'&comision_guardada=1');exit;
    } catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'No se pudo guardar la comisión. Aplicá la migración SQL.';}
}
if (isset($_GET['comision_guardada'])) $ok='La comisión se registró correctamente.';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_costo') {
    $facturaPath = null;
    try {
        if (!puede('editar_costos')) throw new RuntimeException('Tu usuario no tiene permiso para agregar costos.');
        validarCsrf('csrf_costos');
        if (!$producto) throw new RuntimeException('Seleccioná un producto o servicio válido.');

        $tipo = (string)($_POST['tipo_costo'] ?? '');
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        if ($tipo === 'labor' && $nombre === '') $nombre = 'Mano de obra';
        if ($nombre === '' || mb_strlen($nombre) > 150) throw new RuntimeException('Ingresá un nombre de costo de hasta 150 caracteres.');
        if (!in_array($tipo, ['fixed','variable','indirect','labor'], true)) throw new RuntimeException('Seleccioná una categoría de costo válida.');

        $horas = null;
        $valorHora = null;
        if ($tipo === 'labor') {
            $horas = filter_var($_POST['horas_trabajo'] ?? null, FILTER_VALIDATE_INT);
            $valorHora = filter_var($_POST['valor_hora'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($horas === false || $horas === null || $horas <= 0) throw new RuntimeException('Las horas de mano de obra deben ser un número entero mayor que cero.');
            if ($valorHora === false || $valorHora === null || $valorHora <= 0) throw new RuntimeException('El costo por hora debe ser mayor que cero.');
            $monto = round($horas * $valorHora, 2);
        } else {
            $monto = filter_var($_POST['monto'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($monto === false || $monto === null || $monto < 0) throw new RuntimeException('El monto debe ser un número igual o mayor a cero.');
        }

        $compartido = ($_POST['compartido'] ?? '') === '1';
        $productosIds = [$productoId];
        if ($compartido) {
            $recibidos = $_POST['productos_compartidos'] ?? [];
            if (!is_array($recibidos)) $recibidos = [];
            $productosIds = array_values(array_unique(array_filter(array_map('intval', $recibidos), static fn($id) => $id > 0)));
            if (count($productosIds) < 2) throw new RuntimeException('Seleccioná al menos dos productos o servicios para compartir el costo.');
            if (!in_array($productoId, $productosIds, true)) throw new RuntimeException('La selección debe incluir el producto o servicio principal.');

            $placeholders = implode(',', array_fill(0, count($productosIds), '?'));
            $check = db()->prepare("SELECT id, moneda FROM costobene_productos WHERE cliente_id = ? AND id IN ($placeholders)");
            $check->execute(array_merge([$clienteId], $productosIds));
            $seleccionados = $check->fetchAll();
            if (count($seleccionados) !== count($productosIds)) throw new RuntimeException('Uno o más productos seleccionados no pertenecen a este cliente.');
            $monedas = array_unique(array_column($seleccionados, 'moneda'));
            if (count($monedas) !== 1 || $monedas[0] !== $producto['moneda']) throw new RuntimeException('Para compartir un costo, todos los productos deben tener la misma moneda.');
        }

        $facturaPath = subirArchivoSeguro($_FILES['factura'] ?? [], 'facturas', [
            'application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'
        ], 8 * 1024 * 1024);

        $pdo = db();
        $pdo->beginTransaction();
        $ins = $pdo->prepare('INSERT INTO costobene_costos_producto (producto_id,creado_por,estado_aprobacion,tipo_costo,nombre,monto,factura_path,horas_trabajo,valor_hora) VALUES (?,?,?,?,?,?,?,?,?)');
        $estadoNuevo = esOperador() ? 'pendiente' : 'aprobado';
        $ins->execute([$productoId,(int)usuario()['id'],$estadoNuevo,$tipo,$nombre,$monto,$facturaPath,$horas,$valorHora]);
        $costoId = (int)$pdo->lastInsertId();

        if ($compartido) {
            // Se reparte en centavos enteros para que la suma asignada coincida exactamente con el total.
            $totalCentavos = (int)round($monto * 100);
            $cantidad = count($productosIds);
            $base = intdiv($totalCentavos, $cantidad);
            $resto = $totalCentavos % $cantidad;
            $map = $pdo->prepare('INSERT INTO costobene_costos_productos_compartidos (costo_id,producto_id,monto_asignado) VALUES (?,?,?)');
            foreach ($productosIds as $i => $idProducto) {
                $centavos = $base + ($i < $resto ? 1 : 0);
                $map->execute([$costoId,$idProducto,number_format($centavos / 100, 2, '.', '')]);
            }
        }

        $pdo->commit();
        header('Location: costos.php?producto=' . $productoId . (esSuper() ? '&cliente=' . $clienteId : '') . '&guardado=1');
        exit;
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        if ($facturaPath && is_file(__DIR__ . '/' . $facturaPath)) @unlink(__DIR__ . '/' . $facturaPath);
        error_log('Error en costos.php: ' . $e->getMessage());
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar el costo. Verificá que la actualización SQL esté aplicada.';
    }
}

if (isset($_GET['guardado'])) $ok = 'El costo se registró correctamente.';
$costos = [];
if ($producto) {
    $q = db()->prepare('SELECT c.id,c.nombre,c.tipo_costo,c.monto,c.factura_path,c.fecha_creacion,c.horas_trabajo,c.valor_hora,c.creado_por,c.estado_aprobacion,
        COALESCE(m.monto_asignado,c.monto) AS monto_asignado,
        (SELECT COUNT(*) FROM costobene_costos_productos_compartidos mc WHERE mc.costo_id=c.id) AS cantidad_productos,
        (SELECT GROUP_CONCAT(p2.nombre ORDER BY p2.nombre SEPARATOR ", ")
         FROM costobene_costos_productos_compartidos mc2
         JOIN costobene_productos p2 ON p2.id=mc2.producto_id WHERE mc2.costo_id=c.id) AS nombres_productos
        FROM costobene_costos_producto c
        LEFT JOIN costobene_costos_productos_compartidos m ON m.costo_id=c.id AND m.producto_id=?
        WHERE m.producto_id IS NOT NULL
           OR (c.producto_id=? AND NOT EXISTS (SELECT 1 FROM costobene_costos_productos_compartidos mx WHERE mx.costo_id=c.id))
        ORDER BY c.fecha_creacion DESC,c.id DESC');
    $q->execute([$productoId,$productoId]);
    $costos = $q->fetchAll();
}
$comQ=db()->prepare('SELECT c.*,p.nombre AS producto_nombre FROM costobene_comisiones c LEFT JOIN costobene_productos p ON p.id=c.producto_id WHERE c.cliente_id=? ORDER BY c.id DESC'); $comQ->execute([$clienteId]); $comisiones=$comQ->fetchAll();
$labels = ['fixed'=>'Costo fijo','variable'=>'Costo variable','indirect'=>'Costo indirecto','labor'=>'Mano de obra'];
$cfgPeriodoQ=db()->prepare('SELECT periodo_activo FROM costobene_configuraciones_app WHERE cliente_id=?');$cfgPeriodoQ->execute([$clienteId]);$periodoCostos=['day'=>'día','week'=>'semana','month'=>'mes','year'=>'año'][$cfgPeriodoQ->fetchColumn()?:'month']??'mes';
$csrf = csrfToken('csrf_costos');
$paginaActual = 'costos';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Costos - CostoPyme</title><link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-shell"><?php require 'menu.php'; ?><main class="main">
<header class="page-head"><div><h1>Costos</h1><p>Registrá costos individuales o compartidos y calculá la mano de obra.</p></div></header>
<?php if ($ok): ?><div class="success"><?=h($ok)?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<section class="panel">
<?php if (esSuper()): ?>
<label style="max-width:360px;margin-bottom:14px">Cliente<select onchange="location.href='costos.php?cliente='+this.value">
<?php foreach ($clientes as $c): ?><option value="<?=(int)$c['id']?>" <?=(int)$c['id']===$clienteId?'selected':''?>><?=h($c['nombre_empresa'])?></option><?php endforeach; ?>
</select></label>
<?php endif; ?>
<label style="max-width:460px">Producto o servicio principal<select onchange="location.href='costos.php?producto='+this.value<?=esSuper()?"+'&cliente='+encodeURIComponent('".$clienteId."')":''?>">
<?php foreach ($productos as $p): ?><option value="<?=(int)$p['id']?>" <?=(int)$p['id']===$productoId?'selected':''?>><?=h($p['nombre'])?> (<?=h($p['moneda'])?>)</option><?php endforeach; ?>
</select></label>
<?php if (!$productos): ?><div class="empty" style="margin-top:16px">Primero tenés que agregar un producto o servicio en el catálogo.</div><?php endif; ?>
</section>
<?php if ($producto): ?>
<section class="panel">
<h2>Agregar un costo · <?=h($producto['nombre'])?></h2><p class="help">Período de análisis: <b><?=h($periodoCostos)?></b>. Cargá los costos fijos e indirectos como totales del período y los variables como costo por unidad. Si cambiás el período de análisis, revisá los importes anteriores: no se convierten automáticamente.</p>
<?php if (!puede('editar_costos')): ?><div class="alert">Tu usuario puede consultar los costos, pero no agregarlos.</div>
<?php else: ?>
<form method="post" enctype="multipart/form-data" id="formCosto">
<input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="accion" value="agregar_costo">
<input type="hidden" name="producto_id" value="<?=$productoId?>"><input type="hidden" name="cliente_id" value="<?=$clienteId?>">
<div class="form-grid">
<label>Nombre o concepto del costo *<input name="nombre" id="nombreCosto" maxlength="150" required placeholder="Ej. Alquiler del local"></label>
<label>Tipo de costo *<select name="tipo_costo" id="tipoCosto" required><option value="">Seleccionar categoría</option><?php foreach ($labels as $k=>$v): ?><option value="<?=h($k)?>"><?=h($v)?></option><?php endforeach; ?></select></label>
<div id="montoNormal"><label><span id="etiquetaMonto">Monto total del período (<?=h($producto['moneda'])?>) *</span><input type="number" name="monto" min="0" step="0.01" placeholder="0,00"><small class="help" id="ayudaMonto">Importe correspondiente al período de análisis.</small></label></div>
<div id="camposManoObra" class="labor-box" hidden>
<strong>Mano de obra</strong>
<div class="form-grid labor-grid">
<label>Horas trabajadas *<input type="number" name="horas_trabajo" id="horasTrabajo" min="1" step="1" inputmode="numeric" placeholder="Ej. 8"></label>
<label>Costo por hora (<?=h($producto['moneda'])?>) *<input type="number" name="valor_hora" id="valorHora" min="0.01" step="0.01" placeholder="Ej. 5000"></label>
</div>
<div class="labor-total"><span>Total de mano de obra</span><b id="totalManoObra"><?=h($producto['moneda'])?> 0,00</b></div>
<small class="help">El total se calcula automáticamente: horas enteras × costo por hora.</small>
</div>
<label>Factura o comprobante<input type="file" name="factura" accept="application/pdf,image/jpeg,image/png,image/webp"><span class="help">PDF, JPG, PNG o WEBP. Máximo 8 MB.</span></label>
</div>
<div class="shared-box">
<label class="shared-toggle"><input type="checkbox" name="compartido" id="compartido" value="1"><span><b>¿Es un costo compartido?</b><small>Distribuir este importe entre varios productos o servicios.</small></span></label>
<div id="selectorCompartidos" hidden>
<label>Seleccioná los productos o servicios que comparten el costo *
<select name="productos_compartidos[]" id="productosCompartidos" multiple size="5" disabled>
<?php foreach ($productos as $p): ?><option value="<?=(int)$p['id']?>" <?=(int)$p['id']===$productoId?'selected':''?>><?=h($p['nombre'])?> (<?=h($p['moneda'])?>)</option><?php endforeach; ?>
</select></label>
<small class="help">Mantené presionada Ctrl (Windows) o Cmd (Mac) para seleccionar varios. Todos deben usar la misma moneda. El total se divide en partes iguales; los centavos sobrantes se asignan al primer producto seleccionado.</small>
<div id="resumenCompartido" class="shared-summary">Seleccioná al menos dos productos para ver la distribución.</div>
</div>
</div>
<div class="actions"><button type="submit">＋ Agregar costo</button></div><?php if(esOperador()): ?><p class="help">Tus cargas quedarán pendientes de aprobación. Podés editarlas mientras sigan pendientes.</p><?php endif; ?>
</form>
<?php endif; ?>
</section>
<section class="panel">
<div class="section-title"><h2>Costos registrados para <?=h($producto['nombre'])?></h2><span class="pill"><?=count($costos)?> costos</span></div>
<?php if (!$costos): ?><div class="empty">Todavía no hay costos cargados para este producto o servicio.</div>
<?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Tipo</th><th class="num">Monto total</th><th class="num">Parte asignada</th><th>Compartido con</th><th>Factura</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($costos as $c): ?>
<tr>
<td><?=h($c['nombre'])?><?php if ($c['tipo_costo']==='labor' && $c['horas_trabajo'] !== null): ?><small class="table-note"><?= (int)$c['horas_trabajo']?> h × <?=h($producto['moneda'])?> <?=number_format((float)$c['valor_hora'],2,',','.')?>/h</small><?php endif; ?></td>
<td><?=h($labels[$c['tipo_costo']]??$c['tipo_costo'])?></td>
<td class="num"><?=h($producto['moneda'])?> <?=number_format((float)$c['monto'],2,',','.')?></td>
<td class="num"><b><?=h($producto['moneda'])?> <?=number_format((float)$c['monto_asignado'],2,',','.')?></b></td>
<td><?php if ((int)$c['cantidad_productos']>1): ?><span class="pill">Compartido</span><small class="table-note"><?=h($c['nombres_productos'] ?? '')?></small><?php else: ?><span class="muted">Individual</span><?php endif; ?></td>
<td><?php if ($c['factura_path']): ?><a href="<?=h($c['factura_path'])?>" target="_blank" rel="noopener">Ver archivo ↗</a><?php else: ?><span class="muted">—</span><?php endif; ?></td>
<td><span class="pill"><?=h($c['estado_aprobacion'])?></span></td><td><?=h(substr((string)$c['fecha_creacion'],0,10))?></td><td><?php if(puede('aprobar_cargas') && $c['estado_aprobacion']==='pendiente'): ?><form method="post" style="display:inline-flex;gap:5px"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="costo_id" value="<?=(int)$c['id']?>"><button name="accion" value="aprobar_costo">Aprobar</button><button class="btn-secondary" name="accion" value="rechazar_costo">Rechazar</button></form><?php elseif(esOperador() && (int)$c['creado_por']===(int)usuario()['id'] && $c['estado_aprobacion']==='pendiente'): ?><form method="post" class="inline-edit"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="accion" value="editar_carga_propia"><input type="hidden" name="costo_id" value="<?=(int)$c['id']?>"><input name="nombre" value="<?=h($c['nombre'])?>" required><input name="monto" type="number" min="0" step="0.01" value="<?=h($c['monto'])?>" required><input type="hidden" name="tipo_costo" value="<?=h($c['tipo_costo'])?>"><button class="btn-secondary">Actualizar</button></form><?php else: ?><span class="muted">—</span><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
<?php endif; ?>
<section class="panel" id="comisiones"><h2>Comisiones de venta</h2><p class="help">Configurá cargos de marketplaces, medios de pago y canales de venta. Podés definir una comisión general o específica para un producto.</p>
<?php if(puede('gestionar_comisiones')): ?><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="accion" value="agregar_comision"><div class="form-grid"><label>Categoría *<select name="categoria"><option value="marketplace">Marketplace</option><option value="medio_pago">Medio de pago</option><option value="canal_venta">Canal de venta</option></select></label><label>Nombre *<input name="nombre_comision" required maxlength="150" placeholder="Ej. Mercado Pago"></label><label>Producto específico<select name="producto_comision"><option value="0">General para la empresa</option><?php foreach($productos as $pc):?><option value="<?=(int)$pc['id']?>"><?=h($pc['nombre'])?></option><?php endforeach;?></select></label><label>Comisión (%)<input type="number" name="porcentaje" min="0" max="100" step="0.0001" value="0"></label><label>Cargo fijo por operación<input type="number" name="cargo_fijo" min="0" step="0.01" value="0"></label><label>Moneda del cargo fijo<select name="moneda_comision"><option value="ARS">ARS</option><option value="USD">USD</option><option value="EUR">EUR</option></select></label><label>IVA sobre comisión (%)<input type="number" name="iva_porcentaje" min="0" max="100" step="0.01" value="0"></label><label>Base de cálculo<select name="base_calculo"><option value="precio_total">Precio efectivamente cobrado</option><option value="precio_sin_descuento">Precio sin descuento</option></select></label></div><div class="actions"><button type="submit">＋ Guardar comisión</button></div></form><?php elseif(!puede('ver_costos')):?><div class="alert">No tenés permiso para consultar comisiones.</div><?php endif; ?>
<?php if($comisiones):?><div class="table-wrap"><table><thead><tr><th>Concepto</th><th>Categoría</th><th>Aplicación</th><th>%</th><th>Cargo fijo</th><th>Moneda</th><th>IVA</th></tr></thead><tbody><?php foreach($comisiones as $co):?><tr><td><?=h($co['nombre'])?></td><td><?=h(str_replace('_',' ',$co['categoria']))?></td><td><?=h($co['producto_nombre']??'Todos los productos')?></td><td><?=number_format((float)$co['porcentaje'],2,',','.')?>%</td><td><?=number_format((float)$co['cargo_fijo'],2,',','.')?></td><td><?=h($co['moneda'])?></td><td><?=number_format((float)$co['iva_porcentaje'],2,',','.')?>%</td></tr><?php endforeach;?></tbody></table></div><?php else:?><div class="empty">Todavía no hay comisiones configuradas.</div><?php endif;?></section>
</main></div>
<script>
(() => {
 const tipo = document.getElementById('tipoCosto');
 const normal = document.getElementById('montoNormal');
 const labor = document.getElementById('camposManoObra');
 const nombre = document.getElementById('nombreCosto');
 const horas = document.getElementById('horasTrabajo');
 const valor = document.getElementById('valorHora');
 const total = document.getElementById('totalManoObra');
 const etiquetaMonto = document.getElementById('etiquetaMonto');
 const ayudaMonto = document.getElementById('ayudaMonto');
 const compartido = document.getElementById('compartido');
 const selector = document.getElementById('selectorCompartidos');
 const productos = document.getElementById('productosCompartidos');
 const resumen = document.getElementById('resumenCompartido');
 const moneda = <?=json_encode($producto['moneda'])?>;
 const fmt = n => new Intl.NumberFormat('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n);
 function actualizarTipo(){
   const esLabor = tipo.value === 'labor';
   normal.hidden = esLabor;
   labor.hidden = !esLabor;
   normal.querySelector('input').required = !esLabor;
   const esVariable = tipo.value === 'variable';
   etiquetaMonto.textContent = (esVariable ? 'Costo variable por unidad (' : 'Monto total del período (') + moneda + ') *';
   ayudaMonto.textContent = esVariable ? 'Importe que corresponde a una unidad de venta.' : 'Importe total del período de análisis configurado.';
   horas.required = esLabor;
   valor.required = esLabor;
   if(esLabor && !nombre.value.trim()) nombre.value = 'Mano de obra';
 }
 function actualizarTotal(){
   const h = Number(horas.value)||0, v = Number(valor.value)||0;
   total.textContent = moneda+' '+fmt(h*v);
 }
 function actualizarCompartido(){
   selector.hidden = !compartido.checked;
   productos.disabled = !compartido.checked;
   productos.required = compartido.checked;
   const elegidos = [...productos.selectedOptions];
   if(!compartido.checked){resumen.textContent='El costo se asignará únicamente al producto principal.';return;}
   if(elegidos.length<2){resumen.textContent='Seleccioná al menos dos productos o servicios.';return;}
   const totalMonto = tipo.value==='labor' ? (Number(horas.value)||0)*(Number(valor.value)||0) : (Number(document.querySelector('[name="monto"]').value)||0);
   resumen.textContent = elegidos.length+' productos seleccionados · Aproximadamente '+moneda+' '+fmt(totalMonto/elegidos.length)+' por producto.';
 }
 tipo.addEventListener('change',()=>{actualizarTipo();actualizarCompartido();});
 [horas,valor].forEach(el=>el.addEventListener('input',()=>{actualizarTotal();actualizarCompartido();}));
 document.querySelector('[name="monto"]')?.addEventListener('input',actualizarCompartido);
 compartido.addEventListener('change',actualizarCompartido);
 productos.addEventListener('change',actualizarCompartido);
 actualizarTipo(); actualizarTotal(); actualizarCompartido();
})();
</script>
</body></html>
