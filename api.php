<?php
require 'helpers.php';
header('Content-Type: application/json; charset=utf-8');
function responder($datos, int $status = 200): void {
    http_response_code($status);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if (!usuario()) responder(['error' => 'Sesión vencida'], 401);
if (!puede('ver_dashboard')) responder(['error' => 'Sin permiso'], 403);
$accion = $_GET['accion'] ?? '';
$clienteId = clienteActual();
try {
    if ($accion === 'productos') {
        $s = db()->prepare('SELECT id,nombre,descripcion,foto_path,unidad,moneda,volumen_esperado,margen_porcentaje,precio_manual FROM costobene_productos WHERE cliente_id=? ORDER BY nombre');
        $s->execute([$clienteId]);
        responder($s->fetchAll());
    }
    if ($accion === 'producto') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) responder(['error' => 'ID inválido'], 400);
        $s = db()->prepare('SELECT id,nombre,descripcion,foto_path,unidad,moneda,volumen_esperado,horas_trabajo,valor_hora,margen_porcentaje,precio_manual FROM costobene_productos WHERE id=? AND cliente_id=?');
        $s->execute([$id, $clienteId]);
        $p = $s->fetch();
        if (!$p) responder(['error' => 'Producto no encontrado'], 404);

        $s = db()->prepare("SELECT c.tipo_costo,c.nombre,
                COALESCE(m.monto_asignado,c.monto) AS monto,
                c.monto AS monto_total,c.factura_path,c.horas_trabajo,c.valor_hora,
                (m.costo_id IS NOT NULL) AS compartido
            FROM costobene_costos_producto c
            LEFT JOIN costobene_costos_productos_compartidos m ON m.costo_id=c.id AND m.producto_id=?
            WHERE c.estado_aprobacion='aprobado' AND (m.producto_id IS NOT NULL
               OR (c.producto_id=? AND NOT EXISTS (SELECT 1 FROM costobene_costos_productos_compartidos mx WHERE mx.costo_id=c.id)))
            ORDER BY c.id");
        $s->execute([$id, $id]);
        $costos = $s->fetchAll();

        $s = db()->prepare('SELECT periodo,volumen_objetivo FROM costobene_objetivos_periodo_producto WHERE producto_id=?');
        $s->execute([$id]);
        $objetivos = [];
        foreach ($s as $o) $objetivos[$o['periodo']] = (float)$o['volumen_objetivo'];
        responder(['producto' => $p, 'costos' => $costos, 'objetivos' => $objetivos]);
    }
    responder(['error' => 'Acción inválida'], 400);
} catch (Throwable $e) {
    error_log($e->getMessage());
    responder(['error' => 'Error al consultar la base de datos'], 500);
}
