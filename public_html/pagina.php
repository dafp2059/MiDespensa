<?php
// Páginas creadas desde el panel (Diseño > Páginas): /nosotros, /propietarios, /politica-de-datos…
require __DIR__ . '/../app/bootstrap.php';

// Dirección corta para avisos: /105 abre el inmueble con código 105.
if (preg_match('/^\d+$/', input('slug'))) {
    $property = query('SELECT slug FROM properties WHERE code = ? AND published = 1', [ltrim(input('slug'), '0')])->fetch();
    if ($property) {
        header('Location: ' . url('inmueble/' . $property['slug']), true, 301);
        exit;
    }
}

$p = find_page(input('slug'));
if (!$p) {
    require __DIR__ . '/404.php';
    exit;
}
view('page', ['p' => $p]);
