<?php
require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

$urls = [];
foreach (['', 'inmuebles', 'inmuebles?operacion=venta', 'inmuebles?operacion=arriendo', 'proyectos', 'propietarios', 'nosotros', 'contacto', 'pagos', 'politica-de-datos'] as $path) {
    $urls[] = [url($path), null];
}
foreach (locations() as $loc) {
    $urls[] = [url('inmuebles?ubicacion=' . $loc['slug']), null];
}
foreach (query('SELECT slug, updated_at FROM properties WHERE published = 1')->fetchAll() as $p) {
    $urls[] = [property_url($p), date('Y-m-d', strtotime($p['updated_at']))];
}
foreach (query('SELECT slug FROM projects WHERE published = 1')->fetchAll() as $pr) {
    $urls[] = [project_url($pr), null];
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as [$loc, $mod]) {
    echo '  <url><loc>', e($loc), '</loc>', $mod ? '<lastmod>' . $mod . '</lastmod>' : '', "</url>\n";
}
echo '</urlset>', "\n";
