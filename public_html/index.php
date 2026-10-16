<?php
require __DIR__ . '/../app/bootstrap.php';

// Secciones en el orden elegido en el panel (Diseño > Inicio).
$sections = [];
foreach (setting_list('home.sections') as $s) {
    if (isset(HOME_SECTIONS[$s['key'] ?? '']) && !empty($s['visible'])) {
        $sections[] = $s['key'];
    }
}

$page = [
    'title' => tokens(setting('seo.home_title')) ?: null,
    'description' => tokens(setting('seo.home_description')) ?: 'Inmobiliaria en ' . site('city') . '. Casas, apartamentos, fincas, lotes y locales en venta y arriendo, con asesoría personalizada.',
    'canonical' => url(''),
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateAgent',
        'name' => site('name'),
        'url' => url(''),
        'telephone' => '+57' . preg_replace('/\D/', '', site('phone')),
        'email' => site('email'),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => site('address'), 'addressLocality' => site('city'), 'addressCountry' => 'CO'],
        'sameAs' => array_values(array_filter([site('instagram'), site('facebook')])),
    ],
];
view('header', compact('page'));

foreach ($sections as $section) {
    view('home/' . $section);
}

view('footer');
