<?php
$paginaActual = $paginaActual ?? 'productos';
$menuItems = [
 'productos'=>['Productos / Servicios','package','index.php','ver_dashboard'],
 'costos'=>['Costos','receipt','costos.php','ver_dashboard'],
 'produccion'=>['Producción','factory','produccion.php','cargar_produccion'],
 'reportes'=>['Reportes','chart','reportes.php','ver_reportes'],
 'simulador'=>['Simulador','calculator','simulador.php','ver_dashboard'],
 'usuarios'=>['Usuarios e invitaciones','users','usuarios.php','gestionar_usuarios'],
 'perfil'=>['Mi perfil','user','perfil.php',null],
];
$glyph=['package'=>'▦','receipt'=>'▤','chart'=>'▥','calculator'=>'⌗','user'=>'◉','tags'=>'％','users'=>'♙','factory'=>'⚙'];
?>
<aside class="sidebar"><a class="brand" href="index.php"><img class="brand-logo" src="assets/logo_finanzas.png" alt="Logo CostoPyme"><span><b>CostoPyme</b><small>Gestión y rentabilidad</small></span></a><div class="nav-caption">MENÚ PRINCIPAL</div><nav class="nav-menu">
<?php foreach($menuItems as $key=>[$label,$icon,$url,$permiso]): if($permiso===null || puede($permiso)): ?><a class="nav-link <?=$paginaActual===$key?'active':''?>" href="<?=h($url)?>"><span class="nav-icon"><?=$glyph[$icon]??'•'?></span><span><?=h($label)?></span></a><?php if($key==='productos' && puede('ver_dashboard')):?><a class="nav-link nav-sub-link <?=$paginaActual==='promociones'?'active':''?>" href="promociones.php"><span class="nav-icon">％</span><span>Promociones y descuentos</span></a><?php elseif($key==='costos' && puede('ver_dashboard')):?><a class="nav-link nav-sub-link" href="costos.php#comisiones"><span class="nav-icon">◈</span><span>Comisiones</span></a><?php endif;?><?php endif; endforeach; ?>
</nav><div class="sidebar-bottom"><div class="user-mini"><span class="avatar"><?=h(mb_substr((string)usuario()['nombre'],0,1))?></span><span><b><?=h(usuario()['nombre'])?></b><small><?=h(implode(', ',usuario()['roles']))?></small></span></div><a class="nav-link" href="logout.php"><span class="nav-icon">↪</span><span>Cerrar sesión</span></a></div></aside>
