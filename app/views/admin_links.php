<?php
// Sugerencias para los campos de enlace del panel (menús, botones).
$links = ['/' => 'Inicio', '/inmuebles' => 'Todos los inmuebles', '/inmuebles?operacion=venta' => 'Inmuebles en venta',
    '/inmuebles?operacion=arriendo' => 'Inmuebles en arriendo', '/proyectos' => 'Proyectos', '/pagos' => 'Pagos en línea', '/contacto' => 'Contacto'];
foreach (query('SELECT slug, title FROM pages ORDER BY title')->fetchAll() as $pg) {
    $links['/' . $pg['slug']] = tokens($pg['title']);
}
foreach (locations() as $loc) {
    $links['/inmuebles?ubicacion=' . $loc['slug']] = 'Inmuebles en ' . $loc['name'];
}
foreach (TYPES_PLURAL as $key => $label) {
    $links['/inmuebles?tipo=' . $key] = $label;
}
?>
<datalist id="enlaces">
  <?php foreach ($links as $href => $label): ?><option value="<?= e($href) ?>"><?= e($label) ?></option><?php endforeach; ?>
</datalist>
