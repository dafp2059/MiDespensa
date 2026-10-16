<?php
// Páginas creadas desde el panel (Diseño > Páginas): /nosotros, /propietarios, /politica-de-datos…
require __DIR__ . '/../app/bootstrap.php';

$p = find_page(input('slug'));
if (!$p) {
    require __DIR__ . '/404.php';
    exit;
}
view('page', ['p' => $p]);
