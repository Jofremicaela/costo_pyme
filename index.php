<?php
require 'helpers.php';
requerirLogin();
$u = usuario();
$clienteId = esSuper() ? (int)($_GET['cliente'] ?? $_POST['cliente_id'] ?? $u['cliente_id']) : (int)$u['cliente_id'];
$mensaje = ''; $error = '';
$editando = null;

if (isset($_GET['editar'])) {
    $q = db()->prepare('SELECT * FROM costobene_productos WHERE id=? AND cliente_id=?');
    $q->execute([(int)$_GET['editar'], $clienteId]);
    $editando = $q->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['accion_form'] ?? '', ['crear_producto','editar_producto'], true)) {
    try {
        if (!puede('crear_productos')) throw new RuntimeException('Tu usuario no tiene permiso para crear o editar productos.');
        validarCsrf('csrf_producto');
        $accion = $_POST['accion_form'];
        $id = (int)($_POST['producto_id'] ?? 0);
        $anterior = null;
        if ($accion === 'editar_producto') {
            $q = db()->prepare('SELECT * FROM costobene_productos WHERE id=? AND cliente_id=?');
            $q->execute([$id, $clienteId]); $anterior = $q->fetch();
            if (!$anterior) throw new RuntimeException('No se encontró el producto que querés editar.');
        }
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $unidad = trim((string)($_POST['unidad'] ?? 'unidad'));
        if ($unidad === 'otra') $unidad = trim((string)($_POST['unidad_otra'] ?? '')); 
        $moneda = strtoupper(trim((string)($_POST['moneda'] ?? 'ARS')));
        $volumen = filter_var($_POST['volumen_esperado'] ?? '0', FILTER_VALIDATE_FLOAT);
        $margen = filter_var($_POST['margen_porcentaje'] ?? '30', FILTER_VALIDATE_FLOAT);
        $horasPropias = filter_var($_POST['horas_trabajo'] ?? '0', FILTER_VALIDATE_FLOAT);
        $valorHoraPropio = filter_var($_POST['valor_hora'] ?? '0', FILTER_VALIDATE_FLOAT);
        $precioTexto = trim((string)($_POST['precio_manual'] ?? ''));
        $precio = $precioTexto === '' ? null : filter_var($precioTexto, FILTER_VALIDATE_FLOAT);
        if ($nombre === '' || mb_strlen($nombre)>150) throw new RuntimeException('Ingresá un nombre de hasta 150 caracteres.');
        if ($unidad === '' || mb_strlen($unidad)>50) throw new RuntimeException('Elegí una unidad de medida válida.');
        if (!in_array($moneda,['ARS','USD','EUR'],true)) throw new RuntimeException('La moneda no es válida.');
        if ($volumen === false || $volumen < 0) throw new RuntimeException('El volumen esperado debe ser mayor o igual a cero.');
        if ($margen === false || $margen < 0 || $margen > 90) throw new RuntimeException('El margen debe estar entre 0 y 90%.');
        if ($horasPropias === false || $horasPropias < 0 || $valorHoraPropio === false || $valorHoraPropio < 0) throw new RuntimeException('Las horas propias y el valor por hora deben ser iguales o mayores a cero.');
        if ($precioTexto !== '' && ($precio === false || $precio < 0)) throw new RuntimeException('El precio de venta no es válido.');
        if (esSuper()) {
            $c = db()->prepare('SELECT id FROM costobene_clientes WHERE id=? AND activo=1'); $c->execute([$clienteId]);
            if (!$c->fetch()) throw new RuntimeException('Seleccioná un cliente válido.');
        }
        $fotoPath = $anterior['foto_path'] ?? null;
        $nuevaFoto = subirArchivoSeguro($_FILES['imagen'] ?? [], 'productos', [
            'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'
        ], 6*1024*1024);
        if ($nuevaFoto) $fotoPath = $nuevaFoto;
        $datos = ['nombre'=>$nombre,'descripcion'=>$descripcion ?: null,'foto_path'=>$fotoPath,'unidad'=>$unidad,'moneda'=>$moneda,
            'volumen_esperado'=>(float)$volumen,'horas_trabajo'=>(float)$horasPropias,'valor_hora'=>(float)$valorHoraPropio,'margen_porcentaje'=>(float)$margen,'precio_manual'=>$precio];
        if ($accion === 'crear_producto') {
            $st = db()->prepare('INSERT INTO costobene_productos (cliente_id,nombre,descripcion,foto_path,unidad,moneda,volumen_esperado,horas_trabajo,valor_hora,margen_porcentaje,precio_manual) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$clienteId,$nombre,$datos['descripcion'],$fotoPath,$unidad,$moneda,$volumen,$horasPropias,$valorHoraPropio,$margen,$precio]);
            $id = (int)db()->lastInsertId();
            registrarAuditoriaProducto($id,'insert',null,$datos);
        } else {
            $st = db()->prepare('UPDATE costobene_productos SET nombre=?,descripcion=?,foto_path=?,unidad=?,moneda=?,volumen_esperado=?,horas_trabajo=?,valor_hora=?,margen_porcentaje=?,precio_manual=? WHERE id=? AND cliente_id=?');
            $st->execute([$nombre,$datos['descripcion'],$fotoPath,$unidad,$moneda,$volumen,$horasPropias,$valorHoraPropio,$margen,$precio,$id,$clienteId]);
            registrarAuditoriaProducto($id,'update',$anterior,$datos);
        }
        $destino = 'index.php?guardado=1&producto=' . $id;
        if (esSuper()) $destino .= '&cliente=' . $clienteId;
        header('Location: '.$destino); exit;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar. Verificá que el nombre no esté repetido para este cliente.';
        if (($_POST['accion_form'] ?? '') === 'editar_producto') {
            $q = db()->prepare('SELECT * FROM costobene_productos WHERE id=? AND cliente_id=?'); $q->execute([(int)($_POST['producto_id']??0),$clienteId]); $editando=$q->fetch() ?: null;
        }
    }
}

$clientes = esSuper() ? db()->query('SELECT id,nombre_empresa FROM costobene_clientes WHERE activo=1 ORDER BY nombre_empresa')->fetchAll() : [];
$st = db()->prepare('SELECT id,nombre,descripcion,foto_path,unidad,moneda,volumen_esperado,horas_trabajo,valor_hora,margen_porcentaje,precio_manual FROM costobene_productos WHERE cliente_id=? ORDER BY nombre');
$st->execute([$clienteId]); $productos=$st->fetchAll();
$seleccionado = (int)($_GET['producto'] ?? 0);
$editando = $editando ?: (isset($_GET['editar']) ? null : null);
$csrf = csrfToken('csrf_producto');
$val = function($campo,$def='') use ($editando) { return h($_POST[$campo] ?? ($editando[$campo] ?? $def)); };
$paginaActual='productos';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Productos y servicios - CostoPyme</title><link rel="stylesheet" href="style.css"></head><body>
<div class="app-shell"><?php require 'menu.php'; ?><main class="main">
<header class="page-head"><div><h1>Productos / Servicios</h1><p>Administrá el catálogo del negocio, promociones y datos para calcular su rentabilidad.</p></div><a class="btn btn-secondary" href="promociones.php">Promociones y descuentos</a><?php if (puede('crear_productos')): ?><a class="btn" href="#formProducto" onclick="document.getElementById('formProducto').hidden=false">＋ Agregar nuevo</a><?php endif; ?></header>
<?php if (isset($_GET['guardado'])): ?><div class="success">Los datos del producto o servicio se guardaron correctamente.</div><?php endif; ?>
<?php if ($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<?php if (esSuper()): ?><section class="panel"><label style="max-width:360px">Cliente<select onchange="location.href='index.php?cliente='+this.value"><option value="">Seleccionar cliente</option><?php foreach($clientes as $c): ?><option value="<?= (int)$c['id']?>" <?= (int)$c['id']===$clienteId?'selected':''?>><?=h($c['nombre_empresa'])?></option><?php endforeach;?></select></label></section><?php endif; ?>
<section class="panel"><div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:14px"><h2 style="margin:0">Mi catálogo</h2><span class="pill"><?=count($productos)?> registros</span></div>
<?php if (!$productos): ?><div class="empty"><b>Todavía no hay productos o servicios.</b><p>Agregá el primero para comenzar a calcular sus costos y rentabilidad.</p></div><?php else: ?><div class="catalogo"><?php foreach($productos as $p): ?><article class="producto-card">
<?php if($p['foto_path']): ?><img src="<?=h($p['foto_path'])?>" alt="<?=h($p['nombre'])?>"><?php else: ?><div class="product-placeholder">▦</div><?php endif; ?>
<strong><?=h($p['nombre'])?></strong><small><?=h($p['unidad'])?> · <?=h($p['moneda'])?></small><?php if($p['descripcion']): ?><small><?=h($p['descripcion'])?></small><?php endif; ?>
<small>Volumen: <?=h($p['volumen_esperado'])?> · Margen: <?=h($p['margen_porcentaje'])?>%</small>
<div class="actions"><?php if (puede('crear_productos')): ?><a class="btn btn-secondary" href="index.php?editar=<?=(int)$p['id']?><?=esSuper()?'&cliente='.$clienteId:''?>">Editar</a><?php endif; ?><a class="btn" href="costos.php?producto=<?=(int)$p['id']?><?=esSuper()?'&cliente='.$clienteId:''?>">Ver costos</a></div>
</article><?php endforeach;?></div><?php endif; ?></section>
<section class="panel"><div class="section-title"><h2>Promociones y descuentos</h2><a class="btn btn-secondary" href="promociones.php">Administrar promociones</a></div><p class="help">Creá descuentos individuales o aplicables a todos los productos y servicios, con control de vigencia y rentabilidad.</p></section>
<?php if (puede('crear_productos')): ?><section class="panel" id="formProducto" <?=($productos && !$editando && !$error)?'hidden':''?>><h2><?=$editando?'Editar producto o servicio':'Agregar producto o servicio'?></h2>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="accion_form" value="<?=$editando?'editar_producto':'crear_producto'?>"><input type="hidden" name="producto_id" value="<?=(int)($editando['id']??0)?>"><input type="hidden" name="cliente_id" value="<?=$clienteId?>">
<div class="form-grid"><label class="full">Nombre del producto o servicio *<input name="nombre" maxlength="150" required value="<?=$val('nombre')?>" placeholder="Ej. Torta de chocolate / Servicio de diseño"></label>
<label class="full">Descripción<textarea name="descripcion" placeholder="Describí brevemente el producto o servicio"><?=$val('descripcion')?></textarea></label>
<label>Imagen<input type="file" name="imagen" accept="image/png,image/jpeg,image/gif,image/webp"><span class="help">JPG, PNG, GIF o WEBP. Máximo 6 MB. <?=!empty($editando['foto_path'])?'Si no elegís otra, se conserva la actual.':''?></span></label>
<label>Unidad de medida *<select name="unidad" required><?php $unidades=['unidad'=>'Unidad','kg'=>'Kilogramo (kg)','g'=>'Gramo (g)','litro'=>'Litro','ml'=>'Mililitro (ml)','hora'=>'Hora','servicio'=>'Servicio','metro'=>'Metro','docena'=>'Docena','otra'=>'Otra...']; if($editando && !isset($unidades[$editando['unidad']])): ?><option value="<?=h($editando['unidad'])?>" selected><?=h($editando['unidad'])?></option><?php endif; foreach($unidades as $k=>$v):?><option value="<?=h($k)?>" <?=($val('unidad','unidad')===$k?'selected':'')?>><?=h($v)?></option><?php endforeach;?></select><input name="unidad_otra" placeholder="Escribí otra unidad" style="margin-top:6px" value="<?=($val('unidad')==='otra'?h($_POST['unidad_otra']??''):'')?>"></label>
<label>Moneda<select name="moneda"><option value="ARS" <?=$val('moneda','ARS')==='ARS'?'selected':''?>>Pesos (ARS)</option><option value="USD" <?=$val('moneda','ARS')==='USD'?'selected':''?>>Dólares (USD)</option><option value="EUR" <?=$val('moneda','ARS')==='EUR'?'selected':''?>>Euros (EUR)</option></select></label>
<label>Volumen esperado<input type="number" name="volumen_esperado" min="0" step="0.01" value="<?=$val('volumen_esperado','0')?>"></label>
<label>Horas de trabajo propio por período<input type="number" name="horas_trabajo" min="0" step="0.01" value="<?=$val('horas_trabajo','0')?>"><span class="help">Opcional: tiempo que aporta el dueño o equipo propio.</span></label>
<label>Valor por hora de trabajo propio<input type="number" name="valor_hora" min="0" step="0.01" value="<?=$val('valor_hora','0')?>"></label>
<label>Margen deseado (%)<input type="number" name="margen_porcentaje" min="0" max="90" step="0.01" value="<?=$val('margen_porcentaje','30')?>"></label>
<label>Precio de venta (opcional)<input type="number" name="precio_manual" min="0" step="0.01" value="<?=$val('precio_manual')?>" placeholder="Vacío = precio sugerido"></label></div>
<div class="actions"><button type="submit"><?=$editando?'Guardar cambios':'Crear producto o servicio'?></button><?php if($editando):?><a class="btn btn-secondary" href="index.php<?=esSuper()?'?cliente='.$clienteId:''?>">Cancelar</a><?php endif;?></div></form></section><?php endif; ?>
</main></div></body></html>
